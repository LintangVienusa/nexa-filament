<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RBSDetail extends Model
{
       
        use HasFactory;

        protected $connection = 'mysql_inventory';

        protected $table = 'RBSDetail';

        protected $fillable = [
            'bast_id',
            'rbs_name',
            'hasil_otdr',
            'penyambungan_core',
            'latitude',
            'longitude',
            'notes',
            'progress_percentage',
            'created_by',
            'updated_by',
        ];

        public function bastProject()
        {
            return $this->belongsTo(BastProject::class, 'bast_id', 'bast_id');
        }
    
}
