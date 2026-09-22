<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kabupaten extends Model
{
    protected $table = 's_kabupaten';

    protected $fillable = [
        'provinsi_id',
        'kode',
        'nama',
    ];

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id');
    }

    public function kecamatan(): HasMany
    {
        return $this->hasMany(Kecamatan::class, 'kabupaten_id');
    }
}