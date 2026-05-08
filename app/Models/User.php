<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'shop_id', // Add shop_id here
        'plan',
        'plan_duration',
        'plan_start',
        'plan_end',
        'is_activated',
        'activated_at',
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
        // If no owner_id is provided, set temporary value
        if (!$user->owner_id) {
            $user->owner_id = 0; // temporary placeholder
        }
    });

    static::created(function ($user) {
        // After ID is generated, set owner_id to self
        if ($user->owner_id == 0) {
            $user->owner_id = $user->id;
            $user->saveQuietly(); // avoids infinite loop
        }
    });
}  
}

