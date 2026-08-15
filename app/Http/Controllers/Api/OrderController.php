<?php

namespace App\Http\Controllers\Api;

use App\Consts\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Services\StockService;

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
        // Базовый запрос с необходимыми связями для ответа
        $query = Order::query()->with(['customer', 'warehouse', 'items.product']);

        // Фильтрация по статусу заказа, если указан
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Фильтрация по идентификатору клиента
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Фильтрация по складу
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Фильтрация по дате от
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        // Фильтрация по дате до
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Параметр пагинации (количество элементов на страницу)
        $perPage = $request->input('per_page', 15);

        // Сортируем по убыванию id и выполняем пагинацию
        $orders = $query->orderByDesc('id')->paginate($perPage);

        // Возвращаем коллекцию ресурсов заказов
        return OrderResource::collection($orders);
    }

    /**
     * Создать новый заказ.
     *
     * Оборачивает создание заказа и его позиций в транзакцию, чтобы
     * обеспечить целостность данных. Входные данные валидируются
     * через `StoreOrderRequest`.
     *
     * @param  \App\Http\Requests\StoreOrderRequest  $request  Валидированные данные для создания заказа
     * @return \App\Http\Resources\OrderResource  Созданный ресурс заказа
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        // Создаём заказ в транзакции, чтобы при ошибке откатить все изменения
        $order = DB::transaction(function () use ($request) {
            // Создаем основную запись заказа
            $order = Order::create([
                'customer_id'  => $request->input('customer_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'status'       => OrderStatus::ACTIVE->value,
                'created_at'   => now(),
            ]);

            // Преобразуем входные позиции в формат для массового создания
            $itemsData = array_map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'count'      => $item['count'],
                ];
            }, $request->input('items'));

            // Создаём позиции заказа
            $order->items()->createMany($itemsData);

            return $order;
        });

        // Подгружаем связи для корректного формирования ответа
        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
    }

    /**
     * Обновить заказ.
     *
     * Проверяет, что заказ находится в статусе `ACTIVE` перед редактированием,
     * затем в транзакции обновляет основные поля и при необходимости
     * пересоздаёт позиции заказа.
     *
     * @param  \App\Http\Requests\UpdateOrderRequest  $request  Валидированные изменения
     * @param  \App\Models\Order  $order  Модель заказа для обновления
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse  Обновлённый ресурс или JSON с ошибкой
     */
    public function update(UpdateOrderRequest $request, Order $order): OrderResource|JsonResponse
    {
        // Нельзя редактировать завершенные или отмененные заказы
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Нельзя редактировать выполненный или отмененный заказ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Обновляем заказ и его позиции в транзакции
        DB::transaction(function () use ($request, $order) {
            // Обновляем основные поля заказа
            $order->update($request->only(['customer_id', 'warehouse_id']));

            // Если переданы позиции — удаляем старые и создаём новые
            if ($request->has('items')) {
                $order->items()->delete();

                $itemsData = array_map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'count'      => $item['count'],
                    ];
                }, $request->input('items'));

                $order->items()->createMany($itemsData);
            }
        });

        // Подгружаем связи для ответа
        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
    }

    /**
     * Завершить заказ (списание остатков со склада).
     *
     * В транзакции проверяет наличие достаточных остатков по каждой позиции,
     * блокируя запись `Stock` для безопасного обновления, уменьшает остатки
     * и регистрирует изменение через `StockService`. В случае ошибки
     * выбрасывается исключение и транзакция откатывается.
     *
     * @param  \App\Models\Order  $order  Заказ для завершения
     * @param  \App\Services\StockService  $stockService  Сервис для регистрации изменений стока
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse  Обновлённый ресурс или JSON с ошибкой
     */
    public function complete(Order $order, StockService $stockService): OrderResource|JsonResponse
    {
        // Разрешено завершать только активные заказы
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Завершить можно только заказ в статусе "active".',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::transaction(function () use ($order, $stockService) {
                // Получаем позиции заказа
                $items = $order->items;

                foreach ($items as $item) {
                    // Блокируем запись остатка для безопасного обновления
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('warehouse_id', $order->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    // Если остатков недостаточно — прерываем с ошибкой
                    if (!$stock || $stock->stock < $item->count) {
                        $available = $stock ? $stock->stock : 0;
                        throw new \Exception("Недостаточно товара (ID: {$item->product_id}) на складе (ID: {$order->warehouse_id}). В наличии: {$available}, требуется: {$item->count}");
                    }

                    // Списываем остаток
                    $stock->decrement('stock', $item->count);

                    // Регистрируем изменение через сервис (для истории/логов)
                    $stockService->changeStock(
                        $order->warehouse_id,
                        $item->product_id,
                        -$item->count,
                        $order
                    );
                }

                // Отмечаем заказ как выполненный и записываем время выполнения
                $order->update([
                    'status'       => OrderStatus::COMPLETED->value,
                    'completed_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            // При ошибке возвращаем сообщение клиента с HTTP 422
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Подгружаем связи для формирования ответа
        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
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
}