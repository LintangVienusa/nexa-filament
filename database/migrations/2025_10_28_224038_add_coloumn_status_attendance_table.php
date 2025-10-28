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
        // Schema::connection('mysql_employees')->table('Attendances', function (Blueprint $table) {
        //     $table->integer('status')->default(0)->comment('0=success,1=alpha')->after('check_out_longitude');
        //     $table->LongText('notes')->nullable()->after('status');
        // });

        Schema::connection('mysql_employees')->table('Overtimes', function (Blueprint $table) {
            $table->text('ba_file')->nullable(0)->after('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //  Schema::connection('mysql_employees')->table('Attendances', function (Blueprint $table) {
        //     $table->dropColumn(['status', 'info']);
        // });

        Schema::connection('mysql_employees')->table('Overtimes', function (Blueprint $table) {
            $table->dropColumn(['ba_file']);
        });
    }
};
