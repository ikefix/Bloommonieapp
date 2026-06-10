<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{

    protected $fillable = [
        'shop_id',
        'batch_no',
        'production_type_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    public function productionType()
    {
        return $this->belongsTo(ProductionType::class);
    }

    public function entries()
{
    return $this->hasMany(ProductionEntry::class);
}

public function shop()
{
    return $this->belongsTo(Shop::class);
}

public function warehouse()
{
    return $this->belongsTo(Warehouse::class);
}
}