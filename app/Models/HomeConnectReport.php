<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeConnectReport extends Model
{
    //
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
}
