<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Store;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::select('store_code')->get();

        if ($stores->isEmpty()) {
            $this->warn(
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
                $this->error(
                    "Gagal ambil kategori WHID: {$whid}"
                );

                continue;
            }

            $categories = $response->json();

            if (!is_array($categories)) {
                $this->error(
                    "Response kategori tidak valid WHID: {$whid}"
                );

                continue;
            }

            foreach ($categories as $category) {

                if (
                    !isset($category['itgrpid']) ||
                    !isset($category['itgrpname'])
                ) {
                    continue;
                }

                $categoryCode = $category['itgrpid'];
                $categoryName = trim($category['itgrpname']);

                $existingCategory = DB::table('categories')
                    ->where('category_code', $categoryCode)
                    ->first();

                $now = Carbon::now();

                if ($existingCategory) {

                    DB::table('categories')
                        ->where('id', $existingCategory->id)
                        ->update([
                            'name'       => $categoryName,
                            'updated_at' => $now,
                        ]);
                } else {

                    DB::table('categories')->insert([
                        'category_code' => $categoryCode,
                        'name'          => $categoryName,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            $this->info(
                "Kategori selesai WHID: {$whid}"
            );
        }

        $this->info('CategorySeeder selesai.');
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
