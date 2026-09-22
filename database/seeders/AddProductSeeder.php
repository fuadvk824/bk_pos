<?php


namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $store = Store::where('store_code', 'K03')->first();

            if (!$store) {
                $this->command->error('Toko TOKO-001 tidak ditemukan.');
                return;
            }

            $products = [
                [
                    'product_code' => 'GA9X12X24',
                    'category_id' => 1,
                    'name' => 'Gypsum Aplus 9mm x 1200mm x 2400mm',
                    'description' => '',
                    'unit' => 'pcs',
                    'stock' => 100,
                    'price_all' => 3000,
                    'price' => 3500,
                    'discount' => 0,
                    'image' => null,
                    'barcode' => '',
                ],

            ];

            foreach ($products as $data) {
 
                $product = Product::create([
                    'product_code' => $data['product_code'],
                    'category_id' => $data['category_id'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'unit' => $data['unit'],
                    'stock_all' => $data['stock'],
                    'image' => $data['image'],
                    'barcode' => $data['barcode'],
                ]);

              
                DB::table('product_store')->insert([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'stock' => $data['stock'],
                    'price_all' => $data['price_all'],
                    'price' => $data['price'],
                    'discount' => $data['discount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->command->info('Product berhasil ditambahkan ke toko TOKO-001.');
    }
}
