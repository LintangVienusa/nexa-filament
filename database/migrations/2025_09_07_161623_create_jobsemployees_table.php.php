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
         Schema::connection('mysql_employees')->create('Jobs', function (Blueprint $table) {
            $table->id(); // id INT auto increment primary key
            $table->text('job_name');
            $table->decimal('job_duration', 8, 2); // sesuai kebutuhan
            $table->timestamps(); // optional created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->dropIfExists('Jobs');
    }
};
