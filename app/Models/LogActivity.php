<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogActivity extends Model
{
    use HasFactory;

     protected $connection = 'mysql_activitylog';
    protected $table = 'Activity_Log';

    protected $fillable = [
        'email',
        'log_name',
        'description',
        'event',
        'properties',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
