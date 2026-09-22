<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_code',
        'category_id',
        'name',
        'description',
        'unit',
        'unit2',
        'stock_all',
        'image',
        'barcode'
    ];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'product_store')
            ->withPivot(['stock', 'price', 'price_all', 'discount'])
            ->withTimestamps();
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
