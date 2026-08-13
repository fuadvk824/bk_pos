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
            $this->warn(
                'Store kosong, jalankan StoreSeeder dulu.'
            );

            return;
        }

        $categories = Category::pluck('id', 'category_code');
        $totalStock = [];

        foreach ($stores as $store) {

            $response = Http::timeout(60)
                ->retry(3, 2000)
                ->get(
                    'https://bmp.my.id/bk/api/get_price_all.php',
                    [
                        'whid' => $store->store_code,
                    ]
                );

            if ($response->failed()) {

                $this->error(
                    "Gagal WHID: {$store->store_code}"
                );

                continue;
            }

            $items = $response->json();

            if (!is_array($items)) {

                $this->error(
                    "Response product tidak valid WHID: {$store->store_code}"
                );

                continue;
            }

            foreach ($items as $item) {

                if (
                    !isset($item['itemid']) ||
                    !isset($item['itemdesc']) ||
                    !isset($item['itgrpid'])
                ) {
                    continue;
                }

                if (!isset($categories[$item['itgrpid']])) {
                    continue;
                }

                $categoryId = $categories[$item['itgrpid']];

                $product = Product::where(
                    'product_code',
                    $item['itemid']
                )->first();

                $now = Carbon::now();

                if ($product) {

                    $product->update([
                        'category_id' => $categoryId,
                        'name'        => $item['itemdesc'],
                        'description' => null,
                        'image'       => null,
                        'updated_at'  => $now,
                    ]);
                } else {

                    $product = Product::create([
                        'product_code' => $item['itemid'],
                        'category_id'  => $categoryId,
                        'name'         => $item['itemdesc'],
                        'description'  => null,
                        'image'        => null,
                        'barcode'      => null,
                        'stock_all'    => 0,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }

                $productStore = DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->where('store_id', $store->id)
                    ->first();

                $stock = $item['qty'] ?? 0;
                $priceAll = $item['price'] ?? 0;

                if ($productStore) {

                    DB::table('product_store')
                        ->where('product_id', $product->id)
                        ->where('store_id', $store->id)
                        ->update([
                            'stock'      => $stock,
                            'price_all'  => $priceAll,
                            'updated_at' => $now,
                        ]);
                } else {

                    DB::table('product_store')->insert([
                        'product_id' => $product->id,
                        'store_id'   => $store->id,
                        'stock'      => $stock,
                        'price_all'  => $priceAll,

                        'price'      => 0,
                        'discount'   => 0,

                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                if (!isset($totalStock[$product->id])) {
                    $totalStock[$product->id] = 0;
                }

                $totalStock[$product->id] += (int) $stock;
            }

            $this->info(
                "Selesai store: {$store->store_code}"
            );
        }

        foreach ($totalStock as $productId => $stock) {

            Product::where('id', $productId)
                ->update([
                    'stock_all'  => $stock,
                    'updated_at' => Carbon::now(),
                ]);
        }

        $this->info(
            'ProductSeeder selesai + stock_all updated.'
        );
    }

    private function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }

    private function warn(string $message): void
    {
        if ($this->command) {
            $this->command->warn($message);
        }
    }

    private function error(string $message): void
    {
        if ($this->command) {
            $this->command->error($message);
        }
    }
}
