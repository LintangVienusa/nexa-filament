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
        Schema::connection('mysql_inventory')->create('MappingHomepass', function (Blueprint $table) {
            $table->id();
            $table->string('province_name',50);
            $table->string('regency_name',50);
            $table->string('village_name',50);
            $table->string('station_name',50);
            $table->string('feeder_name',50);
            $table->string('ODC',50);
            $table->string('ODP',50);
            $table->timestamps();

            
            $table->unique(['id', 'province_name', 'regency_name', 'village_name', 'station_name', 'feeder_name', 'ODC','ODP'],'mappinghomepass_unique_idx');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('MappingHomepass');
    }
};
