<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ODPDetail extends Model
{
     use HasFactory;

      protected $connection = 'mysql_inventory';
    protected $table = 'ODPDetail';

    protected $fillable = [
        'site',
        'bast_id',
        'odc_id',
        'odc_name',
        'instalasi',
        'odp_terbuka',
        'odp_tertutup',
        'power_optic_odc',
        'latitude',
        'longitude',
        'odp_id',
        'odp_name',
        'notes',
        'progress_percentage',
        'created_by',
        'updated_by',
    ];

    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id', 'id');
    }

    public function odcDetail()
    {
        return $this->belongsTo(ODCDetail::class, 'odc_id', 'id');
    }

    public function getProgressLabelAttribute()
    {
        return $this->progress_percentage . '%';
    }

    public function getCoordinateAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }
        return null;
    }
}
