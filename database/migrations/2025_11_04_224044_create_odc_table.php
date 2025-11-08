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
        Schema::connection('mysql_inventory')->create('ODCDetail', function (Blueprint $table) {
            $table->id();
            $table->string('bast_id');
            $table->foreign('bast_id')->references('bast_id')->on('BastProject')->onDelete('cascade');
            $table->string('instalasi')->nullable();
            $table->string('feeder_name');
            $table->string('odc_terbuka')->nullable();
            $table->string('odc_tertutup')->nullable();
            $table->string('hasil_ukur_opm')->nullable();
            $table->string('labeling_odc')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('odc_id')->nullable();
            $table->string('odc_name')->nullable(); 
            $table->text('notes')->nullable();    
            $table->unsignedTinyInteger('progress_percentage')->default(0); 
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();      
            $table->timestamps();

            
            $table->unique(['id', 'bast_id']);
            $table->unique(['bast_id', 'feeder_name', 'odc_name'],'odcdetail_unique_idx');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('ODCDetail');
    }
};
