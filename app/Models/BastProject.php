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
        'pass',
        'status',
        'progress_percentage',
        'notes',
        'bast_date',
        'info_pole',
        'info_rbs',
        'info_feeder',
        'info_odc',
        'info_odp',
        'info_homeconnect',
        'list_pole',
        'list_feeder_odc_odp',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'pass' => 'HOMEPASS',
        'status' => 'not started',
        'progress_percentage' => 0,
    ];

}
