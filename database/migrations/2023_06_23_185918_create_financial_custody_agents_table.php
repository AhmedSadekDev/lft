<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('financial_custody_agents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('transferer_id')->unsigned()->nullable();
            $table->string('transferer_type')->nullable();
            $table->double('value')->nullable();
            $table->bigInteger('agent_id')->unsigned()->nullable();
            $table->tinyInteger('type')->default(1)->comment("1 => from dashboard; 2 => Transfer to a agent");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_custody_agents');
    }
};
