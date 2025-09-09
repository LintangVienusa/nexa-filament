<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;
    protected $connection = 'mysql_employees';
    protected $table = 'Organizations';
    protected $fillable = [
        'divisi_name',
        'unit_name',
        'created_at',
        'updated_at'
    ];

    public function employees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class, 'org_id');
    }
}
