<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $connection = 'mysql_employees';
    protected $table = 'SalaryComponents';
    protected $primaryKey = 'id';
    protected $fillable = [
        'component_name',
        'component_type',
        'permission_level',
    ];
}
