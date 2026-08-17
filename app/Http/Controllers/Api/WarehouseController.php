<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WarehouseController extends Controller
{
    /**
     * Получить список всех складов компании.
     *
     * Метод выполняет выборку всех доступных складских помещений из базы данных
     * и возвращает их в виде стандартизированной API-коллекции ресурсов.
     *
     * @return AnonymousResourceCollection Коллекция ресурсов складов (WarehouseResource)
     */
    public function index(): AnonymousResourceCollection
    {
        // Выбираем все записи складов из базы данных без пагинации,
        // так как справочник складов обычно небольшой.
        $warehouses = Warehouse::all();

        // Возвращаем данные, завернутые в API Resource Collection для корректного форматирования JSON
        return WarehouseResource::collection($warehouses);
    }
}
