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
        Schema::connection('mysql_activitylog')->table('Activity_Log',  function (Blueprint $table) {
            $table->string('record_id')->nullable()->after('menu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_activitylog')->table('Activity_Log', fn (Blueprint $table) => $table->dropColumn(['record_id']));
        
    }
};
