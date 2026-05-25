<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'shop_id',
        'owner_id', // 🔥 IMPORTANT ADDITION

        'invoice_number',
        'invoice_date',
        'goods',

        'discount',
        'tax',
        'total',

        'payment_type',
        'amount_paid',
        'balance',
        'payment_status',
    ];

    /*
    |-------------------------------------------------
    | TENANT ISOLATION
    |-------------------------------------------------
    */
    protected static function booted()
    {
        static::addGlobalScope('owner', function ($query) {
            $user = auth()->user();

            if ($user && $user->role !== 'superadmin') {
                $ownerId = $user->owner_id ?? $user->id;
                $query->where('owner_id', $ownerId);
            }
        });

        static::creating(function ($model) {
            $user = auth()->user();

            if ($user) {
                $model->owner_id = $user->owner_id ?? $user->id;
            }
        });
    }

    /*
    |-------------------------------------------------
    | CASTS
    |-------------------------------------------------
    */
    protected $casts = [
        'goods' => 'array',
    ];

    /*
    |-------------------------------------------------
    | RELATIONSHIPS
    |-------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /*
    |-------------------------------------------------
    | ACCESSORS
    |-------------------------------------------------
    */

    public function getProductNameAttribute()
    {
        $goods = $this->goods;

        if (empty($goods)) {
            return 'Unknown Product';
        }

        if (isset($goods['product_id'])) {
            return Product::where('id', $goods['product_id'])->value('name') ?? 'Unknown Product';
        }

        $productIds = array_column($goods, 'product_id');
        $names = Product::whereIn('id', $productIds)->pluck('name')->toArray();

        return implode(', ', $names);
    }

    public function getQuantityAttribute()
    {
        $goods = $this->goods;

        if (empty($goods)) return 0;

        if (isset($goods['quantity'])) {
            return $goods['quantity'];
        }

        return array_sum(array_column($goods, 'quantity'));
    }

    public function user()
{
    return $this->belongsTo(User::class);
}

    
}