<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockIndexRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения данного запроса.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации входящих параметров фильтрации и пагинации.
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'search'       => ['nullable', 'string', 'max:255'],
            'warehouse'    => ['nullable', 'string', 'max:255'],
            'product'      => ['nullable', 'string', 'max:255'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Кастомные сообщения об ошибках валидации.
     */
    public function messages(): array
    {
        return [
            'warehouse_id.exists' => 'Указанный склад не существует.',
            'per_page.min'        => 'Количество элементов на странице должно быть не менее 1.',
            'per_page.max'        => 'Количество элементов на странице не может превышать 100.',
        ];
    }
}