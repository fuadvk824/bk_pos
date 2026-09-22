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
                'product.stores',
            ])
            ->get()
            ->map(function ($item) use ($soldProducts) {

                $stores = $item->product->stores
                    ->map(function ($store) {
                        return [
                            'id' => $store->id,
                            'name' => $store->name_view,
                            'stock' => (int) $store->pivot->stock,
                            'conv2' => (int) $store->pivot->conv2,
                        ];
                    })
                    ->values()
                    ->toArray();

                return [
                    'id' => $item->product->id,
                    'product_code' => $item->product->product_code,
                    'name' => $item->product->name,
                    'unit' => $item->product->unit,
                    'unit2' => $item->product->unit2,
                    'image' => $item->product->image,
                    'barcode' => $item->product->barcode,
                    'description' => $item->product->description,

                    // Store yang sedang login
                    'price' => (float) $item->price,
                    'discount' => (float) $item->discount,
                    'stock' => (int) $item->stock,
                    'conv2' => (int) $item->conv2,

                    'category' => $item->product->category?->name,
                    'sold' => (int) ($soldProducts[$item->product_id] ?? 0),

                    // Semua store
                    'stores' => $stores,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

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

        if ($isBackorder) {
            $product = Product::query()
                ->leftJoin('product_store', function ($join) use ($user) {
                    $join->on('products.id', '=', 'product_store.product_id')
                        ->where('product_store.store_id', '=', $user->store_id);
                })
                ->with([
                    'category',
                    'stores',
                ])
                ->where('products.id', $id)
                ->select([
                    'products.id',
                    'products.product_code',
                    'products.name',
                    'products.unit',
                    'products.unit2',
                    'products.image',
                    'products.barcode',
                    'products.description',

                    DB::raw('COALESCE(product_store.price, 0) as price'),
                    DB::raw('COALESCE(product_store.price_all, 0) as price_all'),
                    DB::raw('COALESCE(product_store.discount, 0) as discount'),
                    DB::raw('COALESCE(product_store.stock, 0) as stock'),
                    DB::raw('COALESCE(product_store.conv2, 0) as conv2'),
                ])
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                ], 404);
            }

            $sold = DB::table('transaction_items')
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->where('transactions.store_id', $user->store_id)
                ->where('transaction_items.product_id', $id)
                ->sum('transaction_items.quantity');

            $stores = $product->stores
                ->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->name_view,
                        'stock' => (int) $store->pivot->stock,
                        'conv2' => (int) $store->pivot->conv2,
                    ];
                })
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'product_code' => $product->product_code,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'unit2' => $product->unit2,
                    'image' => $product->image,
                    'barcode' => $product->barcode,
                    'description' => $product->description,

                    'price' => (float) $product->price,
                    'price_all' => (float) $product->price_all,
                    'discount' => (float) $product->discount,
                    'stock' => (int) $product->stock,
                    'conv2' => (int) $product->conv2,

                    'category' => $product->category?->name,
                    'sold' => (int) $sold,

                    'is_backorder' => true,
                    'stores' => $stores,
                ],
            ]);
        }

        $sold = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
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

        $stores = $product->product->stores
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name_view,
                    'stock' => (int) $store->pivot->stock,
                    'conv2' => (int) $store->pivot->conv2,
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->product->id,
                'product_code' => $product->product->product_code,
                'name' => $product->product->name,
                'unit' => $product->product->unit,
                'unit2' => $product->product->unit2,
                'image' => $product->product->image,
                'barcode' => $product->product->barcode,
                'description' => $product->product->description,

                'price' => (float) $product->price,
                'price_all' => (float) $product->price_all,
                'discount' => (float) $product->discount,
                'stock' => (int) $product->stock,
                'conv2' => (int) $product->conv2,

                'category' => $product->product->category?->name,
                'sold' => (int) $sold,
                'is_backorder' => false,
                'stores' => $stores,
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
                $join->on(
                    'products.id',
                    '=',
                    'product_store.product_id'
                )
                    ->where(
                        'product_store.store_id',
                        $storeId
                    );
            })

            ->with([
                'category',
                'stores',
            ])

            ->where(function ($query) use ($search) {
                $query
                    ->where('products.name', 'like', "%{$search}%")
                    ->orWhere(
                        'products.product_code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'products.barcode',
                        'like',
                        "%{$search}%"
                    );
            })

            ->select([
                'products.id',
                'products.product_code',
                'products.name',
                'products.description',
                'products.unit',
                'products.unit2',
                'products.image',
                'products.barcode',

                // Current store
                DB::raw(
                    'COALESCE(product_store.stock, 0) as stock'
                ),
                DB::raw(
                    'COALESCE(product_store.conv2, 0) as conv2'
                ),
                DB::raw(
                    'COALESCE(product_store.price, 0) as price'
                ),
                DB::raw(
                    'COALESCE(product_store.price_all, 0) as price_all'
                ),
                DB::raw(
                    'COALESCE(product_store.discount, 0) as discount'
                ),
            ])

            ->limit(20)
            ->get();

        $products = $products->map(function ($product) {
            $stores = $product->stores
                ->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->name_view,
                        'stock' => (int) $store->pivot->stock,
                        'conv2' => (int) $store->pivot->conv2,
                    ];
                })
                ->values()
                ->toArray();

            return [
                'id' => $product->id,
                'product_code' => $product->product_code,
                'name' => $product->name,
                'description' => $product->description,
                'unit' => $product->unit,
                'unit2' => $product->unit2,
                'image' => $product->image,
                'barcode' => $product->barcode,

                // Current store
                'stock' => (int) $product->stock,
                'conv2' => (int) $product->conv2,
                'price' => (float) $product->price,
                'price_all' => (float) $product->price_all,
                'discount' => (float) $product->discount,

                'category' => $product->category?->name,

                // Semua store
                'stores' => $stores,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
