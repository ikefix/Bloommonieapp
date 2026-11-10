<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',

        'full_name',
        'email',
        'phone',
        'alternate_phone',
        'company_name',

        'address',
        'apartment',
        'city',
        'state',
        'postal_code',
        'country',

        'delivery_instructions',
        'order_note',

        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'currency',

        'payment_method',
        'payment_status',
        'payment_reference',
        'payment_url',
        'paid_at',

        'order_status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
