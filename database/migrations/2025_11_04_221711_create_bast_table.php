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
        Schema::connection('mysql_inventory')->create('BastProject', function (Blueprint $table) {
            $table->id();
            $table->string('bast_id')->unique();
            $table->string('province_name')->nullable();
            $table->string('regency_name')->nullable();
            $table->string('village_name')->nullable();
            $table->string('project_name');
            $table->string('site')->nullable();
            $table->string('PIC')->nullable();
            $table->string('technici')->nullable();
            $table->enum('status', ['not started', 'in progress','pending', 'completed'])->default('not started');
            $table->enum('pass', ['HOMEPASS', 'HOMECONNECT'])->default('HOMEPASS');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->text('notes')->nullable();
            $table->date('bast_date');
            $table->tinyInteger('info_pole')->default(0)->comment('0=NO,1=YES');
            $table->tinyInteger('info_rbs')->default(0)->comment('0=NO,1=YES');
            $table->tinyInteger('info_feeder')->default(0)->comment('0=NO,1=YES');
            $table->tinyInteger('info_odc')->default(0)->comment('0=NO,1=YES');
            $table->tinyInteger('info_odp')->default(0)->comment('0=NO,1=YES');
            $table->tinyInteger('info_homeconnect')->default(0)->comment('0=NO,1=YES');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('PIC');
            $table->index('technici');
            $table->index(['province_name', 'regency_name', 'village_name']);
            $table->index('site');
            $table->index('status');
            $table->index('pass');
            $table->index('bast_id');
            $table->index('info_pole');
            $table->index('info_rbs');
            $table->index('info_feeder');
            $table->index('info_odc');
            $table->index('info_odp');
            $table->index('info_homeconnect');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('BastProject');
    }
};
