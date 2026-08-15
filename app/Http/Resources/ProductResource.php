<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели Product (товар) в JSON-представление.
 * Отвечает за форматирование основных данных о товаре и информации о его наличии на складах.
 */
class ProductResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными товара
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор товара в базе данных
            'id'     => $this->id,
            
            // Наименование (название) товара
            'name'   => $this->name,
            
            // Базовая стоимость (цена) товара
            'price'  => $this->price,
            
            // Коллекция данных об остатках товара по различным складам.
            // Используется whenLoaded('warehouses') для предотвращения проблемы N+1 запросов:
            // данные будут включены в ответ только если связь со складами была предварительно загружена.
            'stocks' => StockResource::collection($this->whenLoaded('warehouses')),
        ];
    }
}