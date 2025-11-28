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
        Schema::connection('mysql_employees')->table('Employees', function (Blueprint $table) {
                     $table->enum('employee_type',['organik', 'mitra'])->default('organik')->after('date_of_joining');
             });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::connection('mysql_employees')->table('Employees', fn (Blueprint $table) => $table->dropColumn(['employee_type']));
    }
};
