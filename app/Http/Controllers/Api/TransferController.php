<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Models\Stock;
use App\Models\Transfer;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TransferController extends Controller
{
    /**
     * Оформить и провести перемещение товаров между складами
     */
    public function store(StoreTransferRequest $request, StockService $stockService): JsonResponse
    {
        try {
            $transfer = DB::transaction(function () use ($request, $stockService) {
                $fromId = $request->input('from_warehouse_id');
                $toId   = $request->input('to_warehouse_id');

                $transfer = Transfer::create([
                    'from_warehouse_id' => $fromId,
                    'to_warehouse_id'   => $toId,
                    'created_at'        => now(),
                ]);

                foreach ($request->input('items') as $item) {
                    $productId = $item['product_id'];
                    $count     = $item['count'];

                    // 1. Проверяем наличие на складе-источнике с блокировкой
                    $stock = Stock::where('product_id', $productId)
                        ->where('warehouse_id', $fromId)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || $stock->stock < $count) {
                        $available = $stock ? $stock->stock : 0;
                        throw new \Exception("Недостаточно товара (ID: {$productId}) на складе отправителе (ID: {$fromId}). В наличии: {$available}, требуется: {$count}");
                    }

                    // 2. Создаем позицию перемещения
                    $transfer->items()->create([
                        'product_id' => $productId,
                        'count'      => $count,
                    ]);

                    // 3. Списываем со склада-отправителя (-count)
                    $stockService->changeStock($fromId, $productId, -$count, $transfer);

                    // 4. Зачисляем на склад-получатель (+count)
                    $stockService->changeStock($toId, $productId, $count, $transfer);
                }

                return $transfer;
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);

        return response()->json(['data' => $transfer], Response::HTTP_CREATED);
    }
}