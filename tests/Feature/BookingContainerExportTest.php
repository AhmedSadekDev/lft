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
            $table->string('booking_number')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('factory_id')->nullable();
            $table->boolean('taxed')->default(1);
            $table->timestamps();
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
            $table->integer('status')->default(0);
            $table->boolean('superagent_specification_approved')->default(0);
            $table->boolean('is_in_loading')->default(0);
            $table->boolean('superagent_loading_approved')->default(0);
            $table->boolean('superagent_unloading_approved')->default(0);
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
            $table->string('invoice_number')->nullable();
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

    /** @dataProvider listingFilters */
    public function test_export_matches_filtered_listing(array $filters, array $expectedBookingIds): void
    {
        Schema::table('factories', fn (Blueprint $table) => $table->string('name')->nullable());
        DB::table('bookings')->where('id', 1)->update([
            'booking_number' => 'FIRST', 'company_id' => 1, 'created_at' => '2026-09-01 12:00:00',
        ]);
        DB::table('bookings')->where('id', 2)->update([
            'booking_number' => 'SECOND', 'company_id' => 2, 'created_at' => '2026-09-15 12:00:00', 'taxed' => 0,
        ]);
        DB::table('booking_containers')->where('booking_id', 1)->update([
            'status' => 1, 'superagent_specification_approved' => 1, 'is_in_loading' => 1,
        ]);
        DB::table('invoices')->insert(['booking_id' => 2, 'invoice_number' => 'INV-SECOND']);

        $request = \Illuminate\Http\Request::create('/bookings', 'GET', $filters);
        $this->app->instance('request', $request);
        $view = (new \App\Http\Controllers\Admin\BookingController)->index($request);
        $listedIds = $view->getData()['bookings']->getCollection()->pluck('id')->all();
        $this->assertEqualsCanonicalizing($expectedBookingIds, $listedIds);

        Excel::fake();
        (new \App\Http\Controllers\Admin\ContainerController)->export($request);
        Excel::assertDownloaded('booking_containers.xlsx', function (BookingContainerDetails $export) use ($listedIds) {
            $expectedContainerIds = BookingContainer::whereIn('booking_id', $listedIds)->pluck('id')->all();
            $this->assertEqualsCanonicalizing($expectedContainerIds, $export->ids);
            return true;
        });
    }

    public static function listingFilters(): array
    {
        return [
            'all' => [[], [1, 2]],
            'stage' => [['stage' => 'loading'], [1]],
            'legacy status' => [['status' => 'loading'], [1]],
            'stage overrides status' => [['stage' => 'all', 'status' => 'loading'], [1, 2]],
            'waiting' => [['stage' => 'waiting'], []],
            'invoiced' => [['stage' => 'invoiced'], [2]],
            'date range' => [['date_from' => '2026-08-31', 'date_to' => '2026-09-09'], [1]],
            'date from' => [['date_from' => '2026-09-10'], [2]],
            'date to' => [['date_to' => '2026-09-09'], [1]],
            'company' => [['company' => '2'], [2]],
            'tax' => [['tax_status' => '1'], [1]],
            'without invoice' => [['invoice_status' => '0'], [1]],
            'with invoice' => [['invoice_status' => '1'], [2]],
            'container search' => [['search' => 'VALID'], [1]],
            'invoice search' => [['search' => 'INV-SECOND'], [2]],
            'combined' => [['stage' => 'loading', 'company' => '1', 'date_to' => '2026-09-09', 'invoice_status' => '0'], [1]],
        ];
    }

    public function test_export_handles_missing_container_and_includes_employee_and_notes_in_xlsx(): void
    {
        DB::table('bookings')->where('id', 1)->update(['created_at' => '2026-09-01 12:00:00']);
        $export = new BookingContainerDetails([1, 2, 3]);
        $rows = $export->collection()->keyBy('container_no');
        $this->assertSame('2026-09-01', $rows['MISSING']['date']->toDateString());
        $this->assertSame('2026-09-01', $rows['VALID']['date']->toDateString());
        $this->assertNull($rows['DELETED']['date']);
        $this->assertCount(3, $rows);
        $this->assertCount(2, $rows->pluck('id')->unique());
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
            $this->assertSame('2026-09-01 12:00:00', $sheet->getCell('B2')->getValue());
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
