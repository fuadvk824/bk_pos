<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\UserResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            ->latest()
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
                ->select(['id', 'name',])
                ->orderBy('name')
                ->get(),
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'max:255',
                'unique:users,username',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'store_id' => $validated['store_id'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with(
            'success',
            'User berhasil ditambahkan.'
        );
    }

    public function update(
        Request $request,
        User $user
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')
                    ->ignore($user->id),
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->store_id = $validated['store_id'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with(
            'success',
            'User berhasil diperbarui.'
        );
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back()->with(
            'success',
            'User berhasil dihapus.'
        );
    }
}
