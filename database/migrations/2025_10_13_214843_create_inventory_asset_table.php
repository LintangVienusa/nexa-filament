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
        Schema::connection('mysql_inventory')->create('InventoryAsset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoryAsset_id')->constrained('CategoryAsset')->onDelete('cascade');
            $table->text('total');
            $table->integer('inWarehouse')->nullable();
            $table->integer('outWarehouse')->nullable();
        });
    }

    
    public function down(): void
    {
        
        Schema::connection('mysql_inventory')->dropIfExists('inventory_asset');
    }
};
