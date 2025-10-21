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
        'movement_id',
        'asset_transaction_id',
        'asset_id',
        'inventory_id',
        'movement_id',
        'movementDate',
        'movementType',
        'placement_type',
        'PIC',
        'asset_qty_now',
        'request_asset_qty',
        'notes',
        'ba_number',
        'ba_description',
        'recipient',
        'sender',
        'file_path',
        'location',
        'province_code',
        'regency_code',
        'village_code',
        'status',
        'deployment_date',
        'return_date',
        'returned_by',
        'received_by',
        'created_by',
        'created_at',
        'approved_by',
        'approved_at',
        'updated_at',
        'handover_by',
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
