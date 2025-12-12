<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAsset extends Model
{
    protected $connection = 'mysql_inventory';

    protected $table = 'CategoryAsset';

    protected $fillable = [
        'category_id',
        'category_code',
        'category_name',
        'description',
        'info_sn',
        'created_at',
        'updated_at',
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            if (!$category->category_id) {
                // Ambil ID terakhir
                $last = self::latest('category_id')->first();

                if ($last) {
                    // Ambil angka terakhir dan tambah 1
                    $number = (int) substr($last->category_id, 2) + 1;
                } else {
                    $number = 1;
                }

                // Format jadi CA0001, CA0002, dst.
                
                $category->created_by = $category->created_by ?? auth()->user()?->email;
                $category->category_id = 'CA' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

}
