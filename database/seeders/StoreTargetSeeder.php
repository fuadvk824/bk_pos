<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreTarget;
use Illuminate\Database\Seeder;

class StoreTargetSeeder extends Seeder
{
    public function run(): void
    {
        $year = now()->year;

        $stores = Store::all();

        foreach ($stores as $store) {
            StoreTarget::create([
                'store_id' => $store->id,
                'year' => $year,
                'month' => 7,
                'target_amount' => rand(50000000, 300000000),
                'status' => rand(0, 1) ? 'active' : 'inactive',
            ]);
        }
    }
}
