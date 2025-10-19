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
        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->string('movement_id',)
                  ->after('id')
                  ->comment('Jenis penempatan aset')
                  ->index();
            $table->enum('placement_type', ['ASSIGNED_TO_EMPLOYEE', 'DEPLOYED_FIELD', 'WAREHOUSE'])
                  ->default('WAREHOUSE')
                  ->after('status')
                  ->comment('Jenis penempatan aset')
                  ->index();

            $table->string('assigned_to')
                  ->nullable()
                  ->after('placement_type')
                  ->comment('Karyawan yang bertanggung jawab jika placement_type = ASSIGNED_TO_EMPLOYEE')
                  ->index();

            $table->string('location')
                  ->nullable()
                  ->after('assigned_to')
                  ->comment('Lokasi penempatan aset');

            $table->date('deployment_date')
                  ->nullable()
                  ->after('location')
                  ->comment('Tanggal mulai penggunaan/pemasangan aset');

            $table->date('return_date')
                  ->nullable()
                  ->after('deployment_date')
                  ->comment('Tanggal aset dikembalikan, jika ada');

            $table->longText('return_evidence_path')
                  ->nullable()
                  ->after('return_date')
                  ->comment('Bukti pengembalian aset');

            $table->enum('asset_condition', ['GOOD', 'DAMAGED', 'LOST', 'REPAIRED'])
                  ->default('GOOD')
                  ->after('return_evidence_path')
                  ->comment('Kondisi terakhir aset')
                  ->index();
            $table->string('returned_by')
                ->nullable()
                ->after('asset_condition') // atau 'users' tergantung strukturmu
                ->index();

            $table->string('received_by')
                ->nullable()
                ->after('returned_by')
                ->index();

            $table->string('updated_by')
                  ->nullable()
                  ->after('approved_by')
                  ->comment('karyawan yang mengupdate data asset movement')
                  ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->table('AssetMovement', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn([
                'placement_type',
                'assigned_to',
                'location',
                'deployment_date',
                'return_date',
                'return_evidence_path',
                'asset_condition',
                'returned_by',
                'received_by',
                'updated_by',
            ]);
        });
    }
};
