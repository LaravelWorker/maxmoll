<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Resources\TransferResource;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\TransferService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

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

    /**
     * Создать и провести документ межскладского перемещения.
     *
     * Метод принимает валидированные данные из StoreTransferRequest, 
     * передает управление сервису проведения, формирует проводки в Ledger (stock_movements)
     * и возвращает созданный документ в виде TransferResource.
     *
     * @param StoreTransferRequest $request
     * @param TransferService $transferService
     * @return TransferResource|JsonResponse
     */
    public function storeTransfer(StoreTransferRequest $request, TransferService $transferService): TransferResource|JsonResponse
    {
        try {
            // Выполняем бизнес-логику через сервис в рамках транзакции
            $transfer = $transferService->createAndExecute($request->validated());

            // Загружаем связи для коррекционной выдачи через Resource
            $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);

            return (new TransferResource($transfer))
                ->additional(['message' => 'Перемещение успешно создано и проведено.'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            // Возвращаем понятную ошибку в случае сбоя транзакции
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Просмотр остатков товаров с фильтрацией и пагинацией.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stocks(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $query = \App\Models\Stock::with(['product', 'warehouse']);

        // Фильтр по складу
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Поиск по названию товара
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Настраиваемая пагинация с фолбэком на 15 элементов
        $perPage = (int) $request->input('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Получение списка товаров для выпадающих списков в UI.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function products(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $products = \App\Models\Product::all();
        return \App\Http\Resources\ProductResource::collection($products);
    }
}