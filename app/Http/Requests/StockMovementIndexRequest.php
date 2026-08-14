<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id'   => ['nullable', 'integer', 'exists:products,id'],
            'doc_type'     => ['nullable', 'string', 'in:order,supply,transfer'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}