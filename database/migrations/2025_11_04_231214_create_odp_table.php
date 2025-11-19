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
        Schema::connection('mysql_inventory')->create('ODPDetail', function (Blueprint $table) {
            $table->id();
            $table->string('bast_id');
            $table->string('site');
            $table->foreign(['bast_id', 'site'])
                    ->references(['bast_id', 'site'])
                    ->on('BastProject')
                    ->onDelete('cascade');
            $table->string('odc_name')->constrained('ODCDetail')->onDelete('cascade'); 
            $table->string('instalasi')->nullable(); 
            $table->string('odp_terbuka')->nullable();
            $table->string('odp_tertutup')->nullable();
            $table->string('power_optic_odc')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('odp_id')->nullable();  
            $table->string('odp_name')->nullable();  
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            
            $table->unique(['id', 'bast_id']);
            $table->unique(['bast_id', 'odc_name', 'odp_name']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('ODPDetail');
    }
};
