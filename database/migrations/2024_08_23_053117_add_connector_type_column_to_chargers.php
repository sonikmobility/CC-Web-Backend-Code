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
        Schema::table('chargers', function (Blueprint $table) {
            $table->enum('connector_type',[0,1])->after('charge_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chargers', function (Blueprint $table) {
            $table->dropColumn('connector_type');
        });
    }
};
