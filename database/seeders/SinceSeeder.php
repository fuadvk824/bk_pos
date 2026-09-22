<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SinceSeeder extends Seeder
{
    public function run(): void
    {
        Transaction::query()
            ->whereNull('since')
            ->whereNotNull('created_at')
            ->update([
                'since' => DB::raw('created_at'),
            ]);
    }
}
