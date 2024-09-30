<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        // JOIN & COPY BOOKINGS, SECOND_BOOKINGS, THIRD_BOOKINGS
        $old_data = DB::table('bookings')
            ->select([
                'bookings.id as id',
                'bookings.company_id as company_id',
                'bookings.employee_id as employee_id',
                'second_bookings.shipping_agent_id as shipping_agent_id',
                'second_bookings.booking_number as booking_number',
                'second_bookings.certificate_number as certificate_number',
                'second_bookings.type_of_action as type_of_action',
                'second_bookings.discharge_date as discharge_date',
                'second_bookings.permit_end_date as permit_end_date',
                'bookings.created_at as created_at',
                'bookings.updated_at as updated_at',
            ])
            ->leftJoin('second_bookings', 'bookings.id', 'second_bookings.booking_id')
            ->leftJoin('third_bookings', 'bookings.id', 'third_bookings.booking_id')
            ->get()
            ->toArray();

        // EDIT BOOKINGS TABLE
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('second_bookings');
        Schema::dropIfExists('third_bookings');
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->bigInteger('employee_id')->unsigned()->nullable();
            $table->bigInteger('shipping_agent_id')->unsigned()->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('shipping_agent_id')->references('id')->on('shipping_agents')->onDelete('set null');

            $table->bigInteger('company_json')->nullable();
            $table->bigInteger('employee_json')->nullable();
            $table->bigInteger('shipping_agent_json')->nullable();

            $table->string('booking_number', 255)->nullable();
            $table->string('certificate_number', 100)->nullable();

            $table->string('type_of_action', 100)->nullable();
            $table->date('discharge_date')->nullable(); // todo: rename this into arrival_date
            $table->date('permit_end_date')->nullable();

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
        foreach ($old_data as $row)
            Booking::create(
                get_object_vars($row)
            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->bigInteger('employee_id')->unsigned()->nullable();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->tinyInteger('taxed')->default(0)->after('employee_id')->comment('0 => No Taxed, 1 => Taxed');

            $table->timestamps();
        });

        Schema::dropIfExists('second_bookings');
        Schema::create('second_bookings', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->bigInteger('shipping_agent_id')->unsigned()->nullable()->after('booking_id');

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('shipping_agent_id')->references('id')->on('shipping_agents')->onDelete('set null');

            $table->string('shipping_agent', 100)->nullable();
            $table->string('booking_number', 255)->nullable()->change();
            $table->string('certificate_number', 100)->nullable()->change();
            $table->string('type_of_service', 100)->nullable();
            $table->string('type_of_action', 100)->nullable();
            $table->date('discharge_date')->nullable();
            $table->date('permit_end_date')->nullable();

            $table->timestamps();
        });

        Schema::dropIfExists('third_bookings');
        Schema::create('third_bookings', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('booking_id')->unsigned()->nullable();
            $table->bigInteger('container_id')->unsigned()->nullable()->after('factory_id');
            $table->bigInteger('factory_id')->unsigned()->nullable();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('container_id')->references('id')->on('containers')->onDelete('set null');
            $table->foreign('factory_id')->references('id')->on('factories')->onDelete('cascade');

            $table->float('quantity')->nullable();
            $table->string('unit', 100)->nullable();

            $table->timestamps();
        });
    }
};
