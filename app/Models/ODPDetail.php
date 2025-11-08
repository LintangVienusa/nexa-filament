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
        'bast_id',
        'odc_id',
        'instalasi',
        'odc_name',
        'odp_terbuka',
        'odp_tertutup',
        'hasil_ukur_opm',
        'labeling_odp',
        'latitude',
        'longitude',
        'odp_id',
        'odp_name',
        'notes',
        'progress_percentage',
        'created_by',
        'updated_by',
    ];

    /** 🔗 Relasi ke BastProject */
    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id', 'id');
    }

    /** 🔗 Relasi ke ODCDetail */
    public function odcDetail()
    {
        return $this->belongsTo(ODCDetail::class, 'odc_id', 'id');
    }

    /** 🧮 Accessor tambahan */
    public function getProgressLabelAttribute()
    {
        return $this->progress_percentage . '%';
    }

    /** 🌍 Accessor koordinat */
    public function getCoordinateAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }
        return null;
    }
}
