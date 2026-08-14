<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'       => ['required', 'integer', 'exists:customers,id'],
            'warehouse_id'      => ['required', 'integer', 'exists:warehouses,id'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id'=> ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.count'     => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists'         => 'Указанный покупатель не существует.',
            'warehouse_id.exists'        => 'Указанный склад не существует.',
            'items.*.product_id.distinct'=> 'Товар не должен повторяться в рамках одного заказа.',
            'items.*.product_id.exists'  => 'Один из выбранных товаров не существует.',
        ];
    }
}