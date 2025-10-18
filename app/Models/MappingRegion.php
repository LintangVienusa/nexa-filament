<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappingWilayah extends Model
{
    use HasFactory;

    protected $connection = 'mysql_inventory';
    protected $table = 'MappingRegion';
    protected $fillable = [
        'province_name',
        'province_code',
        'district_name',
        'district_code',
        'station_name',
        'station_code',
        'village_name',
        'village_code',
    ];

    public $timestamps = true; 

}
