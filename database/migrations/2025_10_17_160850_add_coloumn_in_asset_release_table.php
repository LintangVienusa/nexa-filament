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
        Schema::connection('mysql_inventory')->table('AssetRelease', function (Blueprint $table) {
            $table->enum('usage_type', ['ASSIGNED_TO_EMPLOYEE','DEPLOYED_FIELD','WAREHOUSE'])->default('WAREHOUSE')->after('notes');
            $table->string('assigned_type')->nullable()->after('usage_type');
            $table->foreignId('assigned_id')->nullable()->after('assigned_type');
            $table->string('province_code')->nullable()->after('assigned_id');
            $table->string('regency_code')->nullable()->after('province_code');
            $table->string('village_code')->nullable()->after('regency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->table('AssetRelease', function (Blueprint $table) {
            $table->dropColumn(['usage_type','assigned_type','assigned_id','province_code','regency_code','village_code']);
        });
    }
};
