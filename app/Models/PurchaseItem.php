<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'category_id',
        'shop_id',
        'owner_id', // 🔥 IMPORTANT ADDITION

        'quantity',
        'total_price',
        'payment_method',
        'transaction_id',
        'discount_type',
        'discount_value',
        'discount',
        'cashier_id',

        'customer_name',
        'customer_phone',

        'sale_type',
    ];

    /*
    |-------------------------------------------------
    | TENANT ISOLATION (GLOBAL SCOPE)
    |-------------------------------------------------
    */
protected static function booted()
{
    static::addGlobalScope('owner', function ($query) {
        if (auth()->check()) {
            $user = auth()->user();

            // Use owner_id from the user, not the user's id
            $ownerID = $user->owner_id ?? $user->id;

            $query->where(
                'purchase_items.owner_id',
                $ownerID
            );
        }
    });
}

    /*
    |-------------------------------------------------
    | RELATIONSHIPS
    |-------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /*
    |-------------------------------------------------
    | ACCESSORS
    |-------------------------------------------------
    */

    public function getFinalPriceAttribute()
    {
        return $this->total_price - $this->discount;
    }

    /*
    |-------------------------------------------------
    | BUSINESS LOGIC
    |-------------------------------------------------
    */

    public static function calculateTotalSales($date = null, $search = null)
    {
        $query = self::query();

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->get()->sum(function ($sale) {
            $discounted = $sale->total_price - ($sale->discount ?? 0);
            return max($discounted, 0);
        });
    }
}