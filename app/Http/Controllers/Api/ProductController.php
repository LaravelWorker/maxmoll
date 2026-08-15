<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Получить полный список товаров с информацией об их остатках по складам.
     *
     * Метод извлекает все доступные товары из базы данных вместе с привязанными складами 
     * и актуальными остатками (благодаря предварительной загрузке отношений через Eager Loading), 
     * после чего форматирует ответ в виде стандартизированной API-коллекции ресурсов.
     *
     * @return AnonymousResourceCollection Коллекция ресурсов товаров со складскими остатками (ProductResource)
     */
    public function index(): AnonymousResourceCollection
    {
        // Загружаем список товаров вместе со связанными складами и промежуточными остатками 
        // для предотвращения проблемы N+1 запросов к базе данных.
        $products = Product::with('warehouses')->get();

        // Возвращаем отформатированную коллекцию через API Resource
        return ProductResource::collection($products);
    }
}