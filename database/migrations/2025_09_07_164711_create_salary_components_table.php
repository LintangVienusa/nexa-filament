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
        Schema::connection('mysql_employees')->create('SalaryComponents', function (Blueprint $table) {
            $table->id();
            $table->string('component_name');
            $table->boolean('component_type')->comment('1=deduction, 0=allowance');
            $table->integer('permission_level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('SalaryComponents');
    }
};
