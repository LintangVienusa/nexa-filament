<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeederDetail extends Model
{
    use HasFactory;

    protected $connection = 'mysql_inventory';
    protected $table = 'FeederDetail';

    protected $fillable = [
        'site',
        'id',
        'bast_id',
        'feeder_name',
        'pulling_cable',
        'instalasi',
        'notes',
        'progress_percentage',
        'staus',
        'aproval_by',
        'aproval_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id', 'bast_id');
    }

    public function getProgressLabelAttribute()
    {
        return $this->progress_percentage . '%';
    }
}
