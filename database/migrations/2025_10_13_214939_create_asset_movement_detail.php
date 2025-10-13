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
        Schema::connection('mysql_inventory')->create('AssetMovement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('Assets')->onDelete('cascade');
            $table->enum('movementType', ['IN', 'OUT']);
            $table->date('movementDate');
            $table->string('serialNumber')->unique(); 
            $table->string('evidencePath')->nullable(); 
            $table->string('PIC');
            $table->text('notes')->nullable();
            $table->string('recipient')->nullable()->comment('OUT'); 
            $table->string('sender')->nullable()->comment('IN');
            $table->integer('status')->default(0)->comment('0=submit,1=pending,2=approved,3=rejected');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('AssetMovement');
    }
};
