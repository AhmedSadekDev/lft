<?php

namespace Tests\Feature;

use App\Exports\BookingContainerDetails;
use App\Models\BookingContainer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class BookingContainerExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (['companies', 'factories', 'shipping_agents', 'cities_and_regions'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->integer('type_of_action')->default(0);
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('booking_containers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('container_id')->nullable();
            $table->string('container_no')->nullable();
            $table->timestamps();
        });
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('size');
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
        });
        Schema::create('delivery_policies', fn (Blueprint $table) => $table->id());
        Schema::create('delivery_policy_containers', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_container_id');
            $table->unsignedBigInteger('delivery_policy_id');
            $table->timestamps();
        });
        DB::table('employees')->insert(['id' => 1, 'name' => 'أحمد']);
        DB::table('bookings')->insert([
            ['id' => 1, 'employee_id' => 1, 'employee_name' => 'التواصل قبل التحميل'],
            ['id' => 2, 'employee_id' => null, 'employee_name' => null],
        ]);
        DB::table('containers')->insert(['id' => 1, 'type' => 'HQ', 'size' => '40']);
        DB::table('booking_containers')->insert([
            ['id' => 1, 'booking_id' => 1, 'container_id' => null, 'container_no' => 'MISSING', 'created_at' => '2026-09-23'],
            ['id' => 2, 'booking_id' => 1, 'container_id' => 1, 'container_no' => 'VALID', 'created_at' => '2026-09-23'],
            ['id' => 3, 'booking_id' => 2, 'container_id' => 999, 'container_no' => 'DELETED', 'created_at' => '2026-09-23'],
        ]);
    }

    public function test_export_handles_missing_container_and_includes_employee_and_notes_in_xlsx(): void
    {
        $export = new BookingContainerDetails([1, 2, 3]);
        $rows = $export->collection()->keyBy('container_no');
        $this->assertSame('', BookingContainer::find(1)->ContainerType);
        $this->assertSame('', BookingContainer::find(3)->ContainerType);
        $this->assertSame('', $rows['MISSING']['container_type_and_size']);
        $this->assertSame('40 - HQ', $rows['VALID']['container_type_and_size']);
        $this->assertSame('أحمد', $rows['MISSING']['employee']);
        $this->assertSame('التواصل قبل التحميل', $rows['MISSING']['notes']);
        $this->assertSame('', $rows['DELETED']['employee']);
        $this->assertSame('', $rows['DELETED']['notes']);
        $this->assertCount(count($export->headings()), $rows['MISSING']);

        $path = tempnam(sys_get_temp_dir(), 'booking-export-');
        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $workbook = IOFactory::load($path);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('الموظف', $sheet->getCell('E1')->getValue());
            $this->assertSame('ملاحظات', $sheet->getCell('F1')->getValue());
            $this->assertSame('أحمد', $sheet->getCell('E2')->getValue());
            $this->assertSame('التواصل قبل التحميل', $sheet->getCell('F2')->getValue());
            $this->assertSame(4, $sheet->getHighestRow());
            $workbook->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }
}
