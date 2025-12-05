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
        Schema::connection('mysql_inventory')->table('ODCDetail', function (Blueprint $table) {
            
            $table->text('closure')->nullable()->after('odc_tertutup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::connection('mysql_inventory')->table('ODCDetail', fn (Blueprint $table) => $table->dropColumns(['closure']));
    }
};
