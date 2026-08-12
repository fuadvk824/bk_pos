<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BmpAuthService
{
    protected string $baseUrl = 'https://bmp.my.id/bk/api/get_user.php';

    public function verify(string $username, string $password): ?array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get($this->baseUrl, [
                    'user' => $username,
                    'pass' => $password,
                ]);

            if (!$response->ok()) {
                return null;
            }

            $data = $response->json();

            if (!$this->isVerified($data)) {
                return null;
            }

            return [
                'status' => 'verified',
                'whid'   => $this->resolveWhid($data),
                'note'   => $data['note'] ?? null,
            ];

        } catch (\Throwable $e) {
            // optional: log error
            report($e);
            return null;
        }
    }

    /**
     * Validasi response API
     */
    protected function isVerified(?array $data): bool
    {
        return isset($data['status']) && $data['status'] === 'verified';
    }

    /**
     * Extract WHID dengan clean logic
     */
    protected function resolveWhid(array $data): ?string
    {
        $whid = $data['whid'] ?? null;

        if ($whid && $whid !== 'unregistered') {
            return $whid;
        }

        // fallback ambil dari note
        return $this->extractWhidFromNote($data['note'] ?? '');
    }

    /**
     * Parse WHID dari string note
     * contoh: "role: GSK01 (Gresik Kepatihan)"
     */
    protected function extractWhidFromNote(string $note): ?string
    {
        if (Str::of($note)->contains('role:')) {
            preg_match('/role:\s*([A-Z0-9]+)/', $note, $matches);
            return $matches[1] ?? null;
        }

        return null;
    }
}