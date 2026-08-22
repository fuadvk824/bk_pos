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

            'customer_id' => $this->customer_id,

            'customer_name' => $this->customer?->name,

            'customer_address' => $this->customer?->address,

            'store_id' => $this->store_id,

            'store_name' => $this->store?->name,

            'total' => $this->total,

            'payment_status' => $this->payment_status,

            'transaction_type' => $this->transaction_type,

            'delivery_type' => $this->delivery_type,

            'driver_name' => $this->driver_name,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->format(
                'd-m-Y H:i'
            ),

            // ==================================================
            // FULFILLMENT COUNT
            // ==================================================

            'waiting_stock_count' =>
            (int) ($this->waiting_stock_count ?? 0),

            'ready_count' =>
            (int) ($this->ready_count ?? 0),

            'fulfilled_count' =>
            (int) ($this->fulfilled_count ?? 0),

            // ==================================================
            // FLAG BACKORDER
            // ==================================================

            'is_backorder' =>
            (int) ($this->waiting_stock_count ?? 0) > 0,
        ];
        // return [
        //     'id' => $this->id,
        //     'invoice_number' => $this->invoice_number,

        //     'customer_name' => $this->customer?->name,
        //     'customer_address' => $this->customer?->address,
        //     'store_name' => $this->store?->name,

        //     'total' => $this->total,
        //     'payment_status' => $this->payment_status,
        //     'delivery_type' => $this->delivery_type,
        //     'driver_name' => $this->driver_name,
        //     'created_at' => $this->created_at
        //         ? $this->created_at->format('d-m-Y H:i')
        //         : null,

        //     'paid_amount' => $this->payments_sum_amount ?? 0,
        //     'remaining_amount' => max(
        //         0,
        //         $this->total - ($this->payments_sum_amount ?? 0)
        //     ),

        //     // ==========================================
        //     // BACKORDER
        //     // ==========================================

        //     'waiting_stock_count' => $this->waiting_stock_count ?? 0,
        //     'ready_count' => $this->ready_count ?? 0,
        //     'fulfilled_count' => $this->fulfilled_count ?? 0,
        //     'is_backorder' => ($this->waiting_stock_count ?? 0) > 0,
        //     'transaction_status' => ($this->waiting_stock_count ?? 0) > 0
        //         ? 'backorder'
        //         : 'normal',
        // ];
    }
}
