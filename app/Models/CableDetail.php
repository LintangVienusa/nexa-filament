<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CableDetail extends Model
{
    use HasFactory;
      
    protected $connection = 'mysql_inventory';

    protected $table = 'CableDetail'; 

    protected $fillable = [
        'bast_id',
        'pole_sn',
        'pulling_cable',
        'instalasi',
        'notes',
        'progress_percentage',
        'created_by',
        'updated_by',
    ];

    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id');
    }
}
