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
        


        Schema::connection('mysql_inventory')->table('BastProject', function (Blueprint $table) {
            
            $table->string('po_number')->after('bast_id');
            $table->unique(['bast_id', 'po_number'], 'bastproject_bast_id_po_unique');
        });

         Schema::connection('mysql_inventory')->table('HomeConnect', function (Blueprint $table) {
            
            $table->string('po_number')->nullable()->after('bast_id');
            $table->index('po_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_inventory')->table('ODCDetail', function (Blueprint $table) {
            $table->dropColumn('po_number');
                 $table->dropUnique('bastproject_bast_id_po_unique');

            $table->unique(['bast_id', 'site'], 'bastproject_bast_id_site_unique');
       
        });

         Schema::connection('mysql_inventory')->table('HomeConnect', function (Blueprint $table) {
            $table->dropIndex(['po_number']); 
            $table->dropColumn('po_number');  
        });
    }
};
