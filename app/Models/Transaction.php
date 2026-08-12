<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'invoice_number',
        'user_id',
        'store_id',
        'customer_id',

        'subtotal',
        'shipping_cost',
        'points_used',
        'total',

        'payment_status',
        'delivery_type',
        'driver_name',
        'notes'
    ];

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
}
