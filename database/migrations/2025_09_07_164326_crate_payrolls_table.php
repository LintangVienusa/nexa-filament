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
        Schema::connection('mysql_employees')->create('Payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('periode');
            $table->boolean('status')->comment('2=paid, 1=approve, 0=draft')->default(0);
            $table->integer('number_of_employees');
            $table->date('start_date');
            $table->date('cutoff_date');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->integer('salary_slips_id')->default(0);
            $table->integer('salary_slips_created')->default(0);
            $table->integer('salary_slips_approved')->default(0);
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // FK ke employees
            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('Employees')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('Payrolls');
    }
};
