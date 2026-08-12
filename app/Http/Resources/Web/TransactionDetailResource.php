<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
       

         return [
            'id' => $this->id,

            'invoice_number' => $this->invoice_number,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'total' => $this->total,
            'payment_status' => $this->payment_status,
            'delivery_type' => $this->delivery_type,
            'driver_name' => $this->driver_name,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('d-m-Y H:i'),
            'customer' => [
                'name' => $this->customer?->name,
                'phone' => $this->customer?->phone,
                'address' => $this->customer?->address,
            ],
            'store' => [
                'name' => $this->store?->name,
                'address' => $this->store?->address,
            ],
            'cashier' => [
                'name' => $this->user?->name,
            ],
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product?->name,
                        'quantity' => $item->quantity,
                        'base_price' => $item->base_price,
                        'price' => $item->price,
                        'discount' => $item->discount,
                        'subtotal' => $item->subtotal,
                    ];
                });
            }),

            'payments' => $this->whenLoaded('payments', function () {
                return $this->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'paid_at' => $payment->paid_at,
                        'cashier_name' => $payment->user?->name,
                    ];
                });
            }),
        ];
    }
}
