<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStore extends Model
{
    protected $table = 'product_store';

    protected $fillable = [
        'product_id',
        'store_id',
        'stock',
        'conv2',
        'price',
        'price_all',
        'discount',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}