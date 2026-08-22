<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TreansactionDetailResource;
use App\Http\Resources\Api\TreansactionResource;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // public function index(Request $request)
    // {
    //     $user = $request->user();

    //     $month = $request->integer('month');
    //     $year  = $request->integer('year');

    //     $transactions = Transaction::query()
    //         ->with([
    //             'customer:id,name',
    //             // 'store:id,name',
    //         ])
    //         ->withSum('payments', 'amount')
    //         ->when(
    //             $user->store_id,
    //             fn($q) => $q->where('store_id', $user->store_id)
    //         )
    //         ->when(
    //             $year,
    //             fn($q) => $q->whereYear('created_at', $year)
    //         )
    //         ->when(
    //             $month,
    //             fn($q) => $q->whereMonth('created_at', $month)
    //         )
    //         ->latest()
    //         ->limit(
    //             $request->integer('limit', 50)
    //         )
    //         ->get();

    //     return TreansactionResource::collection($transactions);
    // }
    public function index(Request $request)
    {
        $user = $request->user();

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $transactions = Transaction::query()
            ->with([
                'customer:id,name',
            ])
            ->withSum('payments', 'amount')

            ->when(
                $user->store_id,
                fn($q) => $q->where('store_id', $user->store_id)
            )

            ->when(
                $startDate,
                fn($q) => $q->where(
                    'created_at',
                    '>=',
                    Carbon::parse($startDate)->startOfDay()
                )
            )

            ->when(
                $endDate,
                fn($q) => $q->where(
                    'created_at',
                    '<=',
                    Carbon::parse($endDate)->endOfDay()
                )
            )

            ->latest()

            ->limit(
                $request->integer('limit', 50)
            )

            ->get();

        return TreansactionResource::collection($transactions);
    }

    public function show(Transaction $transaction, Request $request)
    {
        $user = $request->user();

        if (
            $user->store_id &&
            $transaction->store_id != $user->store_id
        ) {
            abort(403, 'Akses ditolak');
        }

        $transaction->load([
            'customer',
            'store',
            'user',
            'items.product',
            'payments.user',
        ])->loadSum('payments', 'amount');

        return new TreansactionDetailResource($transaction);
    }

    public function addItem(
        Request $request,
        Transaction $transaction
    ) {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        DB::beginTransaction();

        try {

            if ($transaction->payment_status === 'paid') {
                throw new \Exception(
                    'Transaksi sudah lunas dan tidak dapat ditambah produk.'
                );
            }

            $productStore = DB::table('product_store')
                ->where('product_id', $validated['product_id'])
                ->where('store_id', $transaction->store_id)
                ->lockForUpdate()
                ->first();

            if (!$productStore) {
                throw new \Exception(
                    'Produk tidak tersedia di toko transaksi.'
                );
            }

            if ($validated['quantity'] > $productStore->stock) {
                throw new \Exception(
                    'Stok produk tidak mencukupi.'
                );
            }

            $basePrice = $productStore->price;
            $discount = $productStore->discount;

            $price = max(
                0,
                $basePrice - $discount
            );


            $transactionItem = TransactionItem::where(
                'transaction_id',
                $transaction->id
            )
                ->where(
                    'product_id',
                    $validated['product_id']
                )
                ->first();

            if ($transactionItem) {
                $newQuantity =
                    $transactionItem->quantity
                    + $validated['quantity'];

                $transactionItem->update([
                    'quantity' => $newQuantity,

                    'subtotal' =>
                    $newQuantity * $transactionItem->price,
                ]);
            } else {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'base_price' => $basePrice,
                    'price' => $price,
                    'discount' => $discount,
                    'subtotal' =>
                    $validated['quantity'] * $price,
                ]);
            }

            DB::table('product_store')
                ->where('product_id', $validated['product_id'])
                ->where('store_id', $transaction->store_id)
                ->decrement(
                    'stock',
                    $validated['quantity']
                );

            $subtotal = TransactionItem::where(
                'transaction_id',
                $transaction->id
            )->sum('subtotal');

            $transaction->subtotal = $subtotal;

            $transaction->total =
                $subtotal
                + $transaction->shipping_cost
                - ($transaction->points_used ?? 0);

            $transaction->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan ke transaksi.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'subtotal' => $transaction->subtotal,
                    'total' => $transaction->total,
                ],
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateDriver(
        Request $request,
        Transaction $transaction
    ) {
        $user = $request->user();

        if (
            $user->store_id &&
            $transaction->store_id != $user->store_id
        ) {
            abort(403, 'Akses ditolak');
        }

        // if ($transaction->payment_status !== 'paid') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Update driver khusus untuk transaksi yang sudah paid.',
        //     ], 422);
        // }

        if ($transaction->delivery_type !== 'delivery') {
            return response()->json([
                'success' => false,
                'message' => 'Driver hanya diperlukan untuk transaksi delivery.',
            ], 422);
        }

        $validated = $request->validate([
            'driver_name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $transaction->update([
            'driver_name' => $validated['driver_name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nama driver berhasil diperbarui.',
            'data' => [
                'transaction_id' => $transaction->id,
                'driver_name' => $transaction->driver_name,
            ],
        ]);
    }

    public function updateNotes(
        Request $request,
        Transaction $transaction
    ) {
        $user = $request->user();

        if (
            $user->store_id &&
            $transaction->store_id != $user->store_id
        ) {
            abort(403, 'Akses ditolak');
        }

        $validated = $request->validate([
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $transaction->update([
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan transaksi berhasil diperbarui.',
            'data' => [
                'transaction_id' => $transaction->id,
                'notes' => $transaction->notes,
            ],
        ]);
    }
}
