<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provinsi extends Model
{
    protected $table = 's_provinsi';

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function kabupaten(): HasMany
    {
        return $this->hasMany(Kabupaten::class, 'provinsi_id');
    }
}