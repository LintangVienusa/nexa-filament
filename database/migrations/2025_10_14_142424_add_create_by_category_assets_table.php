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
            $table->string('created_by')->nullable()->after('description');
            $table->string('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->table('CategoryAsset', function (Blueprint $table) {
            $table->dropColumn(['created_by','updated_by']);
        });
    }
};
