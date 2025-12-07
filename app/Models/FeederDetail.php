<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeederDetail extends Model
{
    use HasFactory;

    // koneksi ke database mysql_inventory
    protected $connection = 'mysql_inventory';

    // nama tabel
    protected $table = 'FeederDetail';

    // kolom yang bisa diisi mass-assignment
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

    // relasi ke BastProject
    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id', 'bast_id');
    }

    // accessor contoh (jika ingin tampilkan progress dalam format persen)
    public function getProgressLabelAttribute()
    {
        return $this->progress_percentage . '%';
    }
}
