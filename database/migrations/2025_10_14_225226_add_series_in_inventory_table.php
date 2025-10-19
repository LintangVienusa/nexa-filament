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
        Schema::connection('mysql_inventory')->table('CategoryAsset', function (Blueprint $table) {
            $table->string('category_id')->unique()->nullable(false)->after('id');
        });

        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->string('asset_movement_id')->unique()->nullable(false)->after('id');
        });

         Schema::connection('mysql_inventory')->table('AssetRelease', function (Blueprint $table) {
            $table->string('asset_release_id')->unique()->nullable(false)->after('id');
        });

         Schema::connection('mysql_inventory')->table('InventoryAsset', function (Blueprint $table) {
            $table->string('inventory_asset_id')->unique()->nullable(false)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::connection('mysql_inventory')->table('CategoryAsset', function (Blueprint $table) {
            $table->dropColumn(['category_id']);
        });
        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->dropColumn(['asset_movement_id']);
        });
        Schema::connection('mysql_inventory')->table('AssetRelease', function (Blueprint $table) {
            $table->dropColumn(['asset_release_id']);
        });
        Schema::connection('mysql_inventory')->table('InventoryAsset', function (Blueprint $table) {
            $table->dropColumn(['inventory_asset_id']);
        });
    }
};
