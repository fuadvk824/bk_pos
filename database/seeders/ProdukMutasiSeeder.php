<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use App\Models\Product;
use Carbon\Carbon;
use Throwable;

class ProdukMutasiSeeder extends Seeder
{
    /**
     * Daftar pemindahan produk.
     *
     * source_store  = store asal
     * destination_store = store tujuan
     * product_code  = kode produk
     * quantity      = jumlah yang dipindahkan
     */
    private array $transfers = [
        [
            'source_store'      => 'STORE_A',
            'destination_store' => 'STORE_B',
            'product_code'      => 'PRODUK_Z',
            'quantity'          => 3,
        ],

        // Tambahkan transfer lainnya di sini
        // [
        //     'source_store'      => 'STORE_A',
        //     'destination_store' => 'STORE_C',
        //     'product_code'      => 'PRODUK_X',
        //     'quantity'          => 5,
        // ],
    ];

    public function run(): void
    {
        foreach ($this->transfers as $transfer) {

            try {

                DB::transaction(function () use ($transfer) {

                    $sourceStore = Store::where(
                        'store_code',
                        $transfer['source_store']
                    )->first();

                    $destinationStore = Store::where(
                        'store_code',
                        $transfer['destination_store']
                    )->first();

                    if (!$sourceStore) {
                        throw new \Exception(
                            "Store asal tidak ditemukan: {$transfer['source_store']}"
                        );
                    }

                    if (!$destinationStore) {
                        throw new \Exception(
                            "Store tujuan tidak ditemukan: {$transfer['destination_store']}"
                        );
                    }

                    if ($sourceStore->id === $destinationStore->id) {
                        throw new \Exception(
                            'Store asal dan tujuan tidak boleh sama.'
                        );
                    }

                    $product = Product::where(
                        'product_code',
                        $transfer['product_code']
                    )->first();

                    if (!$product) {
                        throw new \Exception(
                            "Produk tidak ditemukan: {$transfer['product_code']}"
                        );
                    }

                    $quantity = (int) $transfer['quantity'];

                    if ($quantity <= 0) {
                        throw new \Exception(
                            'Quantity harus lebih dari 0.'
                        );
                    }

                    /*
                     * Ambil stock store asal.
                     */
                    $sourceProductStore = DB::table('product_store')
                        ->where('product_id', $product->id)
                        ->where('store_id', $sourceStore->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$sourceProductStore) {
                        throw new \Exception(
                            "Produk {$product->product_code} tidak tersedia di store asal {$sourceStore->store_code}."
                        );
                    }

                    /*
                     * Pastikan stock cukup.
                     */
                    if ($sourceProductStore->stock < $quantity) {
                        throw new \Exception(
                            "Stock {$product->product_code} di {$sourceStore->store_code} tidak cukup. "
                            . "Stock tersedia: {$sourceProductStore->stock}, "
                            . "diminta: {$quantity}."
                        );
                    }

                    $now = Carbon::now();

                    /*
                     * 1. KURANGI STOCK STORE ASAL
                     */
                    DB::table('product_store')
                        ->where('id', $sourceProductStore->id)
                        ->update([
                            'stock'      => $sourceProductStore->stock - $quantity,
                            'updated_at' => $now,
                        ]);

                    /*
                     * 2. CEK PRODUK DI STORE TUJUAN
                     */
                    $destinationProductStore = DB::table('product_store')
                        ->where('product_id', $product->id)
                        ->where('store_id', $destinationStore->id)
                        ->lockForUpdate()
                        ->first();

                    if ($destinationProductStore) {

                        /*
                         * PRODUK SUDAH ADA DI STORE TUJUAN
                         * → tambahkan quantity
                         */
                        DB::table('product_store')
                            ->where('id', $destinationProductStore->id)
                            ->update([
                                'stock' => $destinationProductStore->stock + $quantity,
                                'updated_at' => $now,
                            ]);

                        $this->info(
                            "UPDATE: {$product->product_code} "
                            . "{$sourceStore->store_code} → "
                            . "{$destinationStore->store_code} "
                            . "| qty: {$quantity}"
                        );

                    } else {

                        /*
                         * PRODUK BELUM ADA DI STORE TUJUAN
                         * → buat record baru
                         */
                        DB::table('product_store')->insert([
                            'product_id' => $product->id,
                            'store_id'   => $destinationStore->id,
                            'stock'      => $quantity,

                            /*
                             * Harga mengikuti harga store asal.
                             * Bisa Anda ubah sesuai kebutuhan.
                             */
                            'price_all'  => $sourceProductStore->price_all,
                            'price'      => $sourceProductStore->price,
                            'discount'   => $sourceProductStore->discount,

                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        $this->info(
                            "INSERT: {$product->product_code} "
                            . "{$sourceStore->store_code} → "
                            . "{$destinationStore->store_code} "
                            . "| qty: {$quantity}"
                        );
                    }

                    /*
                     * 3. UPDATE stock_all PRODUK
                     *
                     * stock_all = total stock seluruh store.
                     */
                    $totalStock = DB::table('product_store')
                        ->where('product_id', $product->id)
                        ->sum('stock');

                    Product::where('id', $product->id)
                        ->update([
                            'stock_all' => $totalStock,
                            'updated_at' => $now,
                        ]);

                    $this->info(
                        "Stock total {$product->product_code}: {$totalStock}"
                    );
                });

            } catch (Throwable $e) {

                $this->error(
                    "TRANSFER GAGAL: "
                    . "{$transfer['source_store']} → "
                    . "{$transfer['destination_store']} | "
                    . "{$transfer['product_code']} | "
                    . $e->getMessage()
                );
            }
        }

        $this->info('ProductTransferSeeder selesai.');
    }

    private function info(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }

    private function error(string $message): void
    {
        if ($this->command) {
            $this->command->error($message);
        }
    }
}