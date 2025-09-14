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
        Schema::connection('mysql_employees')->create('Timesheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->decimal('working_hours', 8, 2);
            $table->unsignedBigInteger('job_id');

            $table->foreign('attendance_id')
                  ->references('id')
                  ->on('Attendances')
                  ->onDelete('cascade');

            $table->foreign('job_id')
                  ->references('id')
                  ->on('Jobs')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('Timesheets');
    }
};
