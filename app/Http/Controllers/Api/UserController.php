<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreTarget;
use App\Models\Transaction;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->store) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak memiliki store'
            ], 403);
        }

        $store = $user->store;

        $target = StoreTarget::where('store_id', $store->id)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        $todaySales = Transaction::where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('total');

        $monthSales = Transaction::where('store_id', $store->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('payment_status', 'paid')
            ->sum('total');

        $todayTransaction = Transaction::where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,

                'store_id' => $store->id,
                'store_name' => $store->name,
                'store_view' => $store->name_view,
                'store_code' => $store->store_code,

                'target_amount' => (float) ($target?->target_amount ?? 0),
                'today_sales' => (float) $todaySales,
                'month_sales' => (float) $monthSales,
                'today_transaction' => $todayTransaction,
            ]
        ]);
    }
}
