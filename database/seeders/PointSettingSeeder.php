<?php

namespace Database\Seeders;

use App\Models\PointSetting;
use Illuminate\Database\Seeder;

class PointSettingSeeder extends Seeder
{
    public function run(): void
    {
        PointSetting::firstOrCreate(
            ['id' => 1],
            [
                'spend_amount' => 100000,
                'point_reward' => 1000,
                'minimum_transaction' => 100000,
                'is_active' => true,
            ]
        );
    }
}