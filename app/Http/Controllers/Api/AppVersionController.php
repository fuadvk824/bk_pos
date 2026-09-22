<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    public function latest(): JsonResponse
    {
        $version = AppVersion::query()
            ->where('force_update', true)
            ->latest('id')
            ->first();

        if (!$version) {
            return response()->json([
                'message' => 'Versi aplikasi belum tersedia',
            ], 404);
        }

        return response()->json([
            'version' => $version->version,
            'apk_url' => $version->apk_url,
            'force_update' => $version->force_update,
            'message' => $version->message,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
    
}