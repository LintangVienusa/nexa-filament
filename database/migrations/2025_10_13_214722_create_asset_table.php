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
        Schema::connection('mysql_inventory')->create('Assets', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique(); 
            $table->string('name'); 
            $table->string('type')->nullable(); 
            $table->string('serialNumber')->unique();                     
            $table->unsignedBigInteger('category_id'); 
            $table->text('description')->nullable();
            $table->integer('status')->default(0)->comment('0=IN_WAREHOUSE,1=OUT_DEPLOYED,2=LOST,3=DAMAGED,4=RETURNED');

            $table->foreign('category_id')
                ->references('id')->on('CategoryAsset')
                ->onDelete('cascade');
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('Assets');
    }
};
