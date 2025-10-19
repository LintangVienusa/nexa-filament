<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetReleaseItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql_inventory';
    protected $table = 'AssetReleaseItems';

    protected $fillable = [
        'asset_release_id',
        'asset_id',
        'release_date', 
        'item_code',
        'merk',
        'type',
        'serial_number',
        'description',
        'inventory_id',
        'movement_id',
    ];

    public function release()
    {
        return $this->belongsTo(AssetRelease::class, 'asset_release_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function inventory()
    {
        return $this->belongsTo(InventoryAsset::class, 'inventory_id');
    }

    public function movement()
    {
        return $this->belongsTo(AssetMovement::class, 'movement_id');
    }
}
