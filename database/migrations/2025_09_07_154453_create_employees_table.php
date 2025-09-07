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
        Schema::connection('mysql_employees')->create('employees', function (Blueprint $table) {
            $table->id('employee_id'); // Auto increment primary key
            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50);
            $table->string('gender', 15);
            $table->date('date_of_birth');
            $table->date('date_of_joining');
            $table->string('mobile_no', 15);
            $table->string('ktp_no', 50);
            $table->string('bpjs_kes_no', 50);
            $table->string('bpjs_tk_no', 50);
            $table->string('npwp_no', 50);
            $table->text('address');
            $table->string('religion', 10);
            $table->boolean('marital_status')->comment('1=is_married, 0=not_married');
            $table->string('job_title', 25);
            $table->unsignedBigInteger('org_id');
            $table->foreign('org_id')->references('id')->on('organizations')->onsDelete('cascade');
            $table->string('bank_account_name', 50);
            $table->string('bank_account_no', 30);
            $table->string('created_by', 10);
            $table->dateTime('created_at');
            $table->string('updated_by', 10);
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.  
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('employees');
    }
};
