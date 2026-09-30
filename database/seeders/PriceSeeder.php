<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Store;

class PriceSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::select('id', 'store_code')->get();

        if ($stores->isEmpty()) {

            $this->warn(
                'Store kosong, jalankan StoreSeeder terlebih dahulu.'
            );

            return;
        }

        foreach ($stores as $store) {

            $this->info(
                "Memproses price store: {$store->store_code}"
            );

            $productStores = DB::table('product_store')
                ->join(
                    'products',
                    'products.id',
                    '=',
                    'product_store.product_id'
                )
                ->where(
                    'product_store.store_id',
                    $store->id
                )
                ->select(
                    'product_store.product_id',
                    'product_store.store_id',
                    'products.product_code'
                )
                ->get();

            foreach ($productStores as $ps) {

                try {

                    $response = Http::timeout(30)
                        ->retry(3, 1000)
                        ->get(
                            'https://bmp.my.id/bk/api/get_price2.php',
                            [
                                'whid'   => $store->store_code,
                                'itemid' => $ps->product_code,
                            ]
                        );

                    if ($response->failed()) {

                        $this->error(
                            "Gagal price: {$store->store_code} - {$ps->product_code}"
                        );

                        continue;
                    }

                    $data = $response->json();

                    if (!is_array($data)) {

                        $this->error(
                            "Response price tidak valid: "
                                . "{$store->store_code} - {$ps->product_code}"
                        );

                        continue;
                    }

                    $stock = is_numeric($data['stock_qty'] ?? null)
                        ? $data['stock_qty']
                        : 0;

                    DB::table('product_store')
                        ->where('product_id', $ps->product_id)
                        ->where('store_id', $store->id)
                        ->update([
                            'stock'      => $stock,
                            'updated_at' => now(),
                        ]);


                    if (!isset($data['prices'])) {
                        continue;
                    }

                    $retail = collect($data['prices'])
                        ->firstWhere('level', 'Retail');

                    if (!$retail) {
                        continue;
                    }

                    $price = is_numeric(
                        $retail['harga1'] ?? null
                    )
                        ? $retail['harga1']
                        : 0;

                    $discount = is_numeric(
                        $retail['disc1'] ?? null
                    )
                        ? $retail['disc1']
                        : 0;

                    DB::table('product_store')
                        ->where('product_id', $ps->product_id)
                        ->where('store_id', $store->id)
                        ->update([
                            'price'      => $price,
                            'discount'   => $discount,
                            'updated_at' => now(),
                        ]);
                } catch (\Throwable $e) {

                    $this->error(
                        "Error {$store->store_code} - {$ps->product_code}: "
                            . $e->getMessage()
                    );

                    continue;
                }
            }

            $this->info(
                "Price selesai store: {$store->store_code}"
            );
        }

        $this->info(
            'PriceSeeder selesai.'
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
