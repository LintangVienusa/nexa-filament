<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $table = 'AppVersion';

    protected $fillable = [
        'platform',
        'min_build',
        'latest_build',
        'version',
        'store_url',
        'update_message',
    ];

    protected $casts = [
        'min_build' => 'integer',
        'latest_build' => 'integer',
    ];
}
