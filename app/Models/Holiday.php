<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $connection = 'mysql_employees';
    protected $table = 'Holiday';

    protected $fillable = [
        'holiday_date',
        'year',
        'holiday_name',
        'is_national_holiday',
        'source',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'year' => 'date', // sesuai migration kamu (walaupun seharusnya year)
        'is_national_holiday' => 'boolean',
    ];
}
