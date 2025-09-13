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
        Schema::connection('mysql_employees')->create('Overtimes', function (Blueprint $table) {
            $table->id(); // id INT auto increment primary key
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('attendance_id'); // foreign key ke tbAttandances
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('working_hours', 8, 2);
            $table->text('description');
            $table->unsignedBigInteger('job_id'); // foreign key ke tbJobs
            $table->string('created_by', 10)->nullable();
            $table->dateTime('created_at');
            $table->string('updated_by', 10)->nullable();
            $table->dateTime('updated_at');

            // Foreign key ke tbAttandances
            
            $table->index('attendance_id');
            $table->foreign('attendance_id')
                  ->references('id')
                  ->on('Attendances')
                  ->onDelete('cascade');

            // Foreign key ke tbJobs
            $table->index('job_id');
            $table->foreign('job_id')
                  ->references('id')
                  ->on('Jobs')
                  ->onDelete('cascade');
            $table->index('employee_id');
            $table->foreign('employee_id')->references('employee_id')->on('Employees')->onDelete('cascade');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('Overtimes');
    }
};
