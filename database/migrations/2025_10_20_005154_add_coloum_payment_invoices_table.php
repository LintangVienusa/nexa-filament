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
            
            $table->enum('keterangan', ['Full Payment', 'DP'])->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::connection('mysql')->table('Invoices', function (Blueprint $table) {
            $table->dropColumn(['keterangan']);
        });
    }
};
