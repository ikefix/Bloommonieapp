<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Notifications\LowStockAlert;
use App\Models\User;

class Product extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'barcode',
        'price',
        'stock_unit',
        'unit_size',
        'cost_price',
        'stock_quantity',
        'stock_limit',
        'category_id',
        'shop_id',
        'owner_id', // 🔥 IMPORTANT
    ];

    /*
    |-------------------------------------------------
    | GLOBAL SCOPE (TENANT ISOLATION)
    |-------------------------------------------------
    */
    protected static function booted()
    {
        // 🔒 Auto-filter by owner
        static::addGlobalScope('owner', function ($query) {
            $user = auth()->user();

            if ($user && $user->role !== 'superadmin') {
                $ownerId = $user->owner_id ?? $user->id;
                $query->where('owner_id', $ownerId);
            }
        });

        // 🔥 Auto-assign owner_id on create
        static::creating(function ($model) {
            $user = auth()->user();

            if ($user) {
                $model->owner_id = $user->owner_id ?? $user->id;
            }
        });
    }

    /*
    |-------------------------------------------------
    | RELATIONSHIPS
    |-------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_shop_id');
    }

    /*
    |-------------------------------------------------
    | BUSINESS LOGIC
    |-------------------------------------------------
    */

    public function checkStockAndNotify()
    {
        if ($this->stock_quantity <= $this->stock_limit) {

            $admin = User::where('role', 'admin')
                ->where('id', $this->owner_id) // 🔥 FIXED: isolate notification per tenant
                ->first();

            if ($admin) {
                $admin->notify(new LowStockAlert($this));
            }
        }
    }
}