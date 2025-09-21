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
            $table->foreignId('attendance_id')->constrained('Attendances')->cascadeOnDelete();
            $table->text('job_description')->nullable();
            $table->decimal('job_duration', 5, 2)->nullable();
            $table->timestamps();
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
