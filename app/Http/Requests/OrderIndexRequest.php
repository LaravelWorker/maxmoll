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
        // В соответствии с требованиями проекта авторизация не требуется, разрешаем доступ.
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
            // Фильтрация по статусу заказа (значение должно строго соответствовать допустимым кейсам OrderStatus Enum)
            'status'       => ['nullable', 'string', Rule::enum(OrderStatus::class)],
            
            // Фильтрация по конкретному покупателю (проверяется реальное существование ID в таблице customers)
            'customer_id'  => ['nullable', 'integer', 'exists:customers,id'],
            
            // Фильтрация по складу отгрузки (проверяется реальное существование ID в таблице warehouses)
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            
            // Начальная дата периода для фильтрации по дате создания заказа
            'date_from'    => ['nullable', 'date'],
            
            // Конечная дата периода (должна быть больше или равна дате date_from для логической целостности)
            'date_to'      => ['nullable', 'date', 'after_or_equal:date_from'],
            
            // Лимит элементов на странице для пагинации (ограничен диапазоном от 1 до 100)
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}