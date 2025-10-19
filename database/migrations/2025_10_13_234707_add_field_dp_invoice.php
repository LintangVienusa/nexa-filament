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
        Schema::connection('mysql')->table('Invoices', function (Blueprint $table) {
            
            $table->decimal('dp_rate', 5, 2)->default(0)->after('tax_amount');
            $table->integer('dp')->default(0)->after('dp_rate');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql')->table('Invoices', function (Blueprint $table) {
            $table->dropColumn(['dp_rate','dp']);
        });
    }
};
