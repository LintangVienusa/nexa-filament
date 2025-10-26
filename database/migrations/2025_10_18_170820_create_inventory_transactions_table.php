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
        Schema::connection('mysql_inventory')->create('AssetTransactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->enum('transaction_type', ['RELEASE', 'RECEIVE']);
            $table->string('PIC', 250)->nullable();
            $table->integer('asset_qty_now')->default(0);
            $table->integer('request_asset_qty')->default(0);
            $table->integer('receive_asset_qty')->default(0);
            $table->text('notes')->nullable();
            $table->enum('usage_type', ['ASSIGNED_TO_EMPLOYEE', 'DEPLOYED_FIELD', 'WAREHOUSE'])
                  ->default('WAREHOUSE');
            $table->enum('assigned_type', ['EMPLOYEE', 'DISTRIBUTOR', 'CONTRACTOR'])->nullable();
            $table->string('recipient_by', 150)->nullable();
            $table->string('sender_by', 150)->nullable();
            $table->string('sender_custom', 150)->nullable();
            $table->string('province_code')->nullable();
            $table->string('regency_code')->nullable();
            $table->string('village_code')->nullable();
            $table->string('ba_number')->nullable();
            $table->text('ba_description')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('status')->default(0)->comment('0=submit,1=pending,2=approved,3=rejected');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->timestamps();

            $table->index('inventory_id');
            $table->index('PIC');
            $table->index('recipient_by');
            $table->index('sender_by');
            $table->index('sender_custom');
            $table->index('transaction_type');
            $table->index('created_by');
            $table->index('assigned_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::connection('mysql_inventory')->dropIfExists('AssetTransactions');
    }
};
