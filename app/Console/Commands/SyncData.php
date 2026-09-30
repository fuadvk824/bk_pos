<?php

namespace App\Console\Commands;

use Database\Seeders\Barcode;
use Illuminate\Console\Command;
use Database\Seeders\StoreSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\PriceSeeder;

class SyncData extends Command
{
    protected $signature = 'data:sync';

    protected $description = 'Sinkronisasi store, category, product, price dan barcode';

    public function handle(): int
    {
        $this->info('======================================');
        $this->info('START DATA SYNC');
        $this->info('======================================');

        try {

            $this->info('1. Sync Store...');
            $this->call(StoreSeeder::class);

            $this->info('2. Sync Category...');
            $this->call(CategorySeeder::class);

            $this->info('3. Sync Product & Stock...');
            $this->call(ProductSeeder::class);

            $this->info('4. Sync Price...');
            $this->call(PriceSeeder::class);

            $this->info('5. Sync Barcode...');
            // $this->call(Barcode::class);

            $this->newLine();

            $this->info('======================================');
            $this->info('DATA SYNC SELESAI');
            $this->info('======================================');

            return self::SUCCESS;

        } catch (\Throwable $e) {

            $this->error('DATA SYNC GAGAL');
            $this->error($e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }
}

