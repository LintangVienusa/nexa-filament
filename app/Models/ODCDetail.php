<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ODCDetail extends Model
{
    use HasFactory;

    protected $connection = 'mysql_inventory';

    protected $table = 'ODCDetail';

    protected $fillable = [
        'bast_id',
        'feeder_name',
        'instalasi',
        'odc_terbuka',
        'odc_tertutup',
        'power_optic_olt',
        'flexing_conduit',
        'latitude',
        'longitude',
        'odc_id',
        'odc_name',
        'notes',
        'progress_percentage',
        'created_by',
        'updated_by',
    ];

    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id', 'bast_id');
    }

    public function feederDetail()
    {
        return $this->belongsTo(FeederDetail::class, 'feeder_id', 'id');
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
