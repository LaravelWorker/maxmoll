<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplyRequest;
use App\Http\Requests\SupplyIndexRequest;
use App\Http\Resources\SupplyResource;
use App\Models\Stock;
use App\Services\StockService;
use App\Models\Supply;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SupplyController extends Controller
{
    /**
     * Получить список поставок с возможностью фильтрации и пагинацией.
     *
     * Метод выполняет выборку документов поставок из базы данных с предварительной загрузкой 
     * связанных данных (склад, позиции и товары в позициях). Поддерживает фильтрацию по ID склада 
     * и диапазону дат создания, а также постраничную навигацию.
     *
     * @params SupplyIndexRequest $request Валидированный запрос с опциональными параметрами фильтрации и пагинации
     * @return AnonymousResourceCollection Коллекция ресурсов поставок (SupplyResource)
     */
    public function index(SupplyIndexRequest $request): AnonymousResourceCollection
    {
        // Инициируем базовый запрос с подгрузкой необходимых связей для оптимизации (Eager Loading)
        $query = Supply::query()->with(['warehouse', 'items.product']);

        // Применяем фильтр по складу, если передан параметр warehouse_id
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Применяем фильтр по начальной дате периода, если передан date_from
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        // Применяем фильтр по конечной дате периода, если передан date_to
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Получаем размер страницы из запроса или используем значение по умолчанию (15)
        $perPage = $request->input('per_page', 15);
        
        // Сортируем поставки от новых к старым и применяем пагинацию
        $supplies = $query->orderByDesc('id')->paginate($perPage);

        return SupplyResource::collection($supplies);
    }

    /**
     * Создать новую поставку товаров на склад и пополнить остатки.
     *
     * Метод создает документ поставки, сохраняет входящие товарные позиции и в рамках 
     * единой транзакции базы данных через StockService увеличивает количество остатков 
     * на указанном складе, одновременно фиксируя историю движений (Ledger).
     *
     * @params StoreSupplyRequest $request Валидированный запрос, содержащий склад и список поступающих товаров
     * @params StockService $stockService Сервис для изменения складских остатков и ведения аудита
     * @return SupplyResource Ресурс созданной поставки со всеми связанными данными и статусом 201
     * 
     * @throws \Throwable Выбрасывается в случае сбоя транзакции базы данных
     */
    public function store(StoreSupplyRequest $request, StockService $stockService): SupplyResource
    {
        // Выполняем создание поставки и обновление складских остатков атомарно в транзакции
        $supply = DB::transaction(function () use ($request, $stockService) {
            $warehouseId = $request->input('warehouse_id');

            // 1. Создаем основной документ поставки
            $supply = Supply::create([
                'warehouse_id' => $warehouseId,
                'created_at'   => now(),
            ]);

            // 2. Итерируем по списку товаров и создаем позиции поставки
            foreach ($request->input('items') as $item) {
                $supply->items()->create([
                    'product_id' => $item['product_id'],
                    'count'      => $item['count'],
                ]);

                // 3. Увеличиваем остаток товара на складе и фиксируем движение (положительное количество)
                $stockService->changeStock(
                    $warehouseId,
                    $item['product_id'],
                    $item['count'],
                    $supply
                );
            }

            return $supply;
        });

        // Подгружаем актуальные связи для формирования корректного ответа клиенту
        $supply->load(['warehouse', 'items.product']);

        return SupplyResource::make($supply);
    }
}