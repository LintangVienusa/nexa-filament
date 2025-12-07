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
        Schema::connection('mysql_inventory')->table('AssetTransactionItems', function (Blueprint $table) {
            $table->text('file_asset')->nullable()->after('movement_id');
            $table->string('info_ket',50)->nullable()->after('file_asset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->table('AssetTransactionItems', fn (Blueprint $table) => $table->dropColumns(['file_asset','info_ket']));
    }
};
