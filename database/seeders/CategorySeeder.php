<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Store;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::select('store_code')->get();

        if ($stores->isEmpty()) {
            $this->command->warn(
                'Data store kosong, jalankan StoreSeeder terlebih dahulu.'
            );

            return;
        }

        foreach ($stores as $store) {

            $whid = $store->store_code;

            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get(
                    'https://bmp.my.id/bk/api/get_kategori.php',
                    [
                        'whid' => $whid,
                    ]
                );

            if ($response->failed()) {
                $this->command->error(
                    "Gagal ambil kategori WHID: {$whid}"
                );

                continue;
            }

            $categories = $response->json();

            if (!is_array($categories)) {
                $this->command->error(
                    "Response kategori tidak valid WHID: {$whid}"
                );

                continue;
            }

            foreach ($categories as $cat) {

                if (
                    !isset($cat['itgrpid']) ||
                    !isset($cat['itgrpname'])
                ) {
                    continue;
                }

                DB::table('categories')->updateOrInsert(
                    [
                        'category_code' => $cat['itgrpid'],
                    ],
                    [
                        'name'       => trim($cat['itgrpname']),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info(
                "Kategori selesai WHID: {$whid}"
            );
        }

        $this->command->info(
            'CategorySeeder selesai.'
        );
    }
}
