<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'company_code',
        'name',
        'address'
    ];
    public function stores()
    {
        return $this->hasMany(Store::class);
    }
}
