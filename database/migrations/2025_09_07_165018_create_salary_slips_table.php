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
        Schema::connection('mysql_employees')->create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->timestamps();

            // FK ke employees
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');

            // FK ke payrolls
            $table->foreign('payroll_id')
                ->references('id')
                ->on('payrolls')
                ->onDelete('cascade');

            // FK ke salary_components
            $table->foreign('salary_component_id')
                ->references('id')
                ->on('salary_components')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('salary_slips');
    }
};
