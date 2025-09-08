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
        Schema::connection('mysql_employees')->create('Attendances', function (Blueprint $table) {
            $table->id(); // id INT auto increment primary key
            $table->unsignedBigInteger('employee_id'); // foreign key ke tbEmployees
            $table->date('attendance_date');
            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time');
            $table->decimal('working_hours', 8, 2);
            $table->string('check_in_evidence', 50);
            $table->string('check_out_evidence', 50);
            $table->float('check_in_latitude');
            $table->float('check_in_longitude');
            $table->float('check_out_latitude');
            $table->float('check_out_longitude');
            $table->string('created_by', 10);
            $table->dateTime('created_at');
            $table->string('updated_by', 10);
            $table->dateTime('updated_at');

            // Foreign key ke tbEmployees
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
         Schema::connection('mysql_employees')->dropIfExists('Attendances');
    }
};
