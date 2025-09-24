<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'Customers';
    protected $fillable = [
        'customer_name',
        'address',
        'email',
        'phone',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
