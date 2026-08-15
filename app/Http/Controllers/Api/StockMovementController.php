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
     * Просмотр истории движения товаров (аудит/ledger остатков) с фильтрацией и пагинацией.
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
        // Инициируем базовый запрос с предварительной загрузкой связанных моделей для оптимизации (Eager Loading)
        $query = StockMovement::query()->with(['warehouse', 'product']);

        // Фильтрация по идентификатору склада, если параметр передан в запросе
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Фильтрация по идентификатору товара, если параметр передан в запросе
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Фильтрация по типу документа-источника (например, order, supply, transfer)
        if ($request->filled('doc_type')) {
            $docTypeMap = [
                'order'    => \App\Models\Order::class,
                'supply'   => \App\Models\Supply::class,
                'transfer' => \App\Models\Transfer::class,
            ];

            $input = $request->input('doc_type');

            if (isset($docTypeMap[$input])) {
                $query->where('doc_type', $docTypeMap[$input]);
            }
        }

        // Фильтрация по начальной дате периода создания записи
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        // Фильтрация по конечной дате периода создания записи
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Определяем количество элементов на странице (по умолчанию 15)
        $perPage = $request->input('per_page', 15);
        
        // Сортируем записи от самых свежих к старым и применяем пагинацию
        $movements = $query->orderByDesc('id')->paginate($perPage);

        // Возвращаем результат, завернутый в ресурсную коллекцию
        return StockMovementResource::collection($movements);
    }
}