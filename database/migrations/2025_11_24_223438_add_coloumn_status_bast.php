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
        Schema::connection('mysql_inventory')->table('PoleDetail', function (Blueprint $table) {
            $table->enum('status', ['submit', 'pending','approved', 'rejected'])
                  ->default('submit')->after('progress_percentage');
            $table->text('approval_by')->nullable()->after('status');
            $table->date('approval_at')->nullable()->after('approval_by');
        });

        Schema::connection('mysql_inventory')->table('ODCDetail', function (Blueprint $table) {
            $table->enum('status', ['submit','pending', 'approved', 'rejected'])
                  ->default('submit')->after('progress_percentage');
            $table->text('approval_by')->nullable()->after('status');
            $table->date('approval_at')->nullable()->after('approval_by');
        });

        Schema::connection('mysql_inventory')->table('ODPDetail', function (Blueprint $table) {
            $table->enum('status', ['submit','pending', 'approved', 'rejected'])
                  ->default('submit')->after('progress_percentage');
            $table->text('approval_by')->nullable()->after('status');
            $table->date('approval_at')->nullable()->after('approval_by');
        });

        Schema::connection('mysql_inventory')->table('FeederDetail', function (Blueprint $table) {
            $table->enum('status', ['submit','pending', 'approved', 'rejected'])
                  ->default('submit')->after('progress_percentage');
            $table->text('approval_by')->nullable()->after('status');
            $table->date('approval_at')->nullable()->after('approval_by');
        });
        Schema::connection('mysql_inventory')->table('HomeConnect', function (Blueprint $table) {
            $table->enum('status', ['submit','pending', 'approved', 'rejected'])
                  ->default('submit')->after('progress_percentage');
            $table->text('approval_by')->nullable()->after('status');
            $table->date('approval_at')->nullable()->after('approval_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->table('PoleDetail', fn (Blueprint $table) => $table->dropColumns(['status','approval_by', 'approval_at']));
        Schema::connection('mysql_inventory')->table('ODCDetail', fn (Blueprint $table) => $table->dropColumns(['status','approval_by', 'approval_at']));
        Schema::connection('mysql_inventory')->table('ODPDetail', fn (Blueprint $table) => $table->dropColumns(['status','approval_by', 'approval_at']));
        Schema::connection('mysql_inventory')->table('FeederDetail', fn (Blueprint $table) => $table->dropColumns(['status','approval_by', 'approval_at']));
        Schema::connection('mysql_inventory')->table('HomeConnect', fn (Blueprint $table) => $table->dropColumns(['status','approval_by', 'approval_at']));
    }
};
