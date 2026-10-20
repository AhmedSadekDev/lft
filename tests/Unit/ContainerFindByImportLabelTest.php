<?php

namespace Tests\Unit;

use App\Models\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContainerFindByImportLabelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('containers', function (Blueprint $t) {
            $t->id();
            $t->string('size')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
        });

        Container::create(['size' => '20', 'type' => 'هاي كيوب']);
        Container::create(['size' => '40', 'type' => 'هاي كيوب']);
        Container::create(['size' => '40', 'type' => 'عادية']);
    }

    public function test_matches_export_full_name_with_dash(): void
    {
        $c = Container::findByImportLabel('40 - هاي كيوب');
        $this->assertNotNull($c);
        $this->assertSame('40', (string) $c->size);
        $this->assertSame('هاي كيوب', $c->type);
    }

    public function test_matches_size_and_type_without_dash(): void
    {
        $c = Container::findByImportLabel('40 هاي كيوب');
        $this->assertNotNull($c);
        $this->assertSame('40', (string) $c->size);
        $this->assertSame('هاي كيوب', $c->type);
    }

    public function test_type_only_prefers_40_when_ambiguous(): void
    {
        $c = Container::findByImportLabel('هاي كيوب');
        $this->assertNotNull($c);
        $this->assertSame('40', (string) $c->size);
    }

    public function test_matches_20_high_cube(): void
    {
        $c = Container::findByImportLabel('20 - هاي كيوب');
        $this->assertNotNull($c);
        $this->assertSame('20', (string) $c->size);
    }
}
