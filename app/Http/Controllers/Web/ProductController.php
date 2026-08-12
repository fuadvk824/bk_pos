<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\ProductDetailResource;
use App\Http\Resources\Web\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
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

    public function show(Product $product)
    {
        $product->load(['category', 'stores']);
        return Inertia::render('product/show', [
            'product' => (new ProductDetailResource($product))->resolve(),
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
}
