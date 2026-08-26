<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $response = Http::timeout(30)
            ->retry(3, 1000)
            ->get('https://bmp.my.id/bk/api/get_whid.php');

        if ($response->failed()) {
            $this->error('Gagal mengambil data store dari API');
            return;
        }

        $stores = $response->json();

        if (!is_array($stores)) {
            $this->error('Response API tidak valid');
            return;
        }

        foreach ($stores as $store) {

            if (
                !isset($store['whid']) ||
                !isset($store['whname'])
            ) {
                continue;
            }

            $storeCode = $store['whid'];
            $storeName = trim($store['whname']);

            $existingStore = DB::table('stores')
                ->where('store_code', $storeCode)
                ->first();

            $now = Carbon::now();

            if ($existingStore) {

                DB::table('stores')
                    ->where('id', $existingStore->id)
                    ->update([
                        'company_id' => 1,
                        'name'       => $storeName,
                        'updated_at' => $now,
                    ]);
            } else {

                DB::table('stores')->insert([
                    'company_id' => 1,
                    'store_code' => $storeCode,
                    'name'       => $storeName,
                    'address'    => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->info('StoreSeeder selesai.');
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
 