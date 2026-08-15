<?php

namespace App\Http\Controllers\Api;

use App\Consts\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{

    /**
     * Получить список заказов с фильтрацией и пагинацией.
     *
     * Строит запрос к модели `Order` с опциональными фильтрами (статус,
     * клиент, склад, диапазон дат) и возвращает пагинированную коллекцию
     * `OrderResource`.
     *
     * @param  \App\Http\Requests\OrderIndexRequest  $request  Запрос с параметрами фильтрации и пагинации
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Пагинированная коллекция заказов
     */
    public function index(OrderIndexRequest $request): AnonymousResourceCollection
    {
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

        // Возвращаем ответ в виде ресурса заказа
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
            // Передаём бизнес-логику создания заказа в сервис
            $order = $orderService->create($request->validated());

            // Подгружаем связи для ответа
            $order->load(['customer', 'warehouse', 'items.product']);

            // Возвращаем созданный заказ как JSON-ресурс
            return OrderResource::make($order);
        } catch (\Exception $e) {
            // В случае ошибки формируем понятный JSON-ответ клиенту
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
            // Обновляем заказ через сервис и получаем актуальную модель
            $order = $orderService->update($order, $request->validated());

            // Подгружаем связанные данные для ответа
            $order->load(['customer', 'warehouse', 'items.product']);

            // Возвращаем обновлённый заказ
            return OrderResource::make($order);
        } catch (\Exception $e) {
            // Возвращаем сообщение об ошибке в одном формате
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Завершить заказ (списание остатков со склада).
     *
     * Метод делегирует всю бизнес-логику завершения заказа в OrderService
     * (транзакция, pessimistic locking, списание остатков, Ledger и обновление статуса).
     *
     * @param \App\Models\Order $order Заказ для завершения
     * @param \App\Services\OrderService $orderService Сервис управления заказами
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse Обновлённый ресурс или JSON с ошибкой
     */
    public function complete(Order $order, OrderService $orderService): OrderResource|JsonResponse
    {
        try {
            // Выполняем бизнес-логику через сервисный слой
            $order = $orderService->complete($order);

            // Подгружаем связи для формирования ответа
            $order->load(['customer', 'warehouse', 'items.product']);

            // Возвращаем завершённый заказ в API-формате
            return OrderResource::make($order);

        } catch (\Exception $e) {
            // При ошибке (нехватка остатков, неверный статус) возвращаем HTTP 422
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Отменить заказ.
     *
     * Переводит заказ в статус `CANCELED` если он ещё активен.
     *
     * @param  \App\Models\Order  $order  Отменяемый заказ
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse  Обновлённый ресурс или JSON с ошибкой
     */
    public function cancel(Order $order): OrderResource|JsonResponse
    {
        // Разрешено отменять только активные заказы
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Отменить можно только активный заказ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Меняем статус на отменённый
        $order->update([
            'status' => OrderStatus::CANCELED->value,
        ]);

        // Подгружаем связи и возвращаем ресурс
        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
    }

    /**
     * Удаление заказа.
     *
     * Удаляет заказ из базы, только если он ещё активен. Возвращает
     * HTTP 204 при успешном удалении.
     *
     * @param  \App\Models\Order  $order  Удаляемый заказ
     * @return \Illuminate\Http\JsonResponse  Пустой ответ с кодом 204 или JSON с ошибкой
     */
    public function destroy(Order $order): JsonResponse
    {
        // Нельзя удалять выполненные или отменённые заказы
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Выполненный или отменённый заказ удалить нельзя.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Удаляем заказ
        $order->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Возобновить отмененный заказ.
     *
     * Метод переводит заказ из статуса 'canceled' обратно в 'active'.
     *
     * @param \App\Models\Order $order
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse
     */
    public function restore(Order $order): OrderResource|JsonResponse
    {
        // Возобновить можно только отмененный заказ
        if ($order->status !== OrderStatus::CANCELED) {
            return response()->json([
                'message' => 'Возобновить можно только заказ в статусе "canceled".',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Меняем статус на активный
            $order->update([
                'status' => OrderStatus::ACTIVE->value,
            ]);

            // Подгружаем связи для корректного ответа
            $order->load(['customer', 'warehouse', 'items.product']);

            // Возвращаем обновлённый заказ
            return OrderResource::make($order);

        } catch (\Exception $e) {
            // Возвращаем описание ошибки при сбое операции
            return response()->json([
                'message' => 'Ошибка при возобновлении заказа: ' . $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}