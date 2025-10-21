<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $connection = 'mysql_employees';
    protected $table = 'Attendances';
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'working_hours',
        'check_in_evidence',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_evidence',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    

    public function getWorkingHoursAttribute(): ?float
    {
        if ($this->check_in_time && $this->check_out_time) {
            return $this->check_in_time->diffInMinutes($this->check_out_time) / 60;
        }

        return null;
    }

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

     public static function compressBase64Image($base64String, $quality = 70)
    {
        // Hapus prefix data:image
        $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $base64String);
        $imageData = base64_decode($base64String);

        // Buat image resource dari base64
        $image = imagecreatefromstring($imageData);
        if (!$image) {
            return null; // jika gagal decode
        }

        ob_start();
        // Kompres ke JPEG (lebih kecil dari PNG)
        imagejpeg($image, null, $quality);
        $compressedData = ob_get_clean();

        imagedestroy($image);

        return 'data:image/jpeg;base64,' . base64_encode($compressedData);
    }
}
