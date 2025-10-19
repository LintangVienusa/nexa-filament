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
        

        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropUnique(['assetmovement_serialnumber_unique']);
            $table->unique(['asset_id', 'asset_transaction_id'], 'asset_id_asset_transaction_id_unique');
        });

         Schema::connection('mysql_inventory')->table('Assets', function (Blueprint $table) {
            $table->enum('asset_condition', ['GOOD', 'DAMAGED', 'REPAIR'])
                  ->default('GOOD')
                  ->after('description'); 
            $table->text('notes')->after('description'); 
        });
    }

    public function down(): void
    {
        
        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->dropUnique('asset_id_asset_transaction_id_unique');
            $table->unique('asset_id');
        });

         Schema::connection('mysql_inventory')->table('Assets', function (Blueprint $table) {
            $table->dropColumn(['asset_condition','notes']);
        });
    }
};
