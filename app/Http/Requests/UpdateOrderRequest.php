<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'        => ['sometimes', 'required', 'integer', 'exists:customers,id'],
            'warehouse_id'       => ['sometimes', 'required', 'integer', 'exists:warehouses,id'],
            'items'              => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id', 'distinct'],
            'items.*.count'      => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}