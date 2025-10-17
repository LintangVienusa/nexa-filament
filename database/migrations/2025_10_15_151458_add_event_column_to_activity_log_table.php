<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventColumnToActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection('mysql_activitylog')->table('Activity_Log', function (Blueprint $table) {
            $table->string('menu')->nullable()->after('log_name');
            $table->string('event')->nullable()->after('subject_type');
            $table->string('email')->nullable()->after('causer_id');
        });
    }

    public function down()
    {
        Schema::connection('mysql_activitylog')->table('Activity_Log', function (Blueprint $table) {
            $table->dropColumn(['menu','event','email']);
        });
    }
}
