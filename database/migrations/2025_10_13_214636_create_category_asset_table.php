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
        Schema::connection('mysql_inventory')->create('CategoryAsset', function (Blueprint $table) {
            $table->id();
            $table->string('category_code')->unique(); // misal: ELEC, FURN
            $table->string('category_name');           // misal: Electronics, Furniture
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::connection('mysql_inventory')->dropIfExists('CategoryAsset');
    }
};
