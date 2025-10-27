<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BastDocumentation extends Model
{
     use HasFactory;
     protected $table = 'bast_documentations';

     protected $fillable = [
        'project_id',
        'serial_number',
        'bast_number',
        'bast_photo',
        'digging_photo',
        'instalasi_photo',
        'coran_photo',
        'tiang_berdiri_photo',
        'label_tiang_photo',
        'aksesoris_photo',
        'tiang_photo',
        'status',
        'latitude',
        'longitude',
        'description',
        'PIC',
        'kontraktor'
    ];
}
