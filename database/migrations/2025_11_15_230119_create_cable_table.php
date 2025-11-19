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
        Schema::connection('mysql_inventory')->create('CableDetail', function (Blueprint $table) {
            $table->id();
            
            $table->string('bast_id');
            $table->string('site');
            $table->foreign(['bast_id', 'site'])
                    ->references(['bast_id', 'site'])
                    ->on('BastProject')
                    ->onDelete('cascade');
            $table->string('pole_sn')->nullable();
            $table->string('pulling_cable')->nullable();
            $table->string('instalasi')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);   
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('CableDetail');
    }
};
