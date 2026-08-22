<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreansactionDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'invoice_number' => $this->invoice_number,
            'payment_status' => $this->payment_status,
            'created_at' => $this->created_at?->format('d-m-Y H:i'),
            'delivery_type' => $this->delivery_type,
            'driver_name' => $this->driver_name,
            'notes' => $this->notes,
            'cashier' => $this->user?->name,
            'printed_by' => $request->user()?->name,

            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'points_used' => $this->points_used,
            'total' => $this->total,
            'remaining_amount' => max(
                0,
                $this->total - ($this->payments_sum_amount ?? 0)
            ),

            'customer' => [
                'name' => $this->customer?->name,
                'phone' => $this->customer?->phone,
                'address' => $this->customer?->address,
            ],

            'store' => [
                'name' => $this->store?->name,
                'name_view' => $this->store?->name_view,
                'address' => $this->store?->address,
                'email' => $this->store?->email,
                'phone' => $this->store?->phone,
            ],

            'company' => [
                'name' => $this->store?->company?->name,
                'address' => $this->store?->company?->address,
            ],

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product?->name,
                        'product_code' => $item->product?->product_code,
                        'unit' => $item->product?->unit,

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
