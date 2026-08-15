<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели Warehouse (склад) в JSON-представление.
 * Отвечает за форматирование базовых данных о складском помещении при выдаче через API,
 * скрывая лишние поля базы данных (например, created_at или updated_at, если они не нужны).
 */
class WarehouseResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed> Ассоциативный массив с отформатированными данными склада
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный внутренний идентификатор склада в базе данных
            'id'   => $this->id,
            
            // Название (наименование) склада, отображаемое пользователям системы
            'name' => $this->name,
        ];
    }
}