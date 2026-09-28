<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerStoreSeeder extends Seeder
{
    public function run(): void
    {
        $updated = 0;
        $skipped = 0;

        // Ambil customer yang store_id-nya masih NULL
        // berdasarkan transaksi yang sudah ada.
        $customers = DB::table('transactions')
            ->join(
                'customers',
                'transactions.customer_id',
                '=',
                'customers.id'
            )
            ->whereNull('customers.store_id')
            ->whereNotNull('transactions.customer_id')
            ->select(
                'transactions.customer_id',
                DB::raw('COUNT(DISTINCT transactions.store_id) as total_stores'),
                DB::raw('MIN(transactions.store_id) as store_id')
            )
            ->groupBy('transactions.customer_id')
            ->orderBy('transactions.customer_id')
            ->get();

        $this->command->info(
            "Ditemukan {$customers->count()} customer yang store_id-nya masih NULL."
        );

        $this->command->newLine();

        foreach ($customers as $customer) {

            // =====================================================
            // CUSTOMER MEMILIKI TRANSAKSI DI LEBIH DARI 1 TOKO
            // =====================================================
            if ($customer->total_stores > 1) {

                $skipped++;

                // Ambil semua transaksi customer tersebut
                $transactions = DB::table('transactions')
                    ->where('customer_id', $customer->customer_id)
                    ->select(
                        'id',
                        'store_id'
                    )
                    ->orderBy('id')
                    ->get();

                $this->command->warn(
                    "Customer ID {$customer->customer_id} di-skip karena memiliki transaksi di beberapa toko."
                );

                $this->command->table(
                    ['Transaction ID', 'Store ID'],
                    $transactions->map(function ($transaction) {
                        return [
                            $transaction->id,
                            $transaction->store_id,
                        ];
                    })->toArray()
                );

                $this->command->newLine();

                continue;
            }

            // =====================================================
            // CUSTOMER HANYA MEMILIKI TRANSAKSI DI 1 TOKO
            // =====================================================

            DB::table('customers')
                ->where('id', $customer->customer_id)
                ->whereNull('store_id')
                ->update([
                    'store_id' => $customer->store_id,
                    'updated_at' => now(),
                ]);

            $updated++;

            $this->command->info(
                "Customer ID {$customer->customer_id} -> Store ID {$customer->store_id}"
            );
        }

        // =========================================================
        // SUMMARY
        // =========================================================

        $this->command->newLine();
        $this->command->info('======================================');
        $this->command->info('Customer Store Seeder selesai.');
        $this->command->info('======================================');
        $this->command->info("Customer berhasil di-update : {$updated}");
        $this->command->warn("Customer di-skip             : {$skipped}");
        $this->command->info('======================================');
    }
}
