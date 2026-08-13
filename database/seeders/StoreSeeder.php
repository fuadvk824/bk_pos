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
                        'address'    => null,
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

// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\DB;
// use Carbon\Carbon;

// class StoreSeeder extends Seeder
// {
//     public function run(): void
//     {
        
//         $companies = [
//             'BISA KULAK JATIM' => [
//                 'BISAKULAK GRESIK 1',
//                 'BISAKULAK GRESIK DADAP KUNING',
//                 'BISAKULAK GRESIK RANDEGAN SARI',
//                 'BISAKULAK GRESIK KEDAMEAN',
//                 'BISAKULAK JOGJA 1',
//                 'BISAKULAK JOGJA KASONGAN',
//             ],
//             'BEKA MAJU JAYA' => [
//                 'BISAKULAK JEMBER',
//                 'BISAKULAK JEMBER SEMPOLAN',
//                 'BISAKULAK JEMBER RAMBI',
//                 'BISAKULAK JEMBER 2',
//                 'BISAKULAK JEMBER WULUHAN',
//                 'BISAKULAK JEMBER PATRANG',
//             ],
//             'BEKA BANGUN NUSANTARA' => [
//                 'BISAKULAK GAYAM',
//                 'BISAKULAK KEDIRI GURAH',
//                 'BISAKULAK KEDIRI SANTREN',
//                 'BISAKULAK BOJONEGORO',
//             ],
//             'KULAK TUMBUH BAGUS' => [
//                 'BISAKULAK MATARAM 2 / SWETA',
//                 'BISAKULAK MATARAM 3 / JEMPONG',
//                 'BISAKULAK MATARAM 4 / ABIAN TUBUH',
//                 'BISAKULAK MATARAM 1 + GEGUTU',
//                 'BISAKULAK MATARAM 6 / GUNUNGSARI',
//                 'BISAKULAK MATARAM 7 / BRAWIJAYA',
//             ],
//             'BEKA TUTUP' => [],  
//         ];

//         $companyIds = [];

//         foreach ($companies as $companyName => $storeList) {
//             DB::table('companies')->updateOrInsert(
//                 ['company_code' => strtoupper(str_replace(' ', '_', $companyName))],
//                 [
//                     'name' => $companyName,
//                     'address' => null,
//                     'created_at' => now(),
//                     'updated_at' => now(),
//                 ]
//             );

//             // ambil id
//             $companyIds[$companyName] = DB::table('companies')
//                 ->where('company_code', strtoupper(str_replace(' ', '_', $companyName)))
//                 ->value('id');
//         }
       
//         $response = Http::get('https://bmp.my.id/bk/api/get_whid.php');

//         if ($response->failed()) {
//             $this->command->error('Gagal ambil data dari API');
//             return;
//         }

//         $stores = $response->json();

//         foreach ($stores as $store) {
//             $storeName = strtoupper(trim($store['whname']));
//             $companyId = null;

//             foreach ($companies as $companyName => $storeList) {
//                 foreach ($storeList as $mappedStore) {
//                     if ($storeName === strtoupper($mappedStore)) {
//                         $companyId = $companyIds[$companyName];
//                         break 2;
//                     }
//                 }
//             }

//             if (!$companyId) {
//                 $companyId = $companyIds['BEKA TUTUP'];
//             }

//             DB::table('stores')->updateOrInsert(
//                 ['store_code' => $store['whid']],
//                 [
//                     'company_id' => $companyId,
//                     'name' => $store['whname'],
//                     'address' => null,
//                     'created_at' => Carbon::now(),
//                     'updated_at' => Carbon::now(),
//                 ]
//             );
//         }

//         $this->command->info('Seeder stores + company berhasil dijalankan');
//     }
// }