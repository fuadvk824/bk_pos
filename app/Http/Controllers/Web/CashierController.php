<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Payment;
use App\Models\PointSetting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CashierController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->store_id;

        $products = [];

        if ($storeId) {
            $products = Product::query()
                ->whereHas('stores', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                })
                ->with(['stores' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                }])
                ->get()
                ->map(function ($product) {
                    $pivot = $product->stores->first()?->pivot;

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'product_code' => $product->product_code,
                        'price' => (float) ($pivot->price ?? 0),
                        'discount' => (float) ($pivot->discount ?? 0),
                        'final_price' => max(
                            0,
                            (float) ($pivot->price ?? 0) -
                                (float) ($pivot->discount ?? 0)
                        ),
                        'stock' => (int) ($pivot->stock ?? 0),
                    ];
                })
                ->values();
        }

        $customerSearch = $request->customer_search;

        $customers = Customer::query()
            ->when($customerSearch, function ($q) use ($customerSearch) {
                $q->where(function ($query) use ($customerSearch) {
                    $query->where('name', 'like', "%{$customerSearch}%")
                        ->orWhere('phone', 'like', "%{$customerSearch}%");
                });
            })
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'name',
                'phone',
                'address',
                'current_point',
            ]);

        return Inertia::render('cashier/index', [
            'stores' => Store::select('id', 'name')->get(),
            'products' => $products,

            'customers' => $customers,

            'filters' => [
                'store_id' => $storeId,
                'customer_search' => $customerSearch,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],

            'delivery_type' => ['required', 'in:pickup,delivery'],
            'points_used' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],

            'notes' => ['nullable', 'string'],

            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,transfer,qris'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],

            'subtotal' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
        ]);

        return DB::transaction(function () use ($validated) {

            $customer = null;

            if (!empty($validated['phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $validated['phone']],
                    [
                        'name' => $validated['name'] ?? 'Customer',
                        'address' => $validated['address'] ?? null,
                    ]
                );
            }

            if ($validated['payment_status'] === 'paid') {

                if (empty($validated['payment_amount'])) {
                    throw new \Exception('Nominal pembayaran wajib diisi');
                }

                if ($validated['payment_amount'] < $validated['total']) {
                    throw new \Exception('Nominal pembayaran kurang dari total transaksi');
                }
            }

            if ($validated['payment_status'] === 'partial') {

                if (empty($validated['payment_amount'])) {
                    throw new \Exception('Nominal DP wajib diisi');
                }

                if (
                    $validated['payment_amount'] <= 0 ||
                    $validated['payment_amount'] >= $validated['total']
                ) {
                    throw new \Exception('Nominal DP tidak valid');
                }
            }

            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . now()->format('YmdHis'),
                'user_id' => Auth::id(),
                'store_id' => $validated['store_id'],
                'customer_id' => $customer?->id,

                'subtotal' => $validated['subtotal'],
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'points_used' => $validated['points_used'] ?? 0,
                'total' => $validated['total'],

                'payment_status' => $validated['payment_status'],
                'delivery_type' => $validated['delivery_type'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {

                $productStore = DB::table('product_store')
                    ->where('product_id', $item['id'])
                    ->where('store_id', $validated['store_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$productStore) {
                    throw new \Exception("Produk tidak tersedia di store");
                }

                if ($item['qty'] > $productStore->stock) {
                    throw new \Exception("Stock tidak cukup untuk produk ID {$item['id']}");
                }

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['qty'],
                    'base_price' => $productStore->price,
                    'price' => $item['price'],
                    'discount' => $productStore->discount,
                    'subtotal' => $item['qty'] * $item['price'],
                ]);

                DB::table('product_store')
                    ->where('product_id', $item['id'])
                    ->where('store_id', $validated['store_id'])
                    ->decrement('stock', $item['qty']);
            }
            if (
                in_array($validated['payment_status'], ['paid', 'partial']) &&
                !empty($validated['payment_amount'])
            ) {
                Payment::create([
                    'transaction_id' => $transaction->id,
                    'user_id' => Auth::id(),
                    'amount' => $validated['payment_amount'],
                    'payment_method' => $validated['payment_method'],
                ]);
            }

            if ($transaction->customer_id) {

                $customer = Customer::lockForUpdate()->find($transaction->customer_id);

                $pointSetting = PointSetting::where('is_active', true)->first();

                if (
                    $transaction->points_used > 0 &&
                    $customer &&
                    $customer->current_point >= $transaction->points_used
                ) {

                    $customer->decrement(
                        'current_point',
                        $transaction->points_used
                    );

                    CustomerPoint::create([
                        'customer_id' => $customer->id,
                        'points' => $transaction->points_used,
                        'type' => 'redeem',
                        'reference' => $transaction->invoice_number,
                    ]);
                }

                if (
                    $pointSetting &&
                    $transaction->subtotal >= $pointSetting->minimum_transaction
                ) {

                    $earnedPoints = floor(
                        $transaction->subtotal / $pointSetting->spend_amount
                    ) * $pointSetting->point_reward;

                    if ($earnedPoints > 0) {

                        $customer->increment(
                            'current_point',
                            $earnedPoints
                        );

                        CustomerPoint::create([
                            'customer_id' => $customer->id,
                            'points' => $earnedPoints,
                            'type' => 'earn',
                            'reference' => $transaction->invoice_number,
                        ]);
                    }
                }
            }

            return back()->with('success', 'Transaksi berhasil disimpan');
        });
    }
}
