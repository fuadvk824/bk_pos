<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_code'  => $this->product_code,
            'name'          => $this->name,
            'category'      => $this->category?->name,
            'stock_all'     => $this->stock_all,
            'image'         => $this->image,
        ];
    }
}
