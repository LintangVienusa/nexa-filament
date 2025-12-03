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
        Schema::connection('mysql_inventory')->table('FeederDetail', function (Blueprint $table) {
            
            $table->text('pulling_cable_b')->nullable()->after('pulling_cable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->table('FeederDetail', fn (Blueprint $table) => $table->dropColumns(['pulling_cable_b']));
        
    }
};
