<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreTarget;
use App\Models\Transaction;
use App\Models\Payment;

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

        $todayPayments = Payment::query()
            ->whereHas('transaction', function ($query) use ($store) {
                $query->where('store_id', $store->id);
            })
            ->whereDate('paid_at', today())
            ->selectRaw("
            SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as cash,
            SUM(CASE WHEN payment_method = 'transfer' THEN amount ELSE 0 END) as transfer,
            SUM(CASE WHEN payment_method = 'qris' THEN amount ELSE 0 END) as qris")
            ->first();

        $cash = (float) ($todayPayments->cash ?? 0);
        $transfer = (float) ($todayPayments->transfer ?? 0);
        $qris = (float) ($todayPayments->qris ?? 0);
        $paymentTotal = $cash + $transfer + $qris;

        $monthlySales = Transaction::query()
            ->where('store_id', $store->id)
            ->whereYear('created_at', now()->year)
            ->where('payment_status', 'paid')
            ->selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->groupByRaw('MONTH(created_at)')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlySalesData = collect(range(1, 12))
            ->map(function ($month) use ($monthlySales) {
                return [
                    'month' => $month,
                    'total' => (float) ($monthlySales[$month] ?? 0),
                ];
            })->values();

        return response()->json([
            'success' => true,

            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,

                'store_id' => $store->id,
                'store_view' => $store->name_view,

                'target_amount' => (float) ($target?->target_amount ?? 0),
                'today_sales' => (float) $todaySales,
                'month_sales' => (float) $monthSales,
                'today_transaction' => $todayTransaction,

                'today_payment' => [
                    'cash' => $cash,
                    'transfer' => $transfer,
                    'qris' => $qris,
                    'total' => $paymentTotal,
                ],
                'monthly_sales' => $monthlySalesData,
            ]
        ]);
    }
}
