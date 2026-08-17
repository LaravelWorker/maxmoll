<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementIndexRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    /**
     * Просмотр истории движения товаров  с фильтрацией и пагинацией.
     *
     * Метод возвращает постраничный список записей о любых изменениях складских остатков 
     * (поступления, списания по заказам, перемещения). Поддерживает гибкую фильтрацию по 
     * конкретному складу, товару, типу документа-источника (`doc_type`), а также по временному диапазону.
     *
     * @param StockMovementIndexRequest $request Валидированный запрос с параметрами фильтрации и пагинации
     * @return AnonymousResourceCollection Коллекция ресурсов истории движений (StockMovementResource)
     */
    public function index(StockMovementIndexRequest $request): AnonymousResourceCollection
    {
        $query = StockMovement::query()->with(['warehouse', 'product', 'doc']);

        // Фильтр по конкретному складу
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Фильтр по ID товара
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Поиск по названию товара (строка из инпута фронтенда)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Фильтр по типу документа
        if ($request->filled('doc_type')) {
            $query->where('doc_type', $request->input('doc_type'));
        }

        // Фильтры по датам
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = $request->input('per_page', 15);
        $movements = $query->orderByDesc('id')->paginate($perPage);

        return StockMovementResource::collection($movements);
    }
}