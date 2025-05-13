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
        Schema::table('charging_history', function (Blueprint $table) {
            $table->unique(['booking_id', 'charger_station_id'], 'unique_booking_charger');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charging_history', function (Blueprint $table) {
            $table->dropUnique('unique_booking_charger');
        });
    }
};
