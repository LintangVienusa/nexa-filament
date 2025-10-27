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
        Schema::connection('mysql_inventory')->create('BastDocumentation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('serial_number');
            $table->string('bast_number');
            $table->string('bast_photo')->nullable();
            $table->string('digging_photo')->nullable();
            $table->string('instalasi_photo')->nullable();
            $table->string('coran_photo')->nullable();
            $table->string('tiang_berdiri_photo')->nullable();
            $table->string('label_tiang_photo')->nullable();
            $table->string('aksesoris_photo')->nullable();
            $table->enum('status', ['In Progress','Completed','Pending'])->nullable();
            $table->string('tiang_photo')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('PIC')->nullable();
            $table->string('kontraktor')->nullable();
            $table->timestamps();

            
            $table->unique(['project_id', 'serial_number', 'bast_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::connection('mysql_inventory')->dropIfExists('BastDocumentation');
    }
};
