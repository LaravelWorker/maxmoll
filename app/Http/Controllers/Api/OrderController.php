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
     * Получить список заказов с фильтрацией и пагинацией
     */
    public function index(OrderIndexRequest $request): AnonymousResourceCollection
    {
        $query = Order::query()->with(['customer', 'warehouse', 'items.product']);

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Фильтр по клиенту
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Фильтр по складу
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Фильтр по дате создания
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->orderByDesc('id')->paginate($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Создать новый заказ
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        $order = DB::transaction(function () use ($request) {
            $order = Order::create([
                'customer_id'  => $request->input('customer_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'status'       => OrderStatus::ACTIVE->value,
                'created_at'   => now(),
            ]);

            $itemsData = array_map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'count'      => $item['count'],
                ];
            }, $request->input('items'));

            $order->items()->createMany($itemsData);

            return $order;
        });

        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
    }

    /**
     * Обновить заказ
     */
    public function update(UpdateOrderRequest $request, Order $order): OrderResource|JsonResponse
    {
        // Нельзя редактировать завершенные или отмененные заказы
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Нельзя редактировать выполненный или отмененный заказ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($request, $order) {
            $order->update($request->only(['customer_id', 'warehouse_id']));

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

        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
    }

    /**
     * Завершить заказ (списание остатков со склада)
     */
    public function complete(Order $order, StockService $stockService): OrderResource|JsonResponse
    {
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Завершить можно только заказ в статусе "active".',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::transaction(function () use ($order, $stockService) {
                $items = $order->items;

                foreach ($items as $item) {
                    // Блокируем запись остатка для безопасного обновления
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('warehouse_id', $order->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || $stock->stock < $item->count) {
                        $available = $stock ? $stock->stock : 0;
                        throw new \Exception("Недостаточно товара (ID: {$item->product_id}) на складе (ID: {$order->warehouse_id}). В наличии: {$available}, требуется: {$item->count}");
                    }

                    $stock->decrement('stock', $item->count);

                    $stockService->changeStock(
                        $order->warehouse_id,
                        $item->product_id,
                        -$item->count,
                        $order
                    );
                }

                $order->update([
                    'status'       => OrderStatus::COMPLETED->value,
                    'completed_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
    }

    /**
     * Отменить заказ
     */
    public function cancel(Order $order): OrderResource|JsonResponse
    {
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Отменить можно только активный заказ.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->update([
            'status' => OrderStatus::CANCELED->value,
        ]);

        $order->load(['customer', 'warehouse', 'items.product']);

        return OrderResource::make($order);
    }

    /**
     * Удаление заказа
     */
    public function destroy(Order $order): JsonResponse
    {
        if ($order->status !== OrderStatus::ACTIVE) {
            return response()->json([
                'message' => 'Выполненный или отменённый заказ удалить нельзя.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}