<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    use HasFactory;

    protected $connection = 'mysql_employees';
    protected $table = 'Jobs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'timesheet_id',
        'job_description',
        'job_duration',
    ];

    public function overtimes()
    {
        return $this->hasMany(Overtime::class, 'job_id', 'id');
    }

    public function timesheet(): BelongsTo
    {
        return  $this->belongsTo(Timesheet::class, 'timesheet_id', 'id');
    }

}
