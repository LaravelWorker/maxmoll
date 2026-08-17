<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * Получить список заказов с фильтрацией и пагинацией.
     *
     * @param OrderIndexRequest $request Запрос с параметрами фильтрации и пагинации
     * @return AnonymousResourceCollection Пагинированная коллекция заказов
     */
    public function index(OrderIndexRequest $request): AnonymousResourceCollection
    {
        Log::info($request->all());
        $query = Order::query()->with(['customer', 'warehouse', 'items.product']);

        // Фильтрация по названию покупателя
        if ($request->filled('customer_search')) {
            $customerSearch = $request->input('customer_search');
            $query->whereHas('customer', function ($q) use ($customerSearch) {
                $q->where('name', 'like', "%{$customerSearch}%");
            });
        }

        // Фильтрация по названию склада
        if ($request->filled('warehouse_search')) {
            $warehouseSearch = $request->input('warehouse_search');
            $query->whereHas('warehouse', function ($q) use ($warehouseSearch) {
                $q->where('name', 'like', "%{$warehouseSearch}%");
            });
        }

        // Фильтрация по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Параметр пагинации по умолчанию — 15 элементов на страницу
        $perPage = $request->input('per_page', 15);

        // Сортировка по последнему идентификатору и пагинация списка
        $orders = $query->orderByDesc('id')->paginate($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Создать новый заказ.
     *
     * @param StoreOrderRequest $request
     * @param OrderService $orderService
     * @return OrderResource|JsonResponse
     */
    public function store(StoreOrderRequest $request, OrderService $orderService): OrderResource|JsonResponse
    {
        try {
            $order = $orderService->create($request->validated());
            $order->load(['customer', 'warehouse', 'items.product']);

            return OrderResource::make($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Обновить заказ.
     *
     * @param UpdateOrderRequest $request
     * @param Order $order
     * @param OrderService $orderService
     * @return OrderResource|JsonResponse
     */
    public function update(UpdateOrderRequest $request, Order $order, OrderService $orderService): OrderResource|JsonResponse
    {
        try {
            $order = $orderService->update($order, $request->validated());
            $order->load(['customer', 'warehouse', 'items.product']);

            return OrderResource::make($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Завершить заказ (списание остатков со склада).
     *
     * @param Order $order
     * @param OrderService $orderService
     * @return OrderResource|JsonResponse
     */
    public function complete(Order $order, OrderService $orderService): OrderResource|JsonResponse
    {
        try {
            $order = $orderService->complete($order);
            $order->load(['customer', 'warehouse', 'items.product']);

            return OrderResource::make($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Отменить заказ.
     *
     * @param Order $order
     * @param OrderService $orderService
     * @return OrderResource|JsonResponse
     */
    public function cancel(Order $order, OrderService $orderService): OrderResource|JsonResponse
    {
        try {
            $order = $orderService->cancel($order);
            $order->load(['customer', 'warehouse', 'items.product']);

            return OrderResource::make($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Возобновить отмененный заказ.
     *
     * @param Order $order
     * @param OrderService $orderService
     * @return OrderResource|JsonResponse
     */
    public function restore(Order $order, OrderService $orderService): OrderResource|JsonResponse
    {
        try {
            $order = $orderService->restore($order);
            $order->load(['customer', 'warehouse', 'items.product']);

            return OrderResource::make($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Удалить заказ.
     *
     * @param Order $order
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function destroy(Order $order, OrderService $orderService): JsonResponse
    {
        try {
            $orderService->destroy($order);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}