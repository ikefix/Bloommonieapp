<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location', 
        'owner_id', // 🔥 IMPORTANT
        'user_id',
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
    | RELATIONSHIPS
    |-------------------------------------------------
    */

    // the owner (admin/client)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function products()
{
    return $this->hasMany(\App\Models\Product::class, 'shop_id');
}

    public function users()
    {
        return $this->hasMany(User::class, 'shop_id');
    }

    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_shop_id');
    }

    public function purchases()
    {
        return $this->hasMany(PurchaseItem::class);
    }
    public function productions()
{
    return $this->hasMany(Production::class);
}
}