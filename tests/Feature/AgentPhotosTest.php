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
        foreach (['agent-photos.index', 'agent-photos.image', 'agent-photos.destroy'] as $name) {
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
}
