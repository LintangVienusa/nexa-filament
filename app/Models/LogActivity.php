<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogActivity extends Model
{
    use HasFactory;

     protected $connection = 'mysql_activitylog';
    protected $table = 'activity_log';

    protected $fillable = [
        'email',
        'log_name',
        'menu',
        'description',
        'record_id',
        'subject_type',
        'event',
        'subject_id',
        'causer_type',
        'causer_id',
        'email',
        'properties',
        'properties',
        'batch_uuid',
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
