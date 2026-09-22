<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            AppVersion::create([
                'version' => "1.0.2",
                'apk_url' => "https://unincarcerated-darron-matchlessly.ngrok-free.dev/api/apk/bkpos-v1.0.2.apk",
                'force_update' => true,
                'message' => 'Versi terbaru tersedia'
            ]);
        });
    }
}
 
