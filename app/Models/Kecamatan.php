<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    protected $table = 's_kecamatan';

    protected $fillable = [
        'kabupaten_id',
        'kode',
        'nama',
    ];

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
    }

    public function desa(): HasMany
    {
        return $this->hasMany(Desa::class, 'kecamatan_id');
    }
}