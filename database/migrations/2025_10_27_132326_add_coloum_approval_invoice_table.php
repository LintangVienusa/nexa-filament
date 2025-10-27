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
        Schema::table('Invoices', function (Blueprint $table) {
            $table->tinyInteger('approval_1')->default(0)
                  ->comment('0=pending,1=approved,2=rejected')
                  ->after('status');
             $table->string('approval_1_by')->nullable()->after('approval_1');
            $table->timestamp('approved_1_at')->nullable()->after('approval_1_by');
            $table->tinyInteger('approval_2')->default(0)
                  ->comment('0=pending,1=approved,2=rejected')
                  ->after('approved_1_at');
            $table->string('approval_2_by')->nullable()->after('approval_2');

            $table->timestamp('approved_2_at')->nullable()->after('approval_2_by');
        });  
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Invoices', function (Blueprint $table) {
            $table->dropColumn([
                'approval_1',
                'approval_2',
                'approval_1_by',
                'approval_2_by',
                'approved_1_at',
                'approved_2_at'
            ]);
        });
    }
};
