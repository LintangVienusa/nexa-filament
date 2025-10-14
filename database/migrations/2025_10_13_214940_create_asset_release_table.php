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
        Schema::connection('mysql_inventory')->create('AssetRelease', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('movement_id');
            $table->unsignedBigInteger('PIC')->nullable();
            $table->integer('asset_qty_now')->default(0);
            $table->integer('request_asset_qty')->default(0);
            $table->text('notes')->nullable();
            $table->string('ba_number')->nullable();
            $table->text('ba_description')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('status')->default(0)->comment('0=submit,1=pending,2=approved,3=rejected');
            $table->string('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            
            $table->foreign('asset_id')
                ->references('id')->on('Assets')
                ->onDelete('cascade');
            $table->foreign('inventory_id')
                ->references('id')->on('InventoryAsset')
                ->onDelete('cascade');
            $table->foreign('movement_id')
                ->references('id')->on('AssetMovement')
                ->onDelete('cascade');
        });
    }

    
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('AssetRelease');
    }
};
