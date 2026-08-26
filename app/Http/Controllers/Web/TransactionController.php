<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProcessTransactionRequest;
use App\Http\Resources\Web\MutasiTransactionResource;
use App\Http\Resources\Web\TransactionDetailResource;
use App\Http\Resources\Web\TransactionResource;
use App\Models\Payment;
use App\Models\StockMutationHistory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);

        $transactions = Transaction::query()
            ->with([
                'customer',
                'store',
            ])
            ->withSum('payments', 'amount')
            ->withCount([
                'items as waiting_stock_count' => function ($query) {
                    $query->where(
                        'fulfillment_status',
                        'waiting_stock'
                    );
                },

                'items as ready_count' => function ($query) {
                    $query->where(
                        'fulfillment_status',
                        'ready'
                    );
                },

                'items as fulfilled_count' => function ($query) {
                    $query->where(
                        'fulfillment_status',
                        'fulfilled'
                    );
                },
            ])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query
                        ->where(
                            'invoice_number',
                            'like',
                            "%{$request->search}%"
                        )
                        ->orWhereHas('customer', function ($c) use ($request) {
                            $c->where(
                                'name',
                                'like',
                                "%{$request->search}%"
                            );
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('transaction/index', [
            'transactions' => TransactionResource::collection(
                $transactions
            )->response()->getData(true),

            'filters' => [
                'search' => $request->search,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'customer',
            'store',
            'user',
            'payments.user',
            'items.product.stores',
        ]);

        return Inertia::render('transaction/show', [
            'transaction' => (
                new TransactionDetailResource($transaction)
            )->resolve(),
        ]);
    }

    public function mutationDetail(Transaction $transaction)
    {
        $transaction->load([
            'store',
            'items.product.stores',
        ]);

        return response()->json([
            'data' => (
                new MutasiTransactionResource($transaction)
            )->resolve(),
        ]);
    }

    public function process(
        ProcessTransactionRequest $request,
        Transaction $transaction
    ) {
        DB::transaction(function () use (
            $request,
            $transaction
        ) {
            if ($transaction->delivery_type === 'delivery') {
                $transaction->update([
                    'driver_name' => $request->driver_name,
                ]);
            }

            $remaining =
                $transaction->total -
                $transaction->payments()->sum('amount');

            if ($request->amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Nominal pembayaran melebihi sisa tagihan.',
                    ],
                ]);
            }

            Payment::create([
                'transaction_id' => $transaction->id,
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
            ]);

            $totalPaid = $transaction
                ->payments()
                ->sum('amount');

            $transaction->refresh();

            if ($totalPaid <= 0) {
                $status = 'unpaid';
            } elseif ($totalPaid < $transaction->total) {
                $status = 'partial';
            } else {
                $status = 'paid';
            }

            $transaction->update([
                'payment_status' => $status,
            ]);
        });

        return back()->with(
            'success',
            'Transaction processed successfully'
        );
    }

    public function mutateBackorder(
        Request $request,
        Transaction $transaction
    ) {
        $validated = $request->validate([
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.transaction_item_id' => [
                'required',
                'integer',
                'distinct',
                'exists:transaction_items,id',
            ],

            'items.*.source_store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $transaction
        ) {
            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $transactionItems = TransactionItem::query()
                ->where(
                    'transaction_id',
                    $transaction->id
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $waitingStockItems = $transactionItems
                ->filter(function ($item) {
                    return $item->fulfillment_status ===
                        'waiting_stock';
                });

            if ($waitingStockItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [
                        'Tidak ada produk yang berstatus waiting_stock.'
                    ],
                ]);
            }

            $submittedItemIds = collect(
                $validated['items']
            )
                ->pluck('transaction_item_id')
                ->map(
                    fn($id) => (int) $id
                );

            $missingItems = $waitingStockItems
                ->keys()
                ->diff($submittedItemIds);

            if ($missingItems->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [
                        'Semua produk yang berstatus waiting_stock harus dimutasi.'
                    ],
                ]);
            }

            // validation frontend
            foreach ($validated['items'] as $mutation) {

                $transactionItemId = (int) $mutation['transaction_item_id'];
                $sourceStoreId = (int) $mutation['source_store_id'];

                if (!$transactionItems->has(
                    $transactionItemId
                )) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Item transaksi {$transactionItemId} tidak ditemukan pada transaksi ini."
                        ],
                    ]);
                }

                $transactionItem = $transactionItems->get($transactionItemId);

                if (
                    $transactionItem->fulfillment_status
                    !== 'waiting_stock'
                ) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Produk {$transactionItem->product_id} tidak berstatus waiting_stock."
                        ],
                    ]);
                }

                if (
                    $sourceStoreId ===
                    (int) $transaction->store_id
                ) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Source store tidak boleh sama dengan destination store.'
                        ],
                    ]);
                }

                if (
                    (int) $mutation['quantity'] <= 0
                ) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Quantity mutasi produk {$transactionItem->product_id} harus lebih dari 0."
                        ],
                    ]);
                }
            }

            // validation mutation
            foreach ($validated['items'] as $mutation) {

                $alreadyMutated =
                    StockMutationHistory::query()
                    ->where(
                        'transaction_item_id',
                        $mutation['transaction_item_id']
                    )
                    ->where(
                        'status',
                        'completed'
                    )
                    ->exists();

                if ($alreadyMutated) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Item transaksi {$mutation['transaction_item_id']} sudah pernah dimutasi."
                        ],
                    ]);
                }
            }

            $mutationRecords = [];

            foreach ($validated['items'] as $mutation) {
                $transactionItemId = (int) $mutation['transaction_item_id'];
                $sourceStoreId = (int) $mutation['source_store_id'];
                $mutationQuantity = (int) $mutation['quantity'];
                $transactionItem = $transactionItems->get($transactionItemId);
                $productId = (int) $transactionItem->product_id;
                $destinationStoreId = (int) $transaction->store_id;

                $sourceStock = DB::table('product_store')
                    ->where(
                        'product_id',
                        $productId
                    )
                    ->where(
                        'store_id',
                        $sourceStoreId
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$sourceStock) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Produk {$productId} belum terdaftar pada source store."
                        ],
                    ]);
                }

                $sourceAvailable =
                    (int) $sourceStock->stock;

                if (
                    $mutationQuantity >
                    $sourceAvailable
                ) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Stock produk {$productId} di source store tidak mencukupi. "
                                . "Tersedia: {$sourceAvailable}, "
                                . "diminta: {$mutationQuantity}."
                        ],
                    ]);
                }

                $destinationStock = DB::table('product_store')
                    ->where(
                        'product_id',
                        $productId
                    )
                    ->where(
                        'store_id',
                        $destinationStoreId
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$destinationStock) {

                    DB::table('product_store')->insert([
                        'product_id' => $productId,
                        'store_id' => $destinationStoreId,
                        'stock' => 0,
                        'price_all' => $sourceStock->price_all,
                        'price' => $sourceStock->price,
                        'discount' => $sourceStock->discount ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $destinationStock =
                        DB::table('product_store')
                        ->where(
                            'product_id',
                            $productId
                        )
                        ->where(
                            'store_id',
                            $destinationStoreId
                        )
                        ->lockForUpdate()
                        ->first();
                }

                $destinationStockBefore = (int) $destinationStock->stock;
                $transactionQuantity = (int) $transactionItem->quantity;
                $destinationStockAfter = $destinationStockBefore + $mutationQuantity;

                $destinationUsed = min($destinationStockAfter, $transactionQuantity);
                $destinationStockFinal = $destinationStockAfter - $destinationUsed;

                $mutationRecords[] = [
                    'transaction_item' => $transactionItem,
                    'product_id' => $productId,
                    'source_store_id' => $sourceStoreId,
                    'destination_store_id' => $destinationStoreId,
                    'mutation_quantity' => $mutationQuantity,
                    'transaction_quantity' => $transactionQuantity,
                    'destination_stock_before' => $destinationStockBefore,
                    'destination_used' => $destinationUsed,
                    'destination_stock_final' => $destinationStockFinal,
                ];
            }

            foreach ($mutationRecords as $record) {
                $transactionItem = $record['transaction_item'];
                $productId = $record['product_id'];
                $sourceStoreId = $record['source_store_id'];
                $destinationStoreId = $record['destination_store_id'];
                $mutationQuantity = $record['mutation_quantity'];
                $destinationStockBefore = $record['destination_stock_before'];
                $destinationUsed = $record['destination_used'];
                $destinationStockFinal = $record['destination_stock_final'];

                DB::table('product_store')
                    ->where('product_id', $productId)
                    ->where('store_id', $sourceStoreId)
                    ->decrement('stock', $mutationQuantity);

                DB::table('product_store')
                    ->where('product_id', $productId)
                    ->where('store_id', $destinationStoreId)
                    ->update([
                        'stock' => $destinationStockFinal,
                        'updated_at' => now(),
                    ]);

                StockMutationHistory::create([
                    'transaction_id' => $transaction->id,
                    'transaction_item_id' => $transactionItem->id,
                    'product_id' => $productId,
                    'source_store_id' => $sourceStoreId,
                    'destination_store_id' => $destinationStoreId,
                    'quantity' => $mutationQuantity,
                    'status' => 'completed',
                    'user_id' => Auth::id(),
                ]);

                $transactionItem->update([
                    'fulfillment_status' =>
                    'fulfilled',
                ]);
            }

            $readyItems = $transactionItems
                ->filter(function ($item) {
                    return $item->fulfillment_status === 'ready';
                });

            foreach ($readyItems as $readyItem) {

                $productId = (int) $readyItem->product_id;
                $destinationStoreId = (int) $transaction->store_id;
                $quantity = (int) $readyItem->quantity;
                $destinationStock = DB::table('product_store')
                    ->where('product_id', $productId)
                    ->where('store_id', $destinationStoreId)
                    ->lockForUpdate()
                    ->first();

                if (!$destinationStock) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Produk {$productId} berstatus ready tetapi belum terdaftar pada destination store."
                        ],
                    ]);
                }

                $availableStock = (int) $destinationStock->stock;
                if ($availableStock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Stock produk {$productId} di destination store tidak mencukupi. "
                                . "Tersedia: {$availableStock}, "
                                . "dibutuhkan: {$quantity}."
                        ],
                    ]);
                }

                DB::table('product_store')
                    ->where('product_id', $productId)
                    ->where('store_id', $destinationStoreId)
                    ->decrement('stock', $quantity);

                $readyItem->update([
                    'fulfillment_status' =>
                    'fulfilled',
                ]);
            }

            $remainingWaitingStock =
                $transaction->items()
                ->where('fulfillment_status', 'waiting_stock')
                ->exists();

            if (!$remainingWaitingStock) {
                $transaction->update([
                    'transaction_type' =>
                    'normal',
                ]);
            }
        });

        return back()->with(
            'success',
            'Mutasi stock berhasil diproses dan seluruh backorder telah dipenuhi.'
        );
    }

    public function mutationHistory(
        Transaction $transaction
    ) {
        $histories = StockMutationHistory::query()
            ->with([
                'product',
                'sourceStore',
                'destinationStore',
                'user',
                'transactionItem',
            ])
            ->where(
                'transaction_id',
                $transaction->id
            )
            ->latest()
            ->get();

        return response()->json([
            'data' => $histories,
        ]);
    }
}
