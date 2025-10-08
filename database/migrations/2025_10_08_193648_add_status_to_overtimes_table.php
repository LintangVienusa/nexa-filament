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
        Schema::connection('mysql_employees')->table('Overtimes', function (Blueprint $table) {
            $table->integer('status')->default(0)->after('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->table('Overtimes', function (Blueprint $table) {
            $table->dropColumn(['status']);
        });
    }
};
