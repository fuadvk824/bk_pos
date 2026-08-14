<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\UserResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('perPage', 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        $users = User::query()
            ->with('store')
            ->filter($request)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('user/index', [
            'users' => UserResource::collection($users)->response()->getData(true),

            'filters' => [
                'search' => $request->input('search'),
                'store_id' => $request->input('store_id'),
                'perPage' => $perPage,
            ],

            'stores' => Store::query()
                ->select(['id','name',])
                ->orderBy('name')
                ->get(),
        ]);
    }
}