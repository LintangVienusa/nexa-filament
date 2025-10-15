<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventColumnToActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection('mysql_activitylog')->table('activity_log', function (Blueprint $table) {
            $table->string('event')->nullable()->after('subject_type');
        });
    }

    public function down()
    {
        Schema::connection('mysql_activitylog')->table('activity_log', function (Blueprint $table) {
            $table->dropColumn('event');
        });
    }
}
