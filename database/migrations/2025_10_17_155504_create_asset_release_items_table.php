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
        Schema::connection('mysql_inventory')->create('AssetReleaseItems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_release_id')
                ->constrained('AssetRelease')
                ->cascadeOnDelete(); 
            $table->foreignId('asset_id')
                ->constrained('Assets'); 
            $table->string('item_code')->nullable();
            $table->string('merk')->nullable();
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('inventory_id')
                ->nullable()
                ->constrained('InventoryAsset') 
                ->nullOnDelete();
             $table->foreignId('movement_id')
                ->nullable()
                ->constrained('AssetMovement') 
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['asset_release_id', 'asset_id']); 
            $table->index('asset_release_id');
            $table->index('asset_id');
            $table->index('inventory_id');
            $table->index('movement_id');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('AssetReleaseItems');
    }
};
