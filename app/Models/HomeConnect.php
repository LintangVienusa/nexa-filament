<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HomeConnect extends Model
{
    use HasFactory;

    protected $connection = 'mysql_inventory';
    protected $table = 'HomeConnect';

    protected $fillable = [
        'site',
        'bast_id',
        'id_pelanggan',
        'name_pelanggan',
        'odp_name',
        'port_odp',
        'status_port',
        'merk_ont',
        'sn_ont',
        'province_name',
        'regency_name',
        'village_name',
        'import_excel',
        'foto_label_odp',
        'foto_hasil_ukur_odp',
        'foto_penarikan_outdoor',
        'foto_aksesoris_ikr',
        'foto_sn_ont',
        'foto_depan_rumah',
        'foto_label_id_plg',
        'foto_qr',
        'latitude',
        'longitude',
        'notes',
        'progress_percentage',
        'staus',
        'aproval_by',
        'aproval_at',
        'created_by',
        'updated_by',
    ];

    public function bastProject()
    {
        return $this->belongsTo(BastProject::class, 'bast_id', 'bast_id');
    }

    public function odpDetail()
    {
        return $this->belongsTo(ODPDetail::class, 'odp_name', 'id');
    }

    public function scopeByLocation($query, $province = null, $regency = null, $village = null)
    {
        if ($province) $query->where('province_name', $province);
        if ($regency) $query->where('regency_name', $regency);
        if ($village) $query->where('village_name', $village);
        return $query;
    }
}
