<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MappingHomepass extends Model
{
    use HasFactory;
    protected $connection = 'mysql_inventory';

    protected $table = 'MappingHomepass';

    protected $fillable = [
        'province_name',
        'regency_name',
        'village_name',
        'station_name',
        'site',
        'pole',
        'feeder_name',
        'ODC',
        'ODP',
    ];
}
