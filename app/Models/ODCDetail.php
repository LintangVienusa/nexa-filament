<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ODCDetail extends Model
{
    use HasFactory;

    // koneksi database
    protected $connection = 'mysql_inventory';

    // nama tabel
    protected $table = 'ODCDetail';

    // kolom yang bisa diisi mass-assignment
    protected $fillable = [
        'bast_id',
        'feeder_name',
        'instalasi',
        'odc_terbuka',
        'odc_tertutup',
        'hasil_ukur_opm',
        'labeling_odc',
        'latitude',
        'longitude',
        'odc_id',
        'odc_name',
        'notes',
        'progress_percentage',
        'created_by',
        'updated_by',
    ];

    // relasi ke BastProject
    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id', 'bast_id');
    }

    // relasi ke FeederDetail
    public function feederDetail()
    {
        return $this->belongsTo(FeederDetail::class, 'feeder_id', 'id');
    }

    // accessor tambahan (misal progress label)
    public function getProgressLabelAttribute()
    {
        return $this->progress_percentage . '%';
    }

    // jika ingin format koordinat jadi string gabungan
    public function getCoordinateAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }
        return null;
    }
}
