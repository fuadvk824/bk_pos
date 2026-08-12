<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\CustomerResource;
use App\Models\Customer;
use App\Models\PointSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);

        $customers = Customer::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('phone', 'like', "%{$request->search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('customer/index', [
            'customers' => CustomerResource::collection($customers)
                ->response()
                ->getData(true),
            'pointSetting' => PointSetting::first(),
            'filters' => [
                'search' => $request->search,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function updatePoint(Request $request, PointSetting $pointSetting)
    {
        $validated = $request->validate([
            'spend_amount' => ['required', 'integer', 'min:1'],
            'point_reward' => ['required', 'integer', 'min:1'],
            'minimum_transaction' => ['required', 'integer', 'min:0'],
        ]);

        $pointSetting->update($validated);

        return back()->with('success', 'Point setting berhasil diupdate');
    }
}
