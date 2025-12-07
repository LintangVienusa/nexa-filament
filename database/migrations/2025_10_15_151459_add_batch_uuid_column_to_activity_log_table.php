<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchUuidColumnToActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection('mysql_activitylog')->table('Activity_Log', function (Blueprint $table) {
            $table->uuid('batch_uuid')->nullable()->after('properties');
        });
    }

    public function down()
    {
        Schema::connection('mysql_activitylog')->table('Activity_Log', function (Blueprint $table) {
            $table->dropColumn('batch_uuid');
        });
    }
}
