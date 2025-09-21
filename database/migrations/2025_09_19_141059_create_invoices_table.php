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
        Schema::create('Invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained('Customers')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->integer('subtotal')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('amount')->default(0);
            $table->string('status')->default('0')->comment('0 = draft, 1 = approved');;
            $table->string('create_by')->nullable();
            $table->timestamps();
            $table->string('approval_by')->nullable();
            $table->timestamp('approval_at')->nullable();
            $table->timestamp('updated_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Invoices');
    }
};
