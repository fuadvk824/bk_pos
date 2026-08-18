<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\ProductDetailResource;
use App\Http\Resources\Web\ProductResource;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);

        $products = Product::with('category')
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('product_code', 'like', '%' . $request->search . '%');
            })
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('product/index', [
            'products' => ProductResource::collection($products)->response()->getData(true),
            'filters' => [
                'search' => $request->search,
                'perPage' => $perPage,
            ],
        ]);
    }

    // public function show(Product $product)
    // {
    //     $product->load(['category', 'stores']);
    //     return Inertia::render('product/show', [
    //         'product' => (new ProductDetailResource($product))->resolve(),
    //     ]);
    // }
    public function show(Product $product)
    {
        $product->load(['category', 'stores']);

        $stores = Store::query()->select(['id', 'store_code', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('product/show', [
            'product' => (new ProductDetailResource($product))->resolve(),
            'stores' => $stores,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'description' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image = $path;
        }

        $product->description = $request->description;
        $product->save();

        return redirect()
            ->back()
            ->with('success', 'Berhasil update produk');
    }

    public function transferStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'source_store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],

            'destination_store_id' => [
                'required',
                'integer',
                'exists:stores,id',
                'different:source_store_id',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        try {
            DB::transaction(function () use ($validated, $product) {

                $quantity = (int) $validated['quantity'];

                /*
             * Ambil stock store asal.
             */
                $source = DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->where('store_id', $validated['source_store_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$source) {
                    throw new \Exception(
                        'Produk tidak tersedia di store asal.'
                    );
                }

                /*
             * Pastikan stock cukup.
             */
                if ($source->stock < $quantity) {
                    throw new \Exception(
                        "Stock tidak cukup. Stock tersedia: {$source->stock}."
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
             * Cek produk di store tujuan.
             */
                $destination = DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->where(
                        'store_id',
                        $validated['destination_store_id']
                    )
                    ->lockForUpdate()
                    ->first();

                if ($destination) {

                    /*
                 * Produk sudah ada.
                 * Tambahkan stock.
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
                 * Buat relasi product_store baru.
                 *
                 * Harga mengikuti store asal.
                 */
                    DB::table('product_store')->insert([
                        'product_id' => $product->id,
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
             * Update total stock produk.
             */
                $totalStock = DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->sum('stock');

                $product->update([
                    'stock_all' => $totalStock,
                ]);
            });
        } catch (\Throwable $e) {

            return back()
                ->withErrors([
                    'transfer' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route('product.show', $product->id)
            ->with(
                'success',
                'Stock berhasil dipindahkan.'
            );
    }
}
