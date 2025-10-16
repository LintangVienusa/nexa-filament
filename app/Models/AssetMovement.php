<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Assets;
use App\Models\InventoryAsset;

class AssetMovement extends Model
{
    use HasFactory;

    
    protected $connection = 'mysql_inventory'; 
    protected $table = 'AssetMovement';

    protected $fillable = [
        'asset_id',
        'inventory_id',
        'movement_id',
        'PIC',
        'asset_qty_now',
        'request_asset_qty',
        'notes',
        'ba_number',
        'ba_description',
        'file_path',
        'status',
        'created_by',
        'created_at',
        'approved_by',
        'approved_at',
        'updated_at',
        'handover_by',
        'received_by',
        'evidence_return',
    ];

    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_id');
    }

    public function inventory()
    {
        return $this->belongsTo(InventoryAsset::class, 'inventory_id');
    }
}
