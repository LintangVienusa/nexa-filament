<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql_inventory')->create('MappingWilayah', function (Blueprint $table) {
            $table->id();
            $table->string('province_name');
            $table->string('province_code', 10)->nullable();

            $table->string('district_name');
            $table->string('district_code', 10)->nullable();

            $table->string('station_name')->nullable();
            $table->string('station_code', 10)->nullable();

            $table->string('village_name');
            $table->string('village_code', 10)->nullable();
            $table->timestamps();

            $table->index(['province_code', 'district_code', 'station_code', 'village_code'], 'mw_index');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('MappingWilayah');
    }
};
