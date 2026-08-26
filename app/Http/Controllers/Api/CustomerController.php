<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

// class CustomerController extends Controller
// {
//     public function index(Request $request)
//     {
//         $storeId = $request->user()->store_id;

//         $customers = Customer::query()
//             ->where('store_id', $storeId)
//             ->when($request->search, function ($q) use ($request) {
//                 $q->where(function ($query) use ($request) {
//                     $query->where('name', 'like', "%{$request->search}%")
//                         ->orWhere('phone', 'like', "%{$request->search}%");
//                 });
//             })
//             ->orderBy('name')
//             ->get();

//         return CustomerResource::collection($customers);
//     }
// }

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('phone', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return CustomerResource::collection($customers);
    }
}
 