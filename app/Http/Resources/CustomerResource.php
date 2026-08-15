<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели Customer (покупатель) в JSON-представление.
 * Отвечает за структурирование и форматирование данных клиента при отдаче через API.
 */
class CustomerResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными покупателя
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор клиента в базе данных
            'id'         => $this->id,
            
            // Имя (или ФИО) покупателя
            'name'       => $this->name,
            
            // Контактный номер телефона (может быть null)
            'phone'      => $this->phone,
            
            // Контактный адрес электронной почты (может быть null)
            'email'      => $this->email,
            
            // Дата создания записи о клиенте.
            // Используется nullsafe оператор (?->), чтобы избежать ошибки вызова метода на null,
            // метод toDateTimeString() приводит дату к удобному формату 'Y-m-d H:i:s'.
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}