<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


use Carbon\Carbon;

class InvoiceItem extends Model
{
    use HasFactory;
    protected $table = 'InvoiceItems';
    protected $primaryKey = 'id';
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
            $now = Carbon::now();
            $periodeCarbon = Carbon::parse($item->invoice_date ?? now());
            $periodeString = $periodeCarbon->format('F Y');
            $invoiceNumber = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT).'/NXN/INV/' . strtoupper($periodeCarbon->format('Ym'));


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
                $tax = $total * $taxRate;
                $amount = $total - $tax;

                $invoice->subtotal = $total;
                $invoice->tax_rate = $taxRate;
                $invoice->tax_amount = $tax;
                $invoice->amount = $amount;
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
