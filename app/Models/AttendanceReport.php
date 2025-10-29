<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceReport extends Model
{
    protected $connection = 'mysql_employees';
    protected $table = 'Attendances';
}
