<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class BarcodeSeeder extends Seeder
{
    public function run(): void
    {
        Product::whereNull('barcode')
            ->each(function ($product) {

                $product->update([
                    'barcode' => 'BKPOS:PRD:' . $product->id,
                ]);

            });

        $this->command->info(
            'BarcodeSeeder selesai.'
        );
    }
}

