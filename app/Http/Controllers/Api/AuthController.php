<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($request->login);
        $password = $request->password;

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if (!Auth::attempt([
                'email' => $login,
                'password' => $password,
            ])) {
                return response()->json([
                    'message' => 'Email atau password salah',
                ], 401);
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            $token = $user
                ->createToken('mobile_app')
                ->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user,
            ]);
        }

        $username = $login;
        $user = User::where('username', $username)->first();

        if ($user) {
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'message' => 'Username atau password salah',
                ], 401);
            }

            $token = $user
                ->createToken('mobile_app')
                ->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user,
            ]);
        }

        try {
            $externalResponse = Http::timeout(10)
                ->acceptJson()
                ->get(
                    'https://bmp.my.id/bk/api/get_user.php',
                    [
                        'user' => $username,
                        'pass' => $password,
                    ]
                );

            if (!$externalResponse->successful()) {
                return response()->json([
                    'message' => 'Gagal menghubungi server eksternal',
                ], 502);
            }

            $externalData = $externalResponse->json();

            if (
                !is_array($externalData) ||
                strtolower((string) ($externalData['status'] ?? '')) === 'denied'
            ) {
                return response()->json([
                    'message' => 'Username atau password salah',
                ], 401);
            }

            $whid = trim((string) ($externalData['whid'] ?? ''));

            if ($whid === '' || strtolower($whid) === 'unregistered') {
                return response()->json([
                    'message' => 'Store user tidak ditemukan dari server eksternal',
                ], 422);
            }

            $store = Store::where('store_code', $whid)->first();

            if (!$store) {
                return response()->json([
                    'message' => "Store dengan kode {$whid} belum terdaftar di sistem",
                ], 422);
            }

            $email = $this->generateUniqueEmail($username);
            $user = new User();

            $user->store_id = $store->id;
            $user->name = $username;
            $user->username = $username;
            $user->email = $email;
            $user->password = Hash::make($password);

            $user->save();
            $user->assignRole('user');

            $token = $user
                ->createToken('mobile_app')
                ->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user,
                'store' => $store,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Gagal menghubungi server login eksternal',
            ], 502);
        }
    }

    private function generateUniqueEmail(string $username): string
    {
        $username = preg_replace('/[^a-zA-Z0-9._-]/', '', $username);

        do {
            $random = Str::lower(Str::random(3));

            $email = $username . $random . '@gmail.com';
        } while (User::where('email', $email)->exists());

        return $email;
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()
            ->where('name', 'like', 'mobile_%')
            ->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->key_status = 'old';

        $user->save();

        return response()->json([
            'message' => 'Password berhasil diupdate',
        ]);
    }
}
 