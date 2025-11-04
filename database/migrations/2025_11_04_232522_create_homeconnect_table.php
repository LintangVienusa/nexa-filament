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
        Schema::connection('mysql_inventory')->create('HomeConnect', function (Blueprint $table) {
            $table->id();
            $table->string('bast_id');
            $table->foreign('bast_id')->references('bast_id')->on('BastProject')->onDelete('cascade');
            $table->string('sn_ont')->nullable();
            $table->string('province_name')->nullable();
            $table->string('regency_name')->nullable();
            $table->string('village_name')->nullable();    
            $table->string('import_excel')->nullable();
            $table->string('foto_label_odp')->nullable();
            $table->foreignId('odp_name')->constrained('ODPDetail')->onDelete('cascade');  
            $table->string('foto_hasil_ukur_odp')->nullable();
            $table->string('foto_penarikan_outdoor')->nullable();
            $table->string('foto_aksesoris_ikr')->nullable();
            $table->string('foto_sn_ont')->nullable();
            $table->string('foto_depan_rumah')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();    
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();      
            $table->timestamps();

            
            $table->unique(['id', 'bast_id']);
            $table->index('sn_ont');
            $table->index(['province_name', 'regency_name', 'village_name']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('HomeConnect');
    }
};
