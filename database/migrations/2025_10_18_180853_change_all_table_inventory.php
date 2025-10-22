<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::connection('mysql_employees')->table('Attendances', function (Blueprint $table) {
            DB::connection('mysql_employees')->table('Attendances')->whereNull('check_in_evidence')->update(['check_in_evidence' => '']);
            $table->longText('check_in_evidence')->nullable(false)->change();
            DB::connection('mysql_employees')->table('Attendances')->whereNull('check_out_evidence')->update(['check_out_evidence' => '']);
            $table->longText('check_out_evidence')->nullable(false)->change();
        });

        Schema::connection('mysql_inventory')->table('AssetTransactions', function (Blueprint $table) {
            if (!Schema::connection('mysql_inventory')->hasColumn('AssetTransactions', 'sender_custom')) {
                $table->string('sender_custom')->after('sender_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_employees')->table('Attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_evidence','check_out_evidence'])->change();
        });
        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->dropUnique(['asset_id', 'asset_transaction_id']);
            $table->dropColumn('asset_transaction_id');
            $table->unique('asset_id');
            $table->dropColumn(['province_code','regency_code','village_code']);
        });

         Schema::connection('mysql_inventory')->table('Assets', function (Blueprint $table) {
            $table->dropColumn(['asset_condition','notes']);
        });

         Schema::connection('mysql_inventory')->table('AssetTransactions', function (Blueprint $table) {
            $table->dropColumn(['sender_custom']);
        });
    }
};
