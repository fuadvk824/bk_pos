<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSetting extends Model
{
    protected $fillable = [
        'spend_amount',
        'point_reward',
        'minimum_transaction',
        'is_active',
    ];
}
