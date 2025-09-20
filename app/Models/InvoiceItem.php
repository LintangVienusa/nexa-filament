<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class InvoiceItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $fillable = [
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

            $invoiceid = $item->invoice_id ?? null; 
            $periodeCarbon = Carbon::now();
            $periodeString = $periodeCarbon->format('F Y');

            $invoice = Invoice::when($invoiceid, function ($q) use ($invoiceid) {
                    $q->where('customer_id', $invoiceid);
                })
                ->where('created_at', $periodeCarbon)
                ->first();

            if ($invoice) {
                $item->invoice_id = $invoice->id;
            } else {
                $invoiceNumber = 'INV-' . strtoupper($periodeCarbon->format('Ym')) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'invoice_date'   => now(),
                    'due_date'       => now()->addDays(14),
                    'customer_id'    => $item->customer_id,
                    'status'         => 'draft',
                    'create_by'      => Auth::user()->email,
                ]);

                $item->invoice_id = $invoice->id;
            }
        });

        
        static::created(function ($item) {
            $invoice = $item->invoice;

            if ($invoice) {
                $total = $invoice->items()->sum(DB::raw('qty * unit_price'));
                $invoice->subtotal = $total;
                $invoice->save();
            }
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
