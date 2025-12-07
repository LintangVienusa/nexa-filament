<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryAsset extends Model
{
    use HasFactory;

    protected $connection = 'mysql_inventory';

    protected $table = 'InventoryAsset'; 
    protected $fillable = [
        'ategoryasset_id',
        'total',
        'inWarehouse',
        'outWarehouse',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryAsset::class, 'categoryasset_id','id');
    }

    protected static function booted()
    {
        static::creating(function ($asset) {
            if (!$asset->inventory_asset_id) {
                $last = self::latest('inventory_asset_id')->first();
                if ($last) {
                    $number = (int) substr($last->inventory_asset_id, 2) + 1;
                } else {
                    $number = 1;
                }
                $asset->inventory_asset_id = 'IA' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
