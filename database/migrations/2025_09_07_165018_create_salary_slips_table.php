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
        Schema::connection('mysql_employees')->create('SalarySlips', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 20);
            $table->string('periode');
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->integer('amount');
            $table->timestamps();

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('Employees')
                ->onDelete('cascade');


            $table->foreign('salary_component_id')
                ->references('id')
                ->on('SalaryComponents')
                ->onDelete('cascade');

            $table->foreign('payroll_id')
                ->references('id')
                ->on('Payrolls')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('SalarySlips');
    }
};
