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
        Schema::connection('mysql_inventory')->create('InstalasiHome', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('sn_ont')->nullable();
            $table->string('data_excel')->nullable();
            $table->string('label_odp_photo')->nullable();
            $table->string('ukur_odp_photo')->nullable();
            $table->string('penarikan_outdoor_photo')->nullable();
            $table->string('aksesoris_ikr_1_photo')->nullable();
            $table->string('aksesoris_ikr_2_photo')->nullable();
            $table->string('rumah_photo')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('pic')->nullable();
            $table->string('teknisi')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'sn_ont']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->dropIfExists('InstalasiHome');
    }
};
