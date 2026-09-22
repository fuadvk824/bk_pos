<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreansactionResource extends JsonResource
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
            'transaction_type' => $this->transaction_type,
            'payment_status' => $this->payment_status,
            'created_at' => $this->created_at->format('d-m-Y H:i'),
            // 'created_at' => ($this->since ?? $this->created_at)?->format('d-m-Y H:i'),

            'delivery_type' => $this->delivery_type,
            'driver_name' => $this->driver_name,
            'customer_name' => $this->customer?->name,

            'total' => $this->total,
            'paid_amount' => $this->payments_sum_amount ?? 0,
            'remaining_amount' => max(
                0,
                $this->total - ($this->payments_sum_amount ?? 0)
            ),
        ];
    }
}
