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
        Schema::create('InvoiceItems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('Customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('Invoices')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('Services')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->string('description')->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();

            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('InvoiceItems');
    }
};
