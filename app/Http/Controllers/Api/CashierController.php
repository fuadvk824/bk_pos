<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Payment;
use App\Models\PointSetting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([

            // 'store_id' => ['required', 'exists:stores,id'],

            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],

            'delivery_type' => ['required', 'in:pickup,delivery'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'points_used' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'payment_method' => ['nullable', 'in:cash,transfer,qris'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],

            'subtotal' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],

        ]);
        $storeId = Auth::user()->store_id;

        try {
            DB::beginTransaction();
            $customer = Customer::firstOrCreate(
                ['phone' => $validated['phone']],
                [
                    'name' => $validated['name'],
                    'address' => $validated['address'] ?? null
                ]
            );

            if ($validated['payment_status'] == "paid") {
                if (empty($validated['payment_amount']))
                    throw new \Exception("Nominal pembayaran wajib diisi");
                if ($validated['payment_amount'] < $validated['total'])
                    throw new \Exception("Pembayaran kurang dari total");
            }

            if ($validated['payment_status'] == "partial") {
                if (empty($validated['payment_amount']))
                    throw new \Exception("Nominal DP wajib diisi");
                if (
                    $validated['payment_amount'] <= 0 ||
                    $validated['payment_amount'] >= $validated['total']
                ) {
                    throw new \Exception("Nominal DP tidak valid");
                }
            }

            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . now()->format('YmdHis'),
                'user_id' => Auth::id(),
                'store_id' => $storeId,
                'customer_id' => $customer->id,
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
                    ->where('store_id', $storeId)
                    ->lockForUpdate()
                    ->first();

                if (!$productStore)
                    throw new \Exception("Produk tidak tersedia");
                if ($item['qty'] > $productStore->stock)
                    throw new \Exception("Stock {$item['id']} tidak cukup");

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['qty'],
                    'base_price' => $productStore->price,
                    'price' => $item['price'],
                    'discount' => $productStore->discount,
                    'subtotal' => $item['qty'] * $item['price']
                ]);

                DB::table('product_store')
                    ->where('product_id', $item['id'])
                    ->where('store_id', $storeId)
                    ->decrement('stock', $item['qty']);
            }

            if (
                in_array($validated['payment_status'], ['paid', 'partial'])
                && !empty($validated['payment_amount'])
            ) {

                Payment::create([
                    'transaction_id' => $transaction->id,
                    'user_id' => Auth::id(),
                    'amount' => $validated['payment_amount'],
                    'payment_method' => $validated['payment_method']
                ]);
            }

            $customer = Customer::lockForUpdate()->find($customer->id);
            // $setting = PointSetting::where('is_active', true)->first();

            if (
                $transaction->points_used > 0 &&
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
                    'reference' => $transaction->invoice_number
                ]);
            }
            //tambahan 
            if ($transaction->payment_status === 'paid') {
                $this->giveCustomerPoint($transaction);
            }
            //tambahan

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '   ',
                'invoice' => $transaction->invoice_number,
                'transaction_id' => $transaction->id
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function search(Request $request)
    {
        $search = $request->search;

        return Customer::query()
            ->when($search, function ($q) use ($search) {

                $q->where(function ($query) use ($search) {

                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
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
    }

    public function addPayment(Request $request, Transaction $transaction)
    {
        if ($transaction->payment_status == "paid") {
            return response()->json([
                "success" => false,
                "message" => "Transaksi sudah lunas."
            ], 422);
        }
        $validated = $request->validate([

            // 'driver_name' => ['nullable', 'string', 'max:255'],
            // 'delivery_type' => ['nullable', 'in:pickup,delivery'],
            // 'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'delivery_type' => ['required', 'in:pickup,delivery'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => [
                'required',
                'in:cash,transfer,qris'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1'
            ],
        ]);
        if (
            $validated['delivery_type'] === 'delivery'
            && empty($validated['driver_name'])
        ) {
            throw new \Exception("Driver wajib diisi.");
        }

        if (
            $validated['delivery_type'] === 'delivery'
            && ($validated['shipping_cost'] ?? 0) < 0
        ) {
            throw new \Exception("Ongkir tidak valid.");
        }

        DB::beginTransaction();

        try {

            // if ($transaction->payment_status == "unpaid") {

            //     if (!empty($validated['delivery_type'])) {
            //         logger('delivery_type unpaid');
            //         $transaction->delivery_type =
            //             $validated['delivery_type'];
            //     }
            //     if (
            //         $transaction->delivery_type == "delivery"
            //     ) {
            //         logger('delivery unpaid');
            //         $transaction->shipping_cost =
            //             $validated['shipping_cost'] ?? 0;
            //     } else {
            //         logger('else unpaid');
            //         $transaction->shipping_cost = 0;
            //     }

            //     $transaction->total =
            //         $transaction->subtotal +
            //         $transaction->shipping_cost -
            //         ($transaction->points_used ?? 0);
            // }
            // Delivery Type boleh diubah selama transaksi belum lunas
            $transaction->delivery_type = $validated['delivery_type'];

            if ($validated['delivery_type'] === 'delivery') {

                $transaction->shipping_cost =
                    $validated['shipping_cost'] ?? 0;

                $transaction->driver_name =
                    $validated['driver_name'] ?? null;
            } else {

                $transaction->shipping_cost = 0;

                // pickup -> driver harus dikosongkan
                $transaction->driver_name = null;
            }

            // hitung ulang total
            $transaction->total =
                $transaction->subtotal +
                $transaction->shipping_cost -
                ($transaction->points_used ?? 0);

            // if (!empty($validated['driver_name'])) {
            //     $transaction->driver_name =
            //         $validated['driver_name'];
            // }

            $paid = $transaction
                ->payments()
                ->sum('amount');

            $remaining =
                $transaction->total - $paid;

            if ($validated['amount'] > $remaining) {
                throw new \Exception(
                    "Nominal pembayaran melebihi sisa tagihan."
                );
            }

            Payment::create([
                'transaction_id' => $transaction->id,
                'user_id' => Auth::id(),
                'payment_method' =>
                $validated['payment_method'],
                'amount' => $validated['amount']

            ]);

            $paid = $transaction
                ->payments()
                ->sum('amount');

            if ($paid <= 0) {
                $transaction->payment_status = "unpaid";
            } elseif ($paid < $transaction->total) {
                $transaction->payment_status = "partial";
            } else {
                $transaction->payment_status = "paid";
            }

            $transaction->save();


            if ($transaction->payment_status === 'paid') {
                $this->giveCustomerPoint($transaction);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    private function giveCustomerPoint(Transaction $transaction): void
    {
        if (
            CustomerPoint::where('reference', $transaction->invoice_number)
            ->where('type', 'earn')
            ->exists()
        ) {
            logger('CustomerPoint earn');
            return;
        }
        logger('giveCustomerPoint dipanggil');

        $customer = Customer::lockForUpdate()->find($transaction->customer_id);

        if (!$customer) {
            return;
        }

        $setting = PointSetting::where('is_active', true)->first();

        if (
            !$setting ||
            $transaction->subtotal < $setting->minimum_transaction
        ) {
            return;
        }

        $earned = floor(
            $transaction->subtotal / $setting->spend_amount
        ) * $setting->point_reward;

        if ($earned <= 0) {
            return;
        }

        $customer->increment('current_point', $earned);

        CustomerPoint::create([
            'customer_id' => $customer->id,
            'points' => $earned,
            'type' => 'earn',
            'reference' => $transaction->invoice_number,
        ]);
    }
}

