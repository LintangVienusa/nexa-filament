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
        'site',
        'bast_id',
        'po_number',
        'province_name',
        'regency_name',
        'village_name',
        'station_name',
        'project_name',
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
        'list_homeconnect',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'pass' => 'HOMEPASS',
        'status' => 'not started',
        'progress_percentage' => 0,
    ];

    public function poles()
    {
        return $this->hasMany(PoleDetail::class, 'bast_id', 'bast_id'); 
    }

    public function ODCDetail()
    {
        return $this->hasMany(ODCDetail::class, 'bast_id', 'bast_id');
    }

    public function ODPDetail()
    {
        return $this->hasMany(ODPDetail::class, 'bast_id', 'bast_id');
    }

}
