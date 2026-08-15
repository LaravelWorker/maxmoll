<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Класс запроса для валидации входящих параметров при получении списка покупателей.
 * Обеспечивает проверку параметров фильтрации и настройки пагинации.
 */
class CustomerIndexRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения данного запроса.
     *
     * @return bool Возвращает true, если запрос разрешен.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить массив правил валидации, применяемых к параметрам запроса.
     *
     * @return array Массив с правилами валидации для полей запроса.
     */
    public function rules(): array
    {
        return [
            // Общая поисковая строка (например, поиск одновременно по имени, email и телефону)
            'search'   => ['nullable', 'string', 'max:255'],
            
            // Точный или частичный поиск только по имени покупателя
            'name'     => ['nullable', 'string', 'max:255'],
            
            // Фильтрация по номеру телефона
            'phone'    => ['nullable', 'string', 'max:255'],
            
            // Фильтрация по адресу электронной почты
            'email'    => ['nullable', 'string', 'max:255'],
            
            // Параметр пагинации: количество элементов на одной странице (ограничено от 1 до 100)
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}