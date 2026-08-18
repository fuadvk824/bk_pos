<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class MutasiProdukController extends Controller
{
    /**
     * Halaman transfer stok.
     */
    public function index()
    {
        $stores = Store::query()
            ->select('id', 'store_code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('transfer-stock/index', [
            'stores' => $stores,
        ]);
    }

    /**
     * Ambil produk yang tersedia di store asal.
     */
    public function products(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $products = Product::query()
            ->select([
                'products.id',
                'products.product_code',
                'products.name',
                'products.unit',
                'products.image',
            ])
            ->join(
                'product_store',
                'products.id',
                '=',
                'product_store.product_id'
            )
            ->where(
                'product_store.store_id',
                $validated['store_id']
            )
            ->where(
                'product_store.stock',
                '>',
                0
            )
            ->selectRaw('product_store.stock as available_stock')
            ->orderBy('products.name')
            ->get();

        return response()->json($products);
    }

    /**
     * Simpan transfer stok.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_store_id' => [
                'required',
                'integer',
                'exists:stores,id',
                'different:destination_store_id',
            ],

            'destination_store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        try {

            DB::transaction(function () use ($validated) {

                foreach ($validated['items'] as $item) {

                    $productId = $item['product_id'];
                    $quantity = (int) $item['quantity'];

                    /*
                     * Ambil stok store asal.
                     *
                     * lockForUpdate() penting supaya dua proses
                     * transfer bersamaan tidak merusak stok.
                     */
                    $source = DB::table('product_store')
                        ->where('product_id', $productId)
                        ->where(
                            'store_id',
                            $validated['source_store_id']
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$source) {
                        throw new \Exception(
                            "Produk ID {$productId} tidak tersedia di store asal."
                        );
                    }

                    /*
                     * Pastikan stok mencukupi.
                     */
                    if ($source->stock < $quantity) {
                        $product = Product::find($productId);

                        throw new \Exception(
                            "Stock produk {$product->name} tidak cukup. "
                            . "Tersedia {$source->stock}, "
                            . "diminta {$quantity}."
                        );
                    }

                    /*
                     * Kurangi stock store asal.
                     */
                    DB::table('product_store')
                        ->where('id', $source->id)
                        ->update([
                            'stock' => $source->stock - $quantity,
                            'updated_at' => now(),
                        ]);

                    /*
                     * Cek apakah produk sudah ada di store tujuan.
                     */
                    $destination = DB::table('product_store')
                        ->where('product_id', $productId)
                        ->where(
                            'store_id',
                            $validated['destination_store_id']
                        )
                        ->lockForUpdate()
                        ->first();

                    if ($destination) {

                        /*
                         * Produk sudah ada.
                         * Tinggal tambahkan stock.
                         */
                        DB::table('product_store')
                            ->where('id', $destination->id)
                            ->update([
                                'stock' => $destination->stock + $quantity,
                                'updated_at' => now(),
                            ]);

                    } else {

                        /*
                         * Produk belum ada di store tujuan.
                         * Buat record baru.
                         *
                         * Harga mengikuti store asal.
                         */
                        DB::table('product_store')->insert([
                            'product_id' => $productId,
                            'store_id' => $validated['destination_store_id'],
                            'stock' => $quantity,
                            'price_all' => $source->price_all,
                            'price' => $source->price,
                            'discount' => $source->discount,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    /*
                     * Update stock_all.
                     */
                    $totalStock = DB::table('product_store')
                        ->where('product_id', $productId)
                        ->sum('stock');

                    Product::where('id', $productId)
                        ->update([
                            'stock_all' => $totalStock,
                            'updated_at' => now(),
                        ]);
                }
            });

            return redirect()
                ->route('transfer-stock.index')
                ->with(
                    'success',
                    'Transfer stok berhasil dilakukan.'
                );

        } catch (Throwable $e) {

            return back()
                ->withInput()
                ->withErrors([
                    'transfer' => $e->getMessage(),
                ]);
        }
    }
}