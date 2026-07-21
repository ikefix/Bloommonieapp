<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Product;
use App\Models\Shop;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone', // 
        'password',
        'phone',
        'role',
        'shop_id', // Add shop_id here
        'plan',
        'owner_id', // ADD THIS
        'plan_duration',
        'plan_start',
        'plan_end',
        'is_activated',
        'activated_at',
        'owner_id',

        'email_verified_at', // 👈 ADD THIS
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Define the relationship between the user and the store.
     */
    public function shops()
    {
        return $this->hasMany(Shop::class);
    }
    

    public function productions()
{
    return $this->hasMany(\App\Models\Production::class, 'user_id');
}

    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'to_shop_id');
    }

    // User.php
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

public function complaints()
{
    return $this->hasMany(Complaint::class);
}

public function getOwnerId()
{
    return $this->owner_id ?? $this->id;
}

public function getOwnerAccount()
{
    return $this->owner_id 
        ? self::find($this->owner_id) 
        : $this;
}

public function getPlanLimits()
{
    $owner = $this->owner_id
        ? User::find($this->owner_id)
        : $this;

    return config('plans.' . $owner->plan);
}

public function hasFeature($feature)
{
    $owner = $this->owner_id
        ? User::find($this->owner_id)
        : $this;

    $plan = config('plans.' . $owner->plan);

    if (!$plan) {
        return false;
    }

    // enterprise access
    if (in_array('*', $plan['features'])) {
        return true;
    }

    return in_array($feature, $plan['features']);
}

public function canCreateMoreUsers()
{
    $owner = $this->owner_id
        ? User::find($this->owner_id)
        : $this;

    $limits = $owner->getPlanLimits();

    // unlimited
    if (is_null($limits['users'])) {
        return true;
    }

    $staffCount = User::where('owner_id', $owner->id)
        ->whereIn('role', ['manager', 'cashier'])
        ->count();

    return $staffCount < $limits['users'];
}

public function canCreateMoreStores()
{
    $owner = $this->owner_id
        ? User::find($this->owner_id)
        : $this;

    $limits = $owner->getPlanLimits();

    if (is_null($limits['stores'])) {
        return true;
    }

    $storeCount = Shop::where('owner_id', $owner->id)->count();

    return $storeCount < $limits['stores'];
}

public function canCreateMoreProducts()
{
    $owner = $this->owner_id
        ? User::find($this->owner_id)
        : $this;

    $limits = $owner->getPlanLimits();

    if (is_null($limits['products'])) {
        return true;
    }

    $productCount = Product::where('owner_id', $owner->id)->count();

    return $productCount < $limits['products'];
}
}

