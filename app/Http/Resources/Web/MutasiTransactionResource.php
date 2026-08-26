<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MutasiTransactionResource extends JsonResource
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
            'store_id' => $this->store_id,
            'store' => [
                'name' => $this->store?->name,
            ],

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {

                    if (
                        $item->fulfillment_status !==
                        'waiting_stock'
                    ) {
                        return null;
                    }

                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'stock_at_transaction' => $item->stock_at_transaction,
                        'quantity' => $item->quantity,
                        'fulfillment_status' => $item->fulfillment_status,

                        'stores' => $item->product?->stores
                            ?->map(function ($store) {
                                return [
                                    'id' => $store->id,
                                    'store_code' => $store->store_code,
                                    'name' => $store->name,
                                    'stock' => $store->pivot?->stock ?? 0,
                                ];
                            })
                            ->filter(function ($store) {
                                return $store['stock'] > 0;
                            })
                            ->values()->toArray() ?? [],
                    ];
                })->filter()->values()->toArray();
            }),
        ];
    }
}
