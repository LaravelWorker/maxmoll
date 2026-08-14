<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Получить список товаров с остатками по складам
     */
    public function index(): AnonymousResourceCollection
    {
        $products = Product::with('warehouses')->get();

        return ProductResource::collection($products);
    }
}