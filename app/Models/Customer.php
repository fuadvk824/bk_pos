<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'address',
        'current_point',
    ];

    public function customerPoints()
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
