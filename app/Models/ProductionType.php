<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}