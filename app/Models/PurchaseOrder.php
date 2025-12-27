<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $connection = 'mysql_inventory';
    protected $table = 'PurchaseOrder';
    protected $primaryKey = 'po_number';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'po_number',
        'order_date',
        'po_issuer',
        'site_name',
        'kecamatan',
        'job_type',
        'total_target',
        'project_start_date',
        'project_end_date',
        'vendor',
        'pic_name',
        'pic_mobile_no',
        'pic_email',
        'po_status',
        'payment_terms'
    ];
}
