<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProcessTransactionRequest;
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
            // ==========================================
            // COUNT FULFILLMENT STATUS
            // ==========================================
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
            'customer',
            'store',
            'user',
            'payments.user',
            'items.product.stores',
        ]);

        return response()->json([
            'data' => (
                new TransactionDetailResource($transaction)
            )->resolve(),
        ]);
    }

    // public function show(Transaction $transaction)
    // {
    //     $transaction->load([
    //         'customer',
    //         'store',
    //         'user',
    //         'payments.user',
    //         'items.product',
    //     ]);

    //     return Inertia::render('transaction/show', [
    //         'transaction' => (
    //             new TransactionDetailResource($transaction)
    //         )->resolve(),
    //     ]);
    // }

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

    /**
     * ==========================================================
     * MUTATE BACKORDER
     *
     * Mendukung:
     * - Banyak transaction item
     * - Source store berbeda per item
     * - Quantity berbeda per item
     * - Satu transaksi database
     * - Stock locking
     * - History mutasi
     * ==========================================================
     *
     * Request:
     *
     * {
     *     "items": [
     *         {
     *             "transaction_item_id": 10,
     *             "source_store_id": 2,
     *             "quantity": 3
     *         },
     *         {
     *             "transaction_item_id": 11,
     *             "source_store_id": 4,
     *             "quantity": 1
     *         }
     *     ]
     * }
     */


    public function mutateBackorder(
        Request $request,
        Transaction $transaction
    ) {
        // ==========================================================
        // VALIDASI REQUEST
        //
        // Frontend tetap mengirim items.
        // Quantity dari frontend hanya sebagai informasi.
        // Quantity final dihitung ulang oleh backend.
        // ==========================================================

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
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $transaction
        ) {

            // ======================================================
            // LOCK TRANSACTION
            // ======================================================

            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            // ======================================================
            // AMBIL SEMUA ITEM TRANSAKSI + LOCK
            // ======================================================

            $transactionItems = TransactionItem::query()
                ->where(
                    'transaction_id',
                    $transaction->id
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // ======================================================
            // AMBIL ITEM WAITING STOCK
            // ======================================================

            $waitingStockItems = $transactionItems
                ->filter(function ($item) {
                    return $item->fulfillment_status ===
                        'waiting_stock';
                });

            // ======================================================
            // HARUS ADA WAITING STOCK
            // ======================================================

            if ($waitingStockItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [
                        'Tidak ada produk yang berstatus waiting_stock.'
                    ],
                ]);
            }

            // ======================================================
            // ITEM YANG DIKIRIM FRONTEND
            // ======================================================

            $submittedItemIds = collect(
                $validated['items']
            )
                ->pluck('transaction_item_id')
                ->map(fn($id) => (int) $id);

            // ======================================================
            // PASTIKAN SEMUA WAITING STOCK DIKIRIM
            // ======================================================

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

            // ======================================================
            // CEK ITEM YANG DIKIRIM
            // ======================================================

            foreach ($validated['items'] as $mutation) {

                $transactionItemId =
                    (int) $mutation['transaction_item_id'];

                // --------------------------------------------------
                // Item harus milik transaction ini
                // --------------------------------------------------

                if (!$transactionItems->has($transactionItemId)) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Item transaksi {$transactionItemId} tidak ditemukan pada transaksi ini."
                        ],
                    ]);
                }

                $transactionItem =
                    $transactionItems->get(
                        $transactionItemId
                    );

                // --------------------------------------------------
                // Hanya waiting_stock yang boleh dimutasi
                // --------------------------------------------------

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

                // --------------------------------------------------
                // Source tidak boleh destination
                // --------------------------------------------------

                if (
                    (int) $mutation['source_store_id']
                    === (int) $transaction->store_id
                ) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Source store tidak boleh sama dengan destination store.'
                        ],
                    ]);
                }
            }

            // ======================================================
            // CEK DUPLIKAT MUTASI
            //
            // Satu transaction item hanya boleh diselesaikan sekali.
            // ======================================================

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

            // ======================================================
            // PREPARE MUTATIONS
            //
            // Kita hitung dulu:
            //
            // transaction quantity
            // destination stock
            // destination used
            // shortage dari source
            //
            // Belum mengubah stock.
            // ======================================================

            $mutationRecords = [];

            foreach ($validated['items'] as $mutation) {

                $transactionItem =
                    $transactionItems->get(
                        (int) $mutation['transaction_item_id']
                    );

                $productId =
                    $transactionItem->product_id;

                $sourceStoreId =
                    (int) $mutation['source_store_id'];

                $destinationStoreId =
                    (int) $transaction->store_id;

                // ==================================================
                // LOCK SOURCE STOCK
                // ==================================================

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
                            "Produk {$productId} tidak tersedia di source store."
                        ],
                    ]);
                }

                // ==================================================
                // LOCK DESTINATION STOCK
                // ==================================================

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

                // ==================================================
                // QUANTITY TRANSAKSI
                //
                // JANGAN menggunakan quantity dari frontend.
                // ==================================================

                $transactionQuantity =
                    (int) $transactionItem->quantity;

                // ==================================================
                // STOCK DESTINATION SAAT INI
                // ==================================================

                $destinationAvailable =
                    (int) (
                        $destinationStock?->stock ?? 0
                    );

                // ==================================================
                // BERAPA STOCK DESTINATION YANG BISA DIPAKAI?
                // ==================================================

                $destinationUsed = min(
                    $destinationAvailable,
                    $transactionQuantity
                );

                // ==================================================
                // KEKURANGAN YANG HARUS DIAMBIL SOURCE
                // ==================================================

                $shortageQuantity =
                    $transactionQuantity -
                    $destinationUsed;

                // ==================================================
                // VALIDASI SOURCE STOCK
                // ==================================================

                if (
                    $sourceStock->stock <
                    $shortageQuantity
                ) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Stock produk {$productId} di source store tidak mencukupi. "
                                . "Tersedia: {$sourceStock->stock}, "
                                . "dibutuhkan: {$shortageQuantity}."
                        ],
                    ]);
                }

                // ==================================================
                // SIMPAN DATA UNTUK TAHAP MUTASI
                //
                // BELUM UPDATE STOCK.
                // ==================================================

                $mutationRecords[] = [
                    'transaction_item' =>
                    $transactionItem,

                    'product_id' =>
                    $productId,

                    'source_store_id' =>
                    $sourceStoreId,

                    'destination_store_id' =>
                    $destinationStoreId,

                    'transaction_quantity' =>
                    $transactionQuantity,

                    'destination_available' =>
                    $destinationAvailable,

                    'destination_used' =>
                    $destinationUsed,

                    'shortage_quantity' =>
                    $shortageQuantity,

                    'source_stock' =>
                    $sourceStock,

                    'destination_stock' =>
                    $destinationStock,
                ];
            }

            // ======================================================
            // SEMUA VALIDASI STOCK BERHASIL
            //
            // BARU SEKARANG UPDATE STOCK.
            // ======================================================

            foreach ($mutationRecords as $record) {

                $transactionItem =
                    $record['transaction_item'];

                $productId =
                    $record['product_id'];

                $sourceStoreId =
                    $record['source_store_id'];

                $destinationStoreId =
                    $record['destination_store_id'];

                $destinationUsed =
                    $record['destination_used'];

                $shortageQuantity =
                    $record['shortage_quantity'];

                // ==================================================
                // 1. KURANGI SOURCE
                //
                // HANYA SEBESAR KEKURANGAN.
                // ==================================================

                if ($shortageQuantity > 0) {

                    DB::table('product_store')
                        ->where(
                            'product_id',
                            $productId
                        )
                        ->where(
                            'store_id',
                            $sourceStoreId
                        )
                        ->decrement(
                            'stock',
                            $shortageQuantity
                        );
                }

                // ==================================================
                // 2. KONSUMSI STOCK DESTINATION
                //
                // Destination stock tidak ditambah.
                //
                // Stock yang sudah ada digunakan untuk transaksi.
                // ==================================================

                if ($destinationUsed > 0) {

                    DB::table('product_store')
                        ->where(
                            'product_id',
                            $productId
                        )
                        ->where(
                            'store_id',
                            $destinationStoreId
                        )
                        ->decrement(
                            'stock',
                            $destinationUsed
                        );
                }

                // ==================================================
                // 3. SIMPAN HISTORY
                //
                // quantity = stock yang benar-benar diambil
                // dari source store.
                // ==================================================

                StockMutationHistory::create([
                    'transaction_id' =>
                    $transaction->id,

                    'transaction_item_id' =>
                    $transactionItem->id,

                    'product_id' =>
                    $productId,

                    'source_store_id' =>
                    $sourceStoreId,

                    'destination_store_id' =>
                    $destinationStoreId,

                    'quantity' =>
                    $shortageQuantity,

                    'status' =>
                    'completed',

                    'user_id' =>
                    Auth::id(),
                ]);

                // ==================================================
                // 4. WAITING STOCK -> FULFILLED
                // ==================================================

                $transactionItem->update([
                    'fulfillment_status' =>
                    'fulfilled',
                ]);
            }

            // ======================================================
            // READY -> FULFILLED
            //
            // Item ready berarti stock destination sudah tersedia
            // dan sekarang dianggap selesai.
            // ======================================================

            $transaction->items()
                ->where(
                    'fulfillment_status',
                    'ready'
                )
                ->update([
                    'fulfillment_status' =>
                    'fulfilled',
                ]);

            // ======================================================
            // CEK APAKAH MASIH ADA WAITING STOCK
            // ======================================================

            $remainingWaitingStock =
                $transaction->items()
                ->where(
                    'fulfillment_status',
                    'waiting_stock'
                )
                ->exists();

            // ======================================================
            // SEMUA ITEM SUDAH TERPENUHI
            // ======================================================

            if (!$remainingWaitingStock) {

                $transaction->update([
                    'transaction_type' =>
                    'normal',
                ]);
            }
        });

        // ==========================================================
        // RESPONSE
        // ==========================================================

        return back()->with(
            'success',
            'Mutasi stock berhasil diproses dan seluruh backorder telah dipenuhi.'
        );
    }

    /**
     * ==========================================================
     * MUTATION HISTORY
     * ==========================================================
     */
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
