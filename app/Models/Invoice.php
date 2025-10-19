<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Invoice extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'invoice_number',
                'customer_id',
                'subtotal',
                'tax_rate',
                'tax_amount',
                'amount',
                'dp',
                'status',
            ])
            ->logOnlyDirty()
            ->useLogName('Invoice');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->properties = $activity->properties->merge([
            'ip'    => request()->ip(),
            'menu'  => 'Invoice',
            'email' => $user?->email,
            'record_id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
        ]);

        $activity->email = $user?->email;
        $activity->menu  = 'Invoice';
    }

    protected $table = 'Invoices';
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'invoice_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'dp_rate',
        'dp',
        'amount',
        'status',
        'keterangan',
        'create_by',
        'approval_by',
        'approval_at',
        'file_path',
    ];

    protected $dates = [
        'invoice_date',
        'approval_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
