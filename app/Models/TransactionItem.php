<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'stock_at_transaction',
        'fulfillment_status',
        'base_price',
        'price',
        'discount',
        'subtotal',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function mutationHistories()
    {
        return $this->hasMany(StockMutationHistory::class);
    }
}
