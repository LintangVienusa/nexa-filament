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
            $table->string('employee_id', 20);
            $table->string('periode');
            $table->boolean('status')->comment('1=closed, 0=open');
            $table->integer('number_of_employees');
            $table->date('start_date');
            $table->date('cutoff_date');
            $table->integer('salary_slips_created')->default(0);
            $table->integer('salary_slips_approved')->default(0);
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();

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
