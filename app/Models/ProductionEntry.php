<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionEntry extends Model
{
    protected $fillable = [
        'production_id',
        'entry_type',
        'item_name',
        'quantity',
        'unit',
        'price',
        'total',
        'meta',
        'notes',
        'owner_id'
    ];

    // Auto convert JSON column to array
    protected $casts = [
        'meta' => 'array',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    // Optional: auto calculate total if not provided
    public function setQuantityAttribute($value)
    {
        $this->attributes['quantity'] = $value;

        $price = $this->attributes['price'] ?? 0;

        $this->attributes['total'] = $value * $price;
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = $value;

        $quantity = $this->attributes['quantity'] ?? 0;

        $this->attributes['total'] = $quantity * $value;
    }
}