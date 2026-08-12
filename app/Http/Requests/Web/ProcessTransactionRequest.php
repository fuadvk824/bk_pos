<?php

namespace App\Http\Requests\Web;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'transfer',
                    'qris',
                ]),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],
        ];
    }
}
