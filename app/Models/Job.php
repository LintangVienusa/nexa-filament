<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $connection = 'mysql_employees'; // kalau jobs ada di DB employees
    protected $table = 'Jobs';

    public function overtimes()
    {
        return $this->hasMany(Overtime::class, 'job_id', 'id');
    }

     protected $fillable = ['id','job_name'];
}
