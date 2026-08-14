<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id'  => ['required', 'integer', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id'    => ['required', 'integer', 'exists:warehouses,id'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.count'      => ['required', 'integer', 'min:1'],
        ];
    }
}