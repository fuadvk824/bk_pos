<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,

            'customer_name' => $this->customer?->name,
            'customer_address' => $this->customer?->address,
            'store_name' => $this->store?->name,

            'total' => $this->total,
            'payment_status' => $this->payment_status,
            'delivery_type' => $this->delivery_type,
            'driver_name' => $this->driver_name,
            'created_at' => $this->created_at
                ? $this->created_at->format('d-m-Y H:i')
                : null,
                
            'paid_amount' => $this->payments_sum_amount ?? 0,
            'remaining_amount' => max(
                0,
                $this->total - ($this->payments_sum_amount ?? 0)
            ),
        ];
    }
}
