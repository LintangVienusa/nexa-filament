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
        Schema::connection('mysql_employees')->table('Payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('salary_slip_id')->nullable()->after('employee_id');

            $table->foreign('salary_slip_id')
              ->references('id')
              ->on('SalarySlips')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->table('Payrolls', function (Blueprint $table) {
            $table->dropForeign(['salary_slip_id']);
            $table->dropColumn('salary_slip_id');
        });
    }
};
