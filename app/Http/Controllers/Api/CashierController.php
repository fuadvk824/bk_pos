<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Payment;
use App\Models\PointSetting;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Provinsi;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashierController extends Controller
{
    public function search(Request $request)
    {
        $user = $request->user();
        $search = $request->search;

        return Customer::query()
            ->where('store_id', $user->store_id)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->limit(50)
            ->get([
                'id',
                'name',
                'phone',
                'address',
                'current_point',
            ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'transaction_type' => ['required', 'in:normal,backorder'],
            'name' => ['required', 'string', 'max:255',],
            'phone' => ['nullable', 'string', 'max:50',],
            'address' => ['nullable', 'string',],

            'delivery_type' => ['required', 'in:pickup,delivery',],
            'shipping_cost' => ['nullable', 'numeric', 'min:0',],
            'notes' => ['nullable', 'string',],

            'payment_status' => ['required', 'in:unpaid,partial,paid',],
            'payment_method' => ['nullable', 'in:cash,transfer,qris'],
            'payment_amount' => ['nullable', 'numeric', 'min:0',],

            'points_used' => ['nullable', 'integer', 'min:0',],
            'nego' => ['nullable', 'numeric', 'min:0',],

            'items' => ['required', 'array', 'min:1',],
            'items.*.id' => ['required', 'integer', 'exists:products,id',],
            'items.*.qty' => ['required', 'integer', 'min:1',],
            'items.*.price_lines' => ['required', 'array', 'min:1',],
            'items.*.price_lines.*.qty' => ['required', 'integer', 'min:1',],

            'items.*.price_lines.*.price' => ['required', 'numeric', 'min:0.01',],
            'items.*.price_lines.*.label' => ['nullable', 'string', 'max:50',],
            'items.*.price_lines.*.unit_size' => ['required', 'integer', 'min:1',],

            'provinsi_id' => ['nullable', 'integer', 'exists:s_provinsi,id'],
            'kabupaten_id' => ['nullable', 'integer', 'exists:s_kabupaten,id'],
            'kecamatan_id' => ['nullable', 'integer', 'exists:s_kecamatan,id'],
            'desa_id' => ['nullable', 'integer', 'exists:s_desa,id'],
        ]);

        $addressParts = [];

        if (!empty($validated['address'])) {
            $addressParts[] = trim($validated['address']);
        }

        if (!empty($validated['desa_id'])) {
            $desa = Desa::find($validated['desa_id']);

            if ($desa) {
                $addressParts[] = $desa->nama;
            }
        }

        if (!empty($validated['kecamatan_id'])) {
            $kecamatan = Kecamatan::find($validated['kecamatan_id']);

            if ($kecamatan) {
                $addressParts[] = $kecamatan->nama;
            }
        }

        if (!empty($validated['kabupaten_id'])) {
            $kabupaten = Kabupaten::find($validated['kabupaten_id']);

            if ($kabupaten) {
                $addressParts[] = $kabupaten->nama;
            }
        }

        if (!empty($validated['provinsi_id'])) {
            $provinsi = Provinsi::find($validated['provinsi_id']);

            if ($provinsi) {
                $addressParts[] = $provinsi->nama;
            }
        }

        $fullAddress = !empty($addressParts)
            ? ucwords(strtolower(implode(', ', $addressParts)))
            : null;

        $requestedTransactionType = $validated['transaction_type'];
        $transactionType = $requestedTransactionType;

        $shippingCost = (float) ($validated['shipping_cost'] ?? 0);
        $pointsUsed = (int) ($validated['points_used'] ?? 0);
        $nego = (float) ($validated['nego'] ?? 0);

        if ($validated['delivery_type'] === 'pickup') {
            $shippingCost = 0;
        }

        if ($validated['payment_status'] === 'unpaid') {
            $paymentMethod = null;
            $paymentAmount = 0;
        } else {
            $paymentMethod = $validated['payment_method'] ?? null;
            $paymentAmount = (float) ($validated['payment_amount'] ?? 0);

            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Metode pembayaran wajib dipilih',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $customer = null;

            if (!empty($validated['phone']) || !empty($validated['name'])) {

                $name = !empty($validated['name'])
                    ? Str::title(trim($validated['name']))
                    : null;

                $phone = null;

                if (!empty($validated['phone'])) {
                    $phone = preg_replace('/[^\d+]/', '', trim($validated['phone']));

                    if (Str::startsWith($phone, '+62')) {
                        $phone = '0' . substr($phone, 3);
                    } elseif (Str::startsWith($phone, '62')) {
                        $phone = '0' . substr($phone, 2);
                    } elseif (Str::startsWith($phone, '8')) {
                        $phone = '0' . $phone;
                    }

                    if (!preg_match('/^08[1-9][0-9]{7,11}$/', $phone)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'phone' => 'Nomor telepon tidak valid. Gunakan format 08xxxxxxxxxx atau +628xxxxxxxxxx.',
                        ]);
                    }
                }

                $customer = null;

                if ($phone) {
                    $customer = Customer::where('store_id', $user->store_id)
                        ->where('phone', $phone)
                        ->first();
                }

                if (!$customer) {
                    $customer = Customer::create([
                         'store_id' => $user->store_id,
                        'name' => $name,
                        'phone' => $phone,
                        'address' => $fullAddress,
                    ]);
                }
            }

            if ($pointsUsed > 0) {
                if (!$customer) {
                    throw new \Exception(
                        'Customer wajib dipilih untuk menggunakan point'
                    );
                }
                if ($pointsUsed > $customer->current_point) {
                    throw new \Exception(
                        'Point customer tidak mencukupi'
                    );
                }
            }

            $processedItems = [];
            $calculatedSubtotal = 0;

            foreach ($validated['items'] as $item) {
                $productId = (int) $item['id'];
                $itemQty = (int) $item['qty'];
                $priceLines = $item['price_lines'];

                $product = Product::find($productId);
                if (!$product) {
                    throw new \Exception(
                        "Produk ID {$productId} tidak ditemukan"
                    );
                }

                $priceLinePcs = 0;
                foreach ($priceLines as $line) {
                    $lineQty = (int) $line['qty'];
                    $unitSize = (int) $line['unit_size'];

                    if ($lineQty <= 0) {
                        throw new \Exception(
                            "Qty harga produk ID {$productId} tidak valid"
                        );
                    }

                    if ($unitSize <= 0) {
                        throw new \Exception(
                            "Ukuran unit produk ID {$productId} tidak valid"
                        );
                    }

                    $priceLinePcs += $lineQty * $unitSize;
                }

                if ($priceLinePcs !== $itemQty) {
                    throw new \Exception(
                        "Rincian qty harga produk ID {$productId} tidak sesuai dengan qty produk"
                    );
                }

                $productStore = ProductStore::where('product_id', $productId)
                    ->where('store_id', $user->store_id)
                    ->lockForUpdate()
                    ->first();

                if ($requestedTransactionType === 'normal') {

                    if (!$productStore) {
                        throw new \Exception(
                            "Produk ID {$productId} tidak tersedia di toko"
                        );
                    }

                    $stock = (int) $productStore->stock;

                    if ($stock < $itemQty) {
                        throw new \Exception(
                            "Stok produk ID {$productId} tidak mencukupi"
                        );
                    }

                    $fulfillmentStatus = 'fulfilled';

                    $basePrice = (float) $productStore->price;
                    $discount = (float) ($productStore->discount ?? 0);
                } else {

                    $stock = $productStore ? (int) $productStore->stock : 0;

                    if ($stock >= $itemQty) {
                        $fulfillmentStatus = 'ready';
                    } else {
                        $fulfillmentStatus = 'waiting_stock';
                    }

                    $basePrice = $productStore ? (float) $productStore->price : 0;
                    $discount = $productStore ? (float) ($productStore->discount ?? 0) : 0;
                }

                $processedPriceLines = [];
                foreach ($priceLines as $line) {
                    $lineQty = (int) $line['qty'];
                    $unitSize = (int) $line['unit_size'];
                    $linePrice = (float) $line['price'];
                    $lineLabel = trim((string) ($line['label'] ?? 'Eceran'));

                    if ($lineQty <= 0) {
                        throw new \Exception(
                            "Qty harga produk ID {$productId} tidak valid"
                        );
                    }

                    if ($unitSize <= 0) {
                        throw new \Exception(
                            "Ukuran unit produk ID {$productId} tidak valid"
                        );
                    }

                    if ($linePrice <= 0) {
                        throw new \Exception(
                            "Harga produk ID {$productId} tidak valid"
                        );
                    }

                    $pcsQuantity = $lineQty * $unitSize;
                    $normalizedLabel = strtolower($lineLabel);

                    $isBundle = in_array(
                        $normalizedLabel,
                        ['dus', 'bundle', 'pack'],
                        true
                    );

                    if ($isBundle) {
                        $satuanUnit = $product->unit2 ?: 'BNDL';
                    } else {
                        $satuanUnit = $product->unit ?: 'PCS';
                    }

                    $quantity = $lineQty;
                    $qtyUnit = $pcsQuantity;

                    $lineSubtotal = $quantity * $linePrice;

                    $calculatedSubtotal += $lineSubtotal;

                    $processedPriceLines[] = [
                        'quantity' => $quantity,
                        'qty_unit' => $qtyUnit,
                        'satuan_unit' => $satuanUnit,

                        'unit_size' => $unitSize,
                        'pcs_quantity' => $pcsQuantity,

                        'price' => $linePrice,
                        'label' => $lineLabel,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                $processedItems[] = [
                    'product_id' => $productId,
                    'quantity' => $itemQty,
                    'stock' => $stock,
                    'base_price' => $basePrice,
                    'discount' => $discount,
                    'fulfillment_status' => $fulfillmentStatus,
                    'price_lines' => $processedPriceLines,
                    'product_store' => $productStore,
                ];
            }


            if ($requestedTransactionType === 'backorder') {

                $hasWaitingStock = collect($processedItems)
                    ->contains(function ($item) {
                        return $item['fulfillment_status'] === 'waiting_stock';
                    });

                $transactionType = $hasWaitingStock
                    ? 'backorder'
                    : 'normal';
            } else {

                $transactionType = 'normal';
            }


            $totalBeforeDiscount = $calculatedSubtotal + $shippingCost;

            if ($nego < 0) {
                throw new \Exception(
                    'Nominal nego tidak boleh kurang dari 0'
                );
            }

            $maximumNego = $totalBeforeDiscount - $pointsUsed;
            if ($maximumNego < 0) {
                $maximumNego = 0;
            }

            if ($nego > $maximumNego) {
                throw new \Exception(
                    'Nominal nego melebihi total transaksi'
                );
            }

            $calculatedTotal = $calculatedSubtotal + $shippingCost - $pointsUsed - $nego;
            if ($calculatedTotal < 0) {
                throw new \Exception(
                    'Total transaksi tidak boleh kurang dari 0'
                );
            }

            if ($validated['payment_status'] === 'partial') {
                if ($paymentAmount <= 0) {
                    throw new \Exception(
                        'Nominal pembayaran harus lebih dari 0'
                    );
                }

                if ($paymentAmount > $calculatedTotal) {
                    throw new \Exception(
                        'Nominal pembayaran melebihi total transaksi'
                    );
                }
            }

            if ($validated['payment_status'] === 'paid') {
                if ($calculatedTotal <= 0) {
                    throw new \Exception(
                        'Total transaksi tidak valid'
                    );
                }
                $paymentAmount = $calculatedTotal;
            }

            if ($validated['payment_status'] === 'unpaid') {
                $paymentAmount = 0;
            }

            $invoiceNumber = 'INV-' .
                now()->format('ymd') .
                '-' .
                strtoupper(Str::random(3)) .
                now()->format('Hi');

            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'transaction_type' => $transactionType,
                'user_id' => $user->id,
                'store_id' => $user->store_id,
                'customer_id' => $customer?->id,
                'subtotal' => $calculatedSubtotal,
                'shipping_cost' => $shippingCost,
                'points_used' => $pointsUsed,
                'nego' => $nego,
                'total' => $calculatedTotal,
                'payment_status' => $validated['payment_status'],
                'delivery_type' => $validated['delivery_type'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($processedItems as $processedItem) {

                $productStore = $processedItem['product_store'];

                foreach ($processedItem['price_lines'] as $priceLine) {

                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $processedItem['product_id'],

                        'quantity' => $priceLine['quantity'],
                        'qty_unit' => $priceLine['qty_unit'],
                        'satuan_unit' => $priceLine['satuan_unit'],

                        'stock_at_transaction' => $processedItem['stock'],
                        'fulfillment_status' => $processedItem['fulfillment_status'],
                        'base_price' => $processedItem['base_price'],
                        'price' => $priceLine['price'],
                        'discount' => $processedItem['discount'],
                        'subtotal' => $priceLine['subtotal'],
                    ]);
                }

                if (
                    $productStore &&
                    in_array(
                        $processedItem['fulfillment_status'],
                        ['fulfilled', 'ready'],
                        true
                    )
                ) {
                    $productStore->decrement(
                        'stock',
                        $processedItem['quantity']
                    );
                }
            }

            if ($pointsUsed > 0) {
                if (!$customer) {
                    throw new \Exception(
                        'Customer wajib dipilih untuk menggunakan point'
                    );
                }

                $customer = Customer::lockForUpdate()->find($customer->id);
                if (!$customer) {
                    throw new \Exception(
                        'Customer tidak ditemukan'
                    );
                }

                if ($pointsUsed > $customer->current_point) {
                    throw new \Exception(
                        'Point customer tidak mencukupi'
                    );
                }

                $customer->decrement('current_point', $pointsUsed);

                CustomerPoint::create([
                    'customer_id' => $customer->id,
                    'points' => $pointsUsed,
                    'type' => 'redeem',
                    'reference' => $transaction->invoice_number,
                ]);
            }

            if (in_array($validated['payment_status'], ['paid', 'partial'], true)) {
                Payment::create([
                    'transaction_id' => $transaction->id,
                    'user_id' => Auth::id(),
                    'payment_method' => $paymentMethod,
                    'amount' => $paymentAmount,
                ]);
            }

            if ($transaction->payment_status === 'paid' && $transaction->customer_id) {
                $this->giveCustomerPoint($transaction);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil',
                'data' => [
                    'id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'subtotal' => (float) $transaction->subtotal,
                    'shipping_cost' => (float) $transaction->shipping_cost,
                    'points_used' => (int) $transaction->points_used,
                    'nego' => (float) $transaction->nego,
                    'total' => (float) $transaction->total,
                    'payment_status' => $transaction->payment_status,
                ],

            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function addPayment(Request $request, Transaction $transaction)
    {
        if ($transaction->payment_status == "paid") {
            return response()->json([
                "success" => false,
                "message" => "Transaksi sudah lunas."
            ], 422);
        }
        $validated = $request->validate([
            'driver_name' => ['nullable', 'string', 'max:255'],
            'delivery_type' => ['required', 'in:pickup,delivery'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        if (
            $validated['delivery_type'] === 'delivery'
            && ($validated['shipping_cost'] ?? 0) < 0
        ) {
            throw new \Exception("Ongkir tidak valid.");
        }

        DB::beginTransaction();

        try {
            $transaction->delivery_type = $validated['delivery_type'];

            if ($validated['delivery_type'] === 'delivery') {
                $transaction->shipping_cost =
                    $validated['shipping_cost'] ?? 0;

                $transaction->driver_name =
                    $validated['driver_name'] ?? null;
            } else {
                $transaction->shipping_cost = 0;
                $transaction->driver_name = null;
            }
            $transaction->notes =
                isset($validated['notes'])
                ? trim($validated['notes'])
                : $transaction->notes;

            $transaction->total =
                $transaction->subtotal +
                $transaction->shipping_cost -
                ($transaction->points_used ?? 0) -
                ($transaction->nego ?? 0);

            $paid = $transaction->payments()->sum('amount');
            $remaining = $transaction->total - $paid;

            if ($remaining <= 0) {
                throw new \Exception(
                    'Transaksi sudah tidak memiliki sisa tagihan.'
                );
            }

            if ($validated['amount'] > $remaining) {
                throw new \Exception(
                    "Nominal pembayaran melebihi sisa tagihan."
                );
            }

            Payment::create([
                'transaction_id' => $transaction->id,
                'user_id' => Auth::id(),
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount']
            ]);

            $paid = $transaction->payments()->sum('amount');

            if ($paid <= 0) {
                $transaction->payment_status = "unpaid";
            } elseif ($paid < $transaction->total) {
                $transaction->payment_status = "partial";
            } else {
                $transaction->payment_status = "paid";
            }

            $transaction->save();

            if ($transaction->payment_status === 'paid') {
                $this->giveCustomerPoint($transaction);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    private function giveCustomerPoint(Transaction $transaction): void
    {
        if (
            CustomerPoint::where('reference', $transaction->invoice_number)
            ->where('type', 'earn')
            ->exists()
        ) {
            logger('CustomerPoint earn');
            return;
        }
        logger('giveCustomerPoint dipanggil');

        $customer = Customer::lockForUpdate()->find($transaction->customer_id);

        if (!$customer) {
            return;
        }

        $setting = PointSetting::where('is_active', true)->first();

        if (
            !$setting ||
            $transaction->subtotal < $setting->minimum_transaction
        ) {
            return;
        }

        $earned = floor(
            $transaction->subtotal / $setting->spend_amount
        ) * $setting->point_reward;

        if ($earned <= 0) {
            return;
        }

        $customer->increment('current_point', $earned);

        CustomerPoint::create([
            'customer_id' => $customer->id,
            'points' => $earned,
            'type' => 'earn',
            'reference' => $transaction->invoice_number,
        ]);
    }
}
