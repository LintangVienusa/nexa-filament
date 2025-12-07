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
        Schema::connection('mysql_employees')->create('Leaves', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->unsignedBigInteger('leave_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('leave_duration');
            $table->text('reason');
            $table->tinyInteger('status')->comment('0=submit,1=pending,2=approved,3=rejected');
            $table->string('created_by', 100)->nullable();
            $table->string('approved_by', 100)->nullable();
            $table->text('note_rejected')->nullable();
            $table->string('leave_evidence', 50)->nullable();
            $table->index('employee_id');
            $table->foreign('employee_id')->references('employee_id')->on('Employees')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::connection('mysql_employees')->dropIfExists('Leaves');
    }
};
