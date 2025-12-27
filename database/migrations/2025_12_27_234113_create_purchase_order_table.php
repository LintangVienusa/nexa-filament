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
        if (!Schema::connection('mysql_inventory')->hasTable('PurchaseOrder')) {
            Schema::connection('mysql_inventory')->create('PurchaseOrder', function (Blueprint $table) {
                $table->id();
                $table->string('po_number')->unique();
                $table->date('order_date');
                $table->string('po_issuer');
                $table->string('site_name');
                $table->string('kecamatan');
                $table->string('job_type');
                $table->integer('total_target');
                $table->date('project_start_date');
                $table->date('project_end_date');
                $table->string('vendor');
                $table->string('pic_name');
                $table->string('pic_mobile_no');
                $table->string('pic_email');
                $table->string('po_status')->default('Draft');
                $table->string('payment_terms');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PurchaseOrder');
    }
};
