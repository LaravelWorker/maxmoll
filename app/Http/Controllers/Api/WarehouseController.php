<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WarehouseController extends Controller
{
    /**
     * Получить список всех складов
     */
    public function index(): AnonymousResourceCollection
    {
        $warehouses = Warehouse::all();

        return WarehouseResource::collection($warehouses);
    }
}