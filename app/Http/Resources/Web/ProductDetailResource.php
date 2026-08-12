<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_code' => $this->product_code,
             'barcode'      => $this->barcode, 
            'name'         => $this->name,
            'description'  => $this->description,
            'image'        => $this->image,
            'category'     => $this->category?->name,

            'stores' => $this->whenLoaded('stores', function () {
                return $this->stores->values()->map(function ($store) {
                    return [
                        'id'       => $store->id,
                        'name'     => $store->name,
                        'stock'    => $store->pivot->stock,
                        'price'    => $store->pivot->price,
                        'discount' => $store->pivot->discount,
                        'price_all' => $store->pivot->price_all,
                    ];
                });
            }),
        ];
    }
}
