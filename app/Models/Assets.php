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
        'serialNumber',
        'category_id',
        'description',
        'status',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryAsset::class, 'category_id');
    }

    

    protected static function booted()
    {
        static::created(function ($asset) {
            $category = $asset->category()->first();
            if ($category) {
                $nextId = $asset->id;
                $formattedId = str_pad($nextId, 4, '0', STR_PAD_LEFT);
                $asset->item_code = $category->category_code . '-' . $formattedId;
                $asset->saveQuietly(); 
            }
        });
    }
}
