<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assets extends Model
{
    use HasFactory;

    protected $connection = 'mysql_inventory'; 
    protected $table = 'Assets';

    protected $fillable = [
        'item_code',
        'name',
        'type',
        'merk',
        'serialNumber',
        'category_id',
        'description',
        'status',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryAsset::class, 'category_id','id');
    }

    

    protected static function booted()
    {
        static::created(function ($asset) {
            $category = $asset->category()->first();
            if ($category) {
                $nextId = $asset->id;
                $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                $asset->item_code = $category->category_code . $formattedId;
                $asset->saveQuietly(); 
            }

            $inventory = InventoryAsset::firstOrNew([
                'categoryasset_id' => $asset->category_id
            ]);

            if (is_null($inventory->categoryasset_id)) {
                
                $inventory->created_by = $asset->created_by ?? auth()->user()?->email;
            }

            $inventory->categoryasset_id = $asset->category_id;

            $inventory->total = ($inventory->total ?? 0) + 1;

            if ($asset->status === 0) {
                $inventory->inWarehouse = ($inventory->inWarehouse ?? 0) + 1;
            } else {
                $inventory->outWarehouse = ($inventory->outWarehouse ?? 0) + 1;
            }

            
            $inventory->updated_by = $asset->created_by ?? auth()->user()?->email;
            $inventory->save();

        });


    }
}
