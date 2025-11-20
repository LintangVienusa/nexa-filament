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
        Schema::connection('mysql_inventory')->table('HomeConnect', function (Blueprint $table) {
            
            $table->string('id_pelanggan')->nullable()->after('bast_id');
            $table->string('name_pelanggan')->nullable()->after('id_pelanggan');
            $table->string('foto_label_id_plg')->nullable()->after('foto_depan_rumah');
            $table->string('foto_qr')->nullable()->after('foto_label_id_plg');
        });  
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::connection('mysql_inventory')->table('HomeConnect', function (Blueprint $table) {
            $table->dropColumn(['merk_ont','id_pelanggan', 'name_pelanggan',  'foto_label_id_plg','foto_qr']);
        });
    }
};
