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
            $table->string('created_by',200)->nullable()->change();
            $table->string('updated_by',200)->nullable()->default('')->change();
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->table('Overtimes', function (Blueprint $table) {
            $table->dropColumn(['created_by','updated_by'])->change();
        });
    }
};
