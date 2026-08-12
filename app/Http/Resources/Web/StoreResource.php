<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target = $this->targets->first();

        return [
            'id' => $this->id,
            'store_code' => $this->store_code,
            'name' => $this->name,
            'address' => $this->address,

            'target' => $target ? [
                'id' => $target->id,
                'year' => $target->year,
                'month' => $target->month,
                'target_amount' => $target->target_amount,
                'status' => $target->status,
            ] : null,
        ];
    }
}
