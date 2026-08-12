<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductStore;
use Illuminate\Http\Request;
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
    public function scan(Request $request)
    {
        $request->validate([
            'qr' => ['required', 'string'],
        ]);

        $user = $request->user();
        $qr = trim($request->qr);

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
            ]
        ]);
    }
}
