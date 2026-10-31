<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockRefillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->session()->has('d1_user_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'liter_added' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'buying_price_per_liter' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'supplier_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
