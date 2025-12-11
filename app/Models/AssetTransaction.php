<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetTransaction extends Model
{
     use HasFactory;

    protected $connection = 'mysql_inventory';
    protected $table = 'AssetTransactions';
    
    protected static ?string $title = 'Transaksi Asset';

    protected $fillable = [
        'transaction_type',
        'PIC',
        'asset_qty_now',
        'request_asset_qty',
        'notes',
        'ba_number',
        'ba_description',
        'status',
        'file_asset',
        'usage_type',
        'assigned_type',
        'assigned_id',
        'recipient_by',
        'sender_by',
        'sender_custom',
        'province_code',
        'regency_code',
        'village_code',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($release) {
            $last = self::latest('id')->first();
            $number = $last ? $last->id + 1 : 1;
            $release->transaction_code = 'ASR' . str_pad($number, 5, '0', STR_PAD_LEFT);
        });

        
    }

    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_id');
    }

    public function inventory()
    {
        return $this->belongsTo(InventoryAsset::class, 'inventory_id');
    }

    public function movement()
    {
        return $this->belongsTo(AssetMovement::class, 'movement_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'name');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 2);
    }

    public function scopePending($query)
    {
        return $query->where('status', 1);
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            0 => 'Submitted',
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown',
        };
    }
    

    protected static function generateBANumber()
    {
        $month = now()->format('m');
        $year = now()->format('Y');

        // Hitung jumlah BA yang sudah dibuat bulan ini
        $count = self::whereMonth('created_at', $month)
                     ->whereYear('created_at', $year)
                     ->count() + 1;

        $number = str_pad($count, 3, '0', STR_PAD_LEFT); // 001, 002, ...

        return "DPN/BA/{$number}/{$month}/{$year}";
    }
}
