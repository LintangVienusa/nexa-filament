<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAsset extends Model
{
    protected $connection = 'mysql_inventory';

    protected $table = 'CategoryAsset';

    protected $fillable = [
        'category_code',
        'category_name',
        'description',
        'created_at',
        'updated_at',
    ];

}
