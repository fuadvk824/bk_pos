<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $soldProducts = DB::table('transaction_items')
            ->join(
                'transactions',
                'transactions.id',
                '=',
                'transaction_items.transaction_id'
            )
            ->where('transactions.store_id', $user->store_id)
            ->select(
                'transaction_items.product_id',
                DB::raw('SUM(transaction_items.quantity) as sold')
            )
            ->groupBy('transaction_items.product_id')
            ->orderByDesc(DB::raw('SUM(transaction_items.quantity)'))
            ->limit(5)
            ->pluck('sold', 'product_id');

        $products = ProductStore::query()
            ->where('store_id', $user->store_id)
            ->with([
                'product.category',
            ])
            ->get()
            ->map(function ($item) use ($soldProducts) {
                return [
                    'id' => $item->product->id,
                    'product_code' => $item->product->product_code,
                    'name' => $item->product->name,
                    'unit' => $item->product->unit,
                    'image' => $item->product->image,
                    'barcode' => $item->product->barcode,
                    'description' => $item->product->description,

                    'price' => (float) $item->price,
                    'price_all' => (float) $item->price_all,
                    'discount' => (float) $item->discount,
                    'stock' => (int) $item->stock,

                    'category' => $item->product->category?->name,

                    'sold' => (int) ($soldProducts[$item->product_id] ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    // public function scan(Request $request)
    // {
    //     $request->validate([
    //         'qr' => ['required', 'string'],
    //     ]);

    //     $user = $request->user();
    //     $qr = trim($request->qr);

    //     if (!str_starts_with($qr, 'BKPOS:PRD:')) {

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'QR bukan milik BK POS.',
    //         ], 400);
    //     }

    //     $id = (int) str_replace('BKPOS:PRD:', '', $qr);

    //     if ($id <= 0) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'QR tidak valid.',
    //         ], 400);
    //     }

    //     $sold = DB::table('transaction_items')
    //         ->join(
    //             'transactions',
    //             'transactions.id',
    //             '=',
    //             'transaction_items.transaction_id'
    //         )
    //         ->where('transactions.store_id', $user->store_id)
    //         ->where('transaction_items.product_id', $id)
    //         ->sum('transaction_items.quantity');

    //     $product = ProductStore::query()
    //         ->where('store_id', $user->store_id)
    //         ->where('product_id', $id)
    //         ->with('product.category')
    //         ->first();

    //     if (!$product) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Produk tidak ditemukan.',
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => [

    //             'id' => $product->product->id,
    //             'product_code' => $product->product->product_code,
    //             'name' => $product->product->name,
    //             'unit' => $product->product->unit,
    //             'image' => $product->product->image,
    //             'barcode' => $product->product->barcode,
    //             'description' => $product->product->description,

    //             'price' => (float) $product->price,
    //             'price_all' => (float) $product->price_all,
    //             'discount' => (float) $product->discount,
    //             'stock' => (int) $product->stock,

    //             'category' => $product->product->category?->name,

    //             'sold' => (int) $sold,
    //         ]
    //     ]);
    // }
    public function scan(Request $request)
    {
        $request->validate([
            'qr' => ['required', 'string'],
            'backorder' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $qr = trim($request->qr);
        $isBackorder = $request->boolean('backorder');

        if (!str_starts_with($qr, 'BKPOS:PRD:')) {
            return response()->json([
                'success' => false,
                'message' => 'QR bukan milik BK POS.',
            ], 400);
        }

        $id = (int) str_replace('BKPOS:PRD:', '', $qr);

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'QR tidak valid.',
            ], 400);
        }

        /*
    |--------------------------------------------------------------------------
    | BACKORDER
    |--------------------------------------------------------------------------
    |
    | Pada mode backorder, produk tidak wajib ada di product_store
    | milik store user.
    |
    */

        if ($isBackorder) {

            $product = Product::query()
                ->leftJoin('product_store', function ($join) use ($user) {
                    $join->on(
                        'products.id',
                        '=',
                        'product_store.product_id'
                    )
                        ->where(
                            'product_store.store_id',
                            '=',
                            $user->store_id
                        );
                })
                ->with('category')
                ->where('products.id', $id)
                ->select([
                    'products.id',
                    'products.product_code',
                    'products.name',
                    'products.unit',
                    'products.image',
                    'products.barcode',
                    'products.description',

                    DB::raw('COALESCE(product_store.price, 0) as price'),
                    DB::raw('COALESCE(product_store.price_all, 0) as price_all'),
                    DB::raw('COALESCE(product_store.discount, 0) as discount'),
                    DB::raw('COALESCE(product_store.stock, 0) as stock'),
                ])
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                ], 404);
            }

            $sold = DB::table('transaction_items')
                ->join(
                    'transactions',
                    'transactions.id',
                    '=',
                    'transaction_items.transaction_id'
                )
                ->where('transactions.store_id', $user->store_id)
                ->where('transaction_items.product_id', $id)
                ->sum('transaction_items.quantity');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'product_code' => $product->product_code,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'image' => $product->image,
                    'barcode' => $product->barcode,
                    'description' => $product->description,

                    'price' => (float) $product->price,
                    'price_all' => (float) $product->price_all,
                    'discount' => (float) $product->discount,

                    // Kalau belum ada di store, stock = 0
                    'stock' => (int) $product->stock,

                    'category' => $product->category?->name,

                    'sold' => (int) $sold,

                    // Informasi tambahan agar Flutter tahu ini backorder
                    'is_backorder' => true,
                ],
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | NORMAL
    |--------------------------------------------------------------------------
    |
    | Logic lama tetap dipertahankan.
    |
    */

        $sold = DB::table('transaction_items')
            ->join(
                'transactions',
                'transactions.id',
                '=',
                'transaction_items.transaction_id'
            )
            ->where('transactions.store_id', $user->store_id)
            ->where('transaction_items.product_id', $id)
            ->sum('transaction_items.quantity');

        $product = ProductStore::query()
            ->where('store_id', $user->store_id)
            ->where('product_id', $id)
            ->with('product.category')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->product->id,
                'product_code' => $product->product->product_code,
                'name' => $product->product->name,
                'unit' => $product->product->unit,
                'image' => $product->product->image,
                'barcode' => $product->product->barcode,
                'description' => $product->product->description,

                'price' => (float) $product->price,
                'price_all' => (float) $product->price_all,
                'discount' => (float) $product->discount,
                'stock' => (int) $product->stock,

                'category' => $product->product->category?->name,

                'sold' => (int) $sold,

                'is_backorder' => false,
            ],
        ]);
    }

    public function backorderSearch(Request $request)
    {
        $request->validate([
            'search' => ['required', 'string', 'min:1'],
        ]);

        $storeId = Auth::user()->store_id;
        $search = trim($request->search);

        $products = Product::query()
            ->leftJoin('product_store', function ($join) use ($storeId) {
                $join->on('products.id', '=', 'product_store.product_id')
                    ->where('product_store.store_id', $storeId);
            })
            ->where(function ($query) use ($search) {
                $query
                    ->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.product_code', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            })
            ->select([
                'products.id',
                'products.product_code',
                'products.name',
                'products.description',
                'products.unit',
                'products.image',
                'products.barcode',

                DB::raw('COALESCE(product_store.stock, 0) as stock'),
                DB::raw('COALESCE(product_store.price, 0) as price'),
                DB::raw('COALESCE(product_store.price_all, 0) as price_all'),
                DB::raw('COALESCE(product_store.discount, 0) as discount'),
            ])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
