<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProcessTransactionRequest;
use App\Http\Resources\Web\TransactionDetailResource;
use App\Http\Resources\Web\TransactionResource;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);

        $transactions = Transaction::query()
            ->with([
                'customer',
                'store',
            ])
            ->withSum('payments', 'amount')
            ->when($request->search, function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                    ->orWhereHas('customers', function ($c) use ($request) {
                        $c->where('name', 'like', "%{$request->search}%");
                    });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('transaction/index', [
            'transactions' => TransactionResource::collection(
                $transactions
            )->response()->getData(true),

            'filters' => [
                'search' => $request->search,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'customer',
            'store',
            'user',
            'payments.user',
            'items.product',
        ]);

        return Inertia::render('transaction/show', [
            'transaction' => (
                new TransactionDetailResource($transaction)
            )->resolve(),
        ]);
    }

    public function process(
        ProcessTransactionRequest $request,
        Transaction $transaction
    ) {

        DB::transaction(function () use (
            $request,
            $transaction
        ) {
            if ($transaction->delivery_type === 'delivery') {
                $transaction->update([
                    'driver_name' => $request->driver_name,
                ]);
            }

            $remaining =
                $transaction->total -
                $transaction->payments()->sum('amount');

            if ($request->amount > $remaining) {

                throw ValidationException::withMessages([
                    'amount' => [
                        'Nominal pembayaran melebihi sisa tagihan.',
                    ],
                ]);
            }

            Payment::create([
                'transaction_id' => $transaction->id,
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
            ]);

            $totalPaid = $transaction
                ->payments()
                ->sum('amount');

            $transaction->refresh();

            if ($totalPaid <= 0) {

                $status = 'unpaid';
            } elseif ($totalPaid < $transaction->total) {
                $status = 'partial';
            } else {

                $status = 'paid';
            }

            $transaction->update([
                'payment_status' => $status,
            ]);
        });

        return back()->with(
            'success',
            'Transaction processed successfully'
        );
    }
}
