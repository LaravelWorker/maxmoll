<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Класс запроса для валидации входящих параметров при получении истории движений товаров.
 * Обеспечивает проверку фильтров по складу, товару, типу документа и временному периоду.
 */
class StockMovementIndexRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения данного запроса.
     *
     * @return bool Возвращает true, разрешая доступ к фильтрации движений товаров всем пользователям.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить массив правил валидации, применяемых к параметрам запроса.
     *
     * @return array Ассоциативный массив правил валидации для фильтров истории движений.
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id'   => ['nullable', 'integer', 'exists:products,id'],
            'search'       => ['nullable', 'string', 'max:255'],
            'doc_type'     => ['nullable', 'string'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}