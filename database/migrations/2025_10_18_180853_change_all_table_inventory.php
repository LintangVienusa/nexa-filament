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
         Schema::connection('mysql_employees')->table('Attendances', function (Blueprint $table) {
            $table->longText('check_in_evidence')->change();
            $table->longText('check_out_evidence')->change();
        });

        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->dropForeign(['asset_transaction_id']);
            $table->dropForeign(['asset_id']);
            $table->unique(['asset_id', 'asset_transaction_id'], 'asset_id_asset_transaction_id');
            $table->string('province_code',50)->nullable()->after('location');
            $table->string('regency_code',50)->nullable()->after('province_code');
            $table->string('village_code',50)->nullable()->after('regency_code');
        });

         Schema::connection('mysql_inventory')->table('Assets', function (Blueprint $table) {
            $table->enum('asset_condition', ['GOOD', 'DAMAGED', 'REPAIR'])
                  ->default('GOOD')
                  ->after('description'); 
            $table->text('notes')->after('asset_condition')->nullable(); 
        });
        Schema::connection('mysql_inventory')->table('AssetTransactions', function (Blueprint $table) {
            $table->string('sender_custom')->after('sender_by')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_employees')->table('Attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_evidence','check_out_evidence'])->change();
        });
        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->dropUnique('asset_id_asset_transaction_id');
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
