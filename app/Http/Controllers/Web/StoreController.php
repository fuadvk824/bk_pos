<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\StoreResource;
use App\Models\Store;
use App\Models\StoreTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);

        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;

        $stores = Store::query()
            ->with([
                'targets' => fn($q) => $q
                    ->where('year', $year)
                    ->where('month', $month)
            ])
            ->when($request->search, function ($q) use ($request) {
                $q->where('store_code', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            })
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('store/index', [
            'stores' => StoreResource::collection($stores)
                ->response()
                ->getData(true),

            'filters' => [
                'search' => $request->search,
                'year' => $year,
                'month' => $month,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function store(Request $request, Store $store)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        StoreTarget::updateOrCreate(
            [
                'store_id' => $store->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'target_amount' => $validated['target_amount'],
                'status' => $validated['status'],
            ]
        );

        return back()->with('success', 'Target store berhasil disimpan.');
    }
}
