<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoleDetail extends Model
{
    use HasFactory;
      
    protected $connection = 'mysql_inventory';

    protected $table = 'PoleDetail'; 

    protected $fillable = [
        'site',
        'bast_id',
        'digging',
        'instalasi',
        'coran',
        'tiang_berdiri',
        'labeling_tiang',
        'aksesoris_tiang',
        'latitude',
        'longitude',
        'pole_sn',
        'notes',
        'progress_percentage',
        'staus',
        'aproval_by',
        'aproval_at',
        'created_by',
        'updated_by',
    ];

    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id');
    }
}
