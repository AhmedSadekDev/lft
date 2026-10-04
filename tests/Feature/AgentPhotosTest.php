<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentPhoto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgentPhotosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Storage::fake('agent_photos');
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('wallet')->default(1000);
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_01_000001_create_agent_photos_table.php'))->up();
        (require database_path('migrations/2026_10_05_000001_add_booking_fields_to_agent_photos_table.php'))->up();
        Agent::create(['id' => 1, 'name' => 'First agent']);
        Agent::create(['id' => 2, 'name' => 'Second agent']);
    }

    public function test_uploads_multiple_images_without_booking_or_wallet_changes(): void
    {
        $this->actingAs(Agent::find(1), 'agent');
        $response = $this->postJson('/api/agent/photos', ['images' => [
            UploadedFile::fake()->image('release.jpg'), UploadedFile::fake()->image('delivery.png'),
        ], 'agent_id' => 2])->assertOk()->assertJsonPath('status', true)->assertJsonCount(2, 'data');
        $this->assertDatabaseCount('agent_photos', 2);
        foreach (AgentPhoto::all() as $photo) {
            $this->assertSame(1, (int) $photo->agent_id);
            Storage::disk('agent_photos')->assertExists($photo->path);
            $this->get($response->json('data.'.($photo->id - 1).'.image'))->assertOk();
        }
        $this->assertSame(1000.0, (float) Agent::find(1)->wallet);
    }

    public function test_agent_only_lists_and_views_their_own_images(): void
    {
        $own = AgentPhoto::create(['agent_id' => 1, 'path' => 'own.jpg', 'original_name' => 'own.jpg']);
        $other = AgentPhoto::create(['agent_id' => 2, 'path' => 'other.jpg', 'original_name' => 'other.jpg']);
        $this->actingAs(Agent::find(1), 'agent');
        $this->getJson('/api/agent/photos')->assertOk()->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $own->id)->assertJsonMissing(['path' => 'own.jpg']);
        $this->getJson(route('api.agent.photos.image', $other))->assertNotFound();
    }

    public function test_rejects_non_images_and_oversized_images_without_saving_any_files(): void
    {
        $this->actingAs(Agent::find(1), 'agent');
        foreach ([UploadedFile::fake()->create('document.pdf', 1, 'application/pdf'),
            UploadedFile::fake()->image('large.jpg')->size(10241)] as $invalid) {
            $this->postJson('/api/agent/photos', ['images' => [UploadedFile::fake()->image('valid.jpg'), $invalid]])
                ->assertUnprocessable();
        }
        $this->assertDatabaseCount('agent_photos', 0);
        $this->assertSame([], Storage::disk('agent_photos')->allFiles());
    }

    public function test_upload_failure_rolls_back_records_and_cleans_files(): void
    {
        $this->actingAs(Agent::find(1), 'agent');
        AgentPhoto::creating(function ($photo) {
            if ($photo->original_name === 'second.jpg') {
                throw new \RuntimeException('Simulated database failure');
            }
        });
        try {
            $this->postJson('/api/agent/photos', ['images' => [
                UploadedFile::fake()->image('first.jpg'), UploadedFile::fake()->image('second.jpg'),
            ]])->assertStatus(500)->assertJsonPath('status', false);
            $this->assertDatabaseCount('agent_photos', 0);
            $this->assertSame([], Storage::disk('agent_photos')->allFiles());
        } finally {
            AgentPhoto::flushEventListeners();
        }
    }

    public function test_dashboard_filters_photos_by_agent_and_date(): void
    {
        foreach ([[1, '2026-09-29'], [1, '2026-09-30'], [2, '2026-09-30']] as [$agentId, $date]) {
            AgentPhoto::create(['agent_id' => $agentId, 'path' => 'photo.jpg', 'original_name' => 'photo.jpg'])
                ->forceFill(['created_at' => $date.' 12:00:00'])->save();
        }
        $request = Request::create('/dashboard/agent-photos', 'GET', ['agent_id' => 1, 'from' => '2026-09-30', 'to' => '2026-09-30']);
        $view = app(\App\Http\Controllers\Admin\AgentPhotoController::class)->index($request);
        $this->assertSame('admin.agent-photos.index', $view->name());
        $this->assertSame(1, $view->getData()['photos']->total());
        $this->assertSame(2, $view->getData()['photos']->first()->id);
    }

    public function test_dashboard_routes_require_existing_agent_view_permission(): void
    {
        foreach ([
            'agent-photos.index',
            'agent-photos.image',
            'agent-photos.destroy',
            'agent-photos.search-bookings',
            'agent-photos.assign',
            'agent-photos.bulk-destroy',
        ] as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('auth', $middleware);
            $this->assertContains('permission:agents.index', $middleware);
        }
    }

    public function test_dashboard_image_requires_permission_and_streams_the_saved_file(): void
    {
        $photo = AgentPhoto::create(['agent_id' => 1, 'path' => 'private.jpg', 'original_name' => 'private.jpg']);
        Storage::disk('agent_photos')->put('private.jpg', 'saved-photo-content');
        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('can')->with('agents.index')->andReturn(false);
        $this->actingAs($user, 'web');
        $this->getJson(route('agent-photos.image', $photo))->assertForbidden();

        $allowed = \Mockery::mock(\App\Models\User::class)->makePartial();
        $allowed->shouldReceive('can')->with('agents.index')->andReturn(true);
        $this->actingAs($allowed, 'web');
        $response = $this->get(route('agent-photos.image', $photo))->assertOk();
        $this->assertSame('saved-photo-content', $response->streamedContent());
    }

    public function test_dashboard_can_delete_a_photo_and_its_saved_file(): void
    {
        $photo = AgentPhoto::create(['agent_id' => 1, 'path' => 'to-delete.jpg', 'original_name' => 'to-delete.jpg']);
        Storage::disk('agent_photos')->put($photo->path, 'saved-photo-content');
        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('can')->with('agents.index')->andReturn(true);
        $this->actingAs($user, 'web');

        $this->delete(route('agent-photos.destroy', $photo))->assertRedirect();

        $this->assertDatabaseMissing('agent_photos', ['id' => $photo->id]);
        Storage::disk('agent_photos')->assertMissing('to-delete.jpg');
    }

    public function test_dashboard_accepts_an_end_date_without_a_start_date(): void
    {
        $request = Request::create('/dashboard/agent-photos', 'GET', ['to' => '2026-09-30']);
        $view = app(\App\Http\Controllers\Admin\AgentPhotoController::class)->index($request);
        $this->assertSame(0, $view->getData()['photos']->total());
    }

    public function test_unauthenticated_users_cannot_upload_or_list_images(): void
    {
        $this->getJson('/api/agent/photos')->assertUnauthorized();
        $this->postJson('/api/agent/photos')->assertUnauthorized();
    }

    public function test_bulk_destroy_deletes_multiple_photos_and_files(): void
    {
        $photo1 = AgentPhoto::create(['agent_id' => 1, 'path' => 'p1.jpg', 'original_name' => 'p1.jpg']);
        $photo2 = AgentPhoto::create(['agent_id' => 1, 'path' => 'p2.jpg', 'original_name' => 'p2.jpg']);
        Storage::disk('agent_photos')->put('p1.jpg', 'content-1');
        Storage::disk('agent_photos')->put('p2.jpg', 'content-2');

        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('can')->with('agents.index')->andReturn(true);
        $this->actingAs($user, 'web');

        $response = $this->postJson(route('agent-photos.bulk-destroy'), [
            'photo_ids' => [$photo1->id, $photo2->id],
        ])->assertOk();

        $this->assertTrue($response->json('status'));
        $this->assertSame(2, $response->json('deleted_count'));
        $this->assertDatabaseMissing('agent_photos', ['id' => $photo1->id]);
        $this->assertDatabaseMissing('agent_photos', ['id' => $photo2->id]);
        Storage::disk('agent_photos')->assertMissing('p1.jpg');
        Storage::disk('agent_photos')->assertMissing('p2.jpg');
    }

    public function test_assign_photo_copies_to_public_storage_and_creates_booking_paper(): void
    {
        Storage::fake('public');
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->nullable();
            $table->timestamps();
        });
        Schema::create('booking_containers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('container_no')->nullable();
            $table->string('sail_of_number')->nullable();
            $table->timestamps();
        });
        Schema::create('booking_papers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('booking_container_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->tinyInteger('type')->default(1);
            $table->timestamps();
        });
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->unsignedBigInteger('imageable_id');
            $table->string('imageable_type');
            $table->timestamps();
        });

        $booking = \App\Models\Booking::create(['booking_number' => 'BK-999']);
        $container = \App\Models\BookingContainer::create(['booking_id' => $booking->id, 'container_no' => 'MSCU12345']);

        $photo = AgentPhoto::create(['agent_id' => 1, 'path' => 'agent_pic.jpg', 'original_name' => 'agent_pic.jpg']);
        Storage::disk('agent_photos')->put('agent_pic.jpg', 'fake-image-bytes');

        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('can')->with('agents.index')->andReturn(true);
        $this->actingAs($user, 'web');

        $response = $this->postJson(route('agent-photos.assign'), [
            'photo_ids' => [$photo->id],
            'booking_id' => $booking->id,
            'booking_container_id' => $container->id,
            'type' => 1,
        ])->assertOk();

        $this->assertTrue($response->json('status'));
        $photo->refresh();
        $this->assertSame((int) $booking->id, (int) $photo->booking_id);
        $this->assertSame((int) $container->id, (int) $photo->booking_container_id);
        $this->assertNotNull($photo->assigned_at);
        $this->assertNotNull($photo->booking_paper_id);

        $this->assertDatabaseHas('booking_papers', [
            'id' => $photo->booking_paper_id,
            'booking_id' => $booking->id,
            'booking_container_id' => $container->id,
            'agent_id' => 1,
            'type' => 1,
        ]);
        $this->assertDatabaseHas('images', [
            'imageable_id' => $photo->booking_paper_id,
            'imageable_type' => \App\Models\BookingPaper::class,
        ]);
    }

    public function test_search_bookings_finds_by_booking_number_and_container_no(): void
    {
        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('booking_number')->nullable();
                $table->timestamps();
            });
            Schema::create('booking_containers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('booking_id');
                $table->string('container_no')->nullable();
                $table->string('sail_of_number')->nullable();
                $table->timestamps();
            });
        }

        $booking = \App\Models\Booking::create(['booking_number' => 'SPECIAL-BK-77']);
        \App\Models\BookingContainer::create(['booking_id' => $booking->id, 'container_no' => 'CONT-TARGET-99']);

        $user = \Mockery::mock(\App\Models\User::class)->makePartial();
        $user->shouldReceive('can')->with('agents.index')->andReturn(true);
        $this->actingAs($user, 'web');

        // Search by booking number
        $res1 = $this->getJson(route('agent-photos.search-bookings', ['q' => 'SPECIAL-BK']))->assertOk();
        $this->assertCount(1, $res1->json());
        $this->assertSame((int) $booking->id, (int) $res1->json('0.id'));

        // Search by container number
        $res2 = $this->getJson(route('agent-photos.search-bookings', ['q' => 'TARGET-99']))->assertOk();
        $this->assertCount(1, $res2->json());
        $this->assertSame((int) $booking->id, (int) $res2->json('0.id'));
        $this->assertTrue($res2->json('0.containers.0.is_match'));
    }

    public function test_dashboard_filters_by_unassigned_and_assigned_status(): void
    {
        $unassigned = AgentPhoto::create(['agent_id' => 1, 'path' => 'u.jpg', 'original_name' => 'u.jpg']);
        $assigned = AgentPhoto::create([
            'agent_id' => 1,
            'path' => 'a.jpg',
            'original_name' => 'a.jpg',
            'booking_id' => 999,
            'assigned_at' => now(),
        ]);

        $reqUnassigned = Request::create('/dashboard/agent-photos', 'GET', ['status' => 'unassigned']);
        $viewUnassigned = app(\App\Http\Controllers\Admin\AgentPhotoController::class)->index($reqUnassigned);
        $this->assertSame(1, $viewUnassigned->getData()['photos']->total());
        $this->assertSame($unassigned->id, $viewUnassigned->getData()['photos']->first()->id);

        $reqAssigned = Request::create('/dashboard/agent-photos', 'GET', ['status' => 'assigned']);
        $viewAssigned = app(\App\Http\Controllers\Admin\AgentPhotoController::class)->index($reqAssigned);
        $this->assertSame(1, $viewAssigned->getData()['photos']->total());
        $this->assertSame($assigned->id, $viewAssigned->getData()['photos']->first()->id);
    }
}

