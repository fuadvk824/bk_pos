<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command->warn('Store kosong, jalankan StoreSeeder dulu');
            return;
        }

        $categories = Category::pluck('id', 'category_code');
        $totalStock = [];

        foreach ($stores as $store) {

            $response = Http::timeout(60)  // tunggu lebih lama
                ->retry(3, 2000) // coba 3x, jeda 2 detik
                ->get('https://bmp.my.id/bk/api/get_price_all.php', [
                    'whid' => $store->store_code
                ]);

            if ($response->failed()) {
                $this->command->error("Gagal WHID: {$store->store_code}");
                continue;
            }

            $items = $response->json();

            foreach ($items as $item) {

                if (!isset($categories[$item['itgrpid']])) {
                    continue;
                }

                $categoryId = $categories[$item['itgrpid']];

                $product = Product::updateOrCreate(
                    ['product_code' => $item['itemid']],
                    [
                        'category_id' => $categoryId,
                        'name' => $item['itemdesc'],
                        'description' => null,
                        'image' => null,
                        'barcode' => null,
                    ]
                );

                DB::table('product_store')->updateOrInsert(
                    [
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                    ],
                    [
                        'stock' => $item['qty'],
                        'price_all' => $item['price'],
                        'updated_at' => Carbon::now(),
                        // 'created_at' => Carbon::now(),//ini klw pertama buat aja on kn
                    ]
                );

                if (!isset($totalStock[$product->id])) {
                    $totalStock[$product->id] = 0;
                }

                $totalStock[$product->id] += (int) $item['qty'];
            }

            $this->command->info("Selesai store: {$store->store_code}");
        }

        foreach ($totalStock as $productId => $stock) {
            Product::where('id', $productId)->update([
                'stock_all' => $stock
            ]);
        }

        $this->command->info('Seeder product & product_store selesai + stock_all updated');
    }
}

 