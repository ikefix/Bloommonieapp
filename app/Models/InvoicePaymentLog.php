<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePaymentLog extends Model
{
    protected $fillable = [
        'owner_id',
        'invoice_id',
        'invoice_no',
        'cashier_id',
        'type',
        'message',
        'amount_added',
        'total_paid',
        'balance',
        'updated_by',
        'updated_by_id',
        'payment_updated_at',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}