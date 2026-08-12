<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'store_code',
        'company_id',
        'name',
        'address'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_store')
            ->withPivot(['stock', 'price', 'price_all', 'discount'])
            ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function targets()
    {
        return $this->hasMany(StoreTarget::class);
    }
}
