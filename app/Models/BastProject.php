<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BastProject extends Model
{
    use HasFactory;

    
    protected $connection = 'mysql_inventory';

    protected $table = 'BastProject'; 

    protected $fillable = [
        'bast_id',
        'province_name',
        'regency_name',
        'village_name',
        'project_name',
        'site',
        'PIC',
        'email',
        'technici',
        'status',
        'progress_percentage',
        'notes',
        'bast_date',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'status' => 'not started',
        'progress_percentage' => 0,
    ];

}
