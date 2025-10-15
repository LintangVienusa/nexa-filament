<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;


use Carbon\Carbon;

class InvoiceItem extends Model
{
    use HasFactory,LogsActivity;
    protected $table = 'InvoiceItems';
    protected $primaryKey = 'id';
    
     public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price'])
            ->logOnlyDirty()       // hanya catat perubahan kolom yang berubah
            ->useLogName('invoice_item');
    }

    protected $fillable = [
        'po_number',
        'po_description',
        'customer_id',
        'invoice_id',
        'service_id',
        'description',
        'invoice_date',
        'qty',
        'unit_price',
        'subtotal',
    ];

    protected static function booted()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->subtotal = (int)$item->qty * (int)$item->unit_price;
            
            $po_number = $item->po_number ?? null;
            $invoiceid = $item->invoice_id ?? null;
            $customer_id = $item->customer_id ?? null;
            // $customer_initial = $customer->initial ?? null;
            $customer = Customer::find($customer_id);
            $customer_initial = $customer->initial ?? '';
            $now = Carbon::now();
            $periodeCarbon = Carbon::parse($item->invoice_date ?? now());
            $periodeString = $periodeCarbon->format('F Y');
            $invoiceNumber = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT).'/DPNG/INV/'.$customer_initial.'/'. strtoupper($periodeCarbon->format('Y'));


            $invoice = Invoice::when($invoiceid, function ($q) use ($customer_id, $invoiceid) {
                    $q->where('customer_id', $customer_id);
                })
                ->where('invoice_date', $periodeCarbon)
                ->where('created_at', '>=', $now->copy()->startOfSecond())
                ->where('created_at', '<=', $now->copy()->endOfSecond())
                ->first();

            if ($invoice) {
                $item->invoice_id = $invoice->id;
            } else {
                
                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'invoice_date'   => now(),
                    'due_date'       => now()->addDays(14),
                    'customer_id'    => $item->customer_id,
                    'status'         => '0',
                    'create_by'      => Auth::user()->email,
                ]);

                $item->invoice_id = $invoice->id;
            }
        });

        static::created(function ($item) {
            $invoice = $item->invoice;
            if ($invoice) {
                $total = $invoice->items()->sum(DB::raw('qty * unit_price'));
                $taxRate = "0.11";
                $dprate = "0.20";
                $tax = $total * $taxRate;
                $amount = $total - $tax;
                $dp = $total * $dprate;

                $invoice->subtotal = $total;
                $invoice->tax_rate = $taxRate;
                $invoice->tax_amount = $tax;
                $invoice->dp_rate = $dprate;
                $invoice->dp = $dp;
                $invoice->amount = $amount;
                $invoice->save();
            }
        });

        static::created(function ($record) {
            $user = auth()->user();

            $activity = activity('filament-action')
                ->causedBy($user)
                ->withProperties([
                    'ip' =>  request()->ip(),
                    'email' => $user?->email,
                    'record_id' => $record->id,
                    'name' => $record->name ?? null,
                ])
                ->log('Membuat record InvoiceItem baru');
                Activity::latest()->first()->update([
                    'email' => auth()->user()?->email,
                ]);
        });
    }

    protected $casts = [
        'price' => 'float',
        'subtotal'   => 'float',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items() {
        return $this->hasMany(InvoiceItem::class, 'id', 'id');
    }

    public function scopeUniqueInvoiceItem($query)
    {
        return $query->select('invoice_id',DB::raw('MAX(id) as id'))
                    ->groupBy('invoice_id')
                    ->orderBy('id', 'asc');
    }
}
