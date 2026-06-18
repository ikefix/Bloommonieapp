<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'symbol'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }


}