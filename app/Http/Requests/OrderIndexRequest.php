<?php

namespace App\Http\Requests;

use App\Consts\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Класс запроса для валидации фильтров и пагинации при получении списка заказов.
 * Обеспечивает проверку статуса, идентификаторов клиентов и складов, а также диапазонов дат.
 */
class OrderIndexRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения данного запроса.
     *
     * @return bool Возвращает true, разрешая выполнение запроса без ограничений аутентификации.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить массив правил валидации, применяемых к параметрам запроса.
     *
     * @return array Ассоциативный массив правил валидации для фильтров заказов.
     */
    public function rules(): array
    {
        return [
            // Поиск по текстовым совпадениям наименований
            'customer_search'  => ['nullable', 'string', 'max:255'],
            'warehouse_search' => ['nullable', 'string', 'max:255'],

            // Фильтрация по статусу заказа
            'status'           => ['nullable', 'string', Rule::enum(OrderStatus::class)],
            
            // Точные ID
            'customer_id'      => ['nullable', 'integer', 'exists:customers,id'],
            'warehouse_id'     => ['nullable', 'integer', 'exists:warehouses,id'],
            
            // Фильтры по датам
            'date_from'        => ['nullable', 'date'],
            'date_to'          => ['nullable', 'date', 'after_or_equal:date_from'],
            
            // Пагинация
            'per_page'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'             => ['nullable', 'integer', 'min:1'],
        ];
    }
}