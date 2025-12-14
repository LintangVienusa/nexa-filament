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
        Schema::connection('mysql_employees')->create('Holiday', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('year',10);
            $table->string('holiday_name');
            $table->boolean('is_national_holiday')->default(true);
            $table->string('source')->nullable(); 
            $table->timestamps();

            $table->index('holiday_date');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::connection('mysql_employees')->dropIfExists('Holiday');
    }
};
