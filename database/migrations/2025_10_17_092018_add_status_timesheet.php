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
         Schema::connection('mysql_employees')->table('Timesheets', function (Blueprint $table) {
            $table->integer('status')->default(0)->comment('0=on progress,1=pending,2=done,3=cancel')->after('job_duration');
            $table->LongText('notes')->nullable()->after('status');
            $table->integer('job_duration')->default(0)->change();
            $table->string('updated_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_employees')->table('Timesheets', function (Blueprint $table) {
            $table->dropColumn(['status','notes']);
        });
    }
};
