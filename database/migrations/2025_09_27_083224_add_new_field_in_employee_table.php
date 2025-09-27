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
        Schema::connection('mysql_employees')->table('Employees', function (Blueprint $table) {
            $table->string('name_in_bank_account', 100)->nullable()->after('bank_account_no');
            $table->unsignedInteger('number_of_children')->default(0)->after('marital_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->table('Employees', function (Blueprint $table) {
            $table->dropColumn(['name_in_bank_account', 'number_of_children']);
        });
    }
};
