<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'shop_id', // Add shop_id here
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

protected static function booted()
{
    static::creating(function ($user) {
        if (auth()->check()) {
            $user->owner_id = auth()->user()->owner_id ?? auth()->id();
        }
    });
}
    
}

