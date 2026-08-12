<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTarget extends Model
{
    protected $fillable = [
        'store_id',
        'year',
        'month',
        'target_amount',
        'status',
    ];

     protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'target_amount' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
