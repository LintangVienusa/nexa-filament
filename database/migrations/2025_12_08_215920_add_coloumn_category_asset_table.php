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
        Schema::connection('mysql_inventory')->table('CategoryAsset', function (Blueprint $table) {
            $table->enum('info_sn',['yes', 'no'])->default('yes')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
         Schema::connection('mysql_employees')->table('CategoryAsset', fn (Blueprint $table) => $table->dropColumn(['info_sn']));
    }
};
