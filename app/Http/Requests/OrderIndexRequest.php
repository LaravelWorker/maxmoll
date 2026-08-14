<?php

namespace App\Http\Requests;

use App\Consts\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'       => ['nullable', 'string', Rule::enum(OrderStatus::class)],
            'customer_id'  => ['nullable', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}