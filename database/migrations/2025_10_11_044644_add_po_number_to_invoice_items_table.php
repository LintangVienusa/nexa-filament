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
        Schema::connection('mysql')->table('InvoiceItems', function (Blueprint $table) {
            
            $table->string('po_number')->nullable()->after('id');
            $table->string('po_description')->nullable()->after('po_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql')->table('InvoiceItems', function (Blueprint $table) {
            $table->string('po_number');
            $table->string('po_description');
        });
    }
};
