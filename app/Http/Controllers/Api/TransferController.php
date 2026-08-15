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
     * Оформить и провести межскладское перемещение товаров.
     *
     * Метод обрабатывает входящий запрос на перемещение партии товаров с одного склада на другой.
     * Вся логика выполняется в рамках единой базы данных в транзакции с блокировкой строк (Pessimistic Locking),
     * что предотвращает гонки данных (race conditions) при параллельных запросах.
     * При нехватке остатков транзакция откатывается, а клиент получает ошибку валидации.
     *
     * @param StoreTransferRequest $request Запрос, содержащий идентификаторы складов и список позиций (товары и количество)
     * @param StockService $stockService Сервис управления остатками и историей движений (Ledger)
     * @return JsonResponse Возвращает JSON с данными созданного перемещения и кодом 201 (Created) либо ошибку 422
     * 
     * @throws \Exception Выбрасывается при нехватке остатков или ошибках выполнения транзакции
     */
    public function store(StoreTransferRequest $request, StockService $stockService): JsonResponse
    {
        try {
            // Выполняем все операции перемещения атомарно в рамках базы данных
            $transfer = DB::transaction(function () use ($request, $stockService) {
                $fromId = $request->input('from_warehouse_id');
                $toId   = $request->input('to_warehouse_id');

                // Создаем документ перемещения в статусе "проведено"
                $transfer = Transfer::create([
                    'from_warehouse_id' => $fromId,
                    'to_warehouse_id'   => $toId,
                    'created_at'        => now(),
                ]);

                // Итеративно обрабатываем каждую товарную позицию из запроса
                foreach ($request->input('items') as $item) {
                    $productId = $item['product_id'];
                    $count     = $item['count'];

                    // 1. Проверяем наличие остатков на складе-источнике с блокировкой строки на чтение/запись
                    $stock = Stock::where('product_id', $productId)
                        ->where('warehouse_id', $fromId)
                        ->lockForUpdate()
                        ->first();

                    // Если товара нет вообще или его количество меньше требуемого — прерываем транзакцию
                    if (!$stock || $stock->stock < $count) {
                        $available = $stock ? $stock->stock : 0;
                        throw new \Exception("Недостаточно товара (ID: {$productId}) на складе отправителе (ID: {$fromId}). В наличии: {$available}, требуется: {$count}");
                    }

                    // 2. Создаем связанную позицию внутри документа перемещения
                    $transfer->items()->create([
                        'product_id' => $productId,
                        'count'      => $count,
                    ]);

                    // 3. Списываем товар со склада-отправителя через сервисный слой (фиксирует отрицательное движение)
                    $stockService->changeStock($fromId, $productId, -$count, $transfer);

                    // 4. Зачисляем товар на склад-получатель через сервисный слой (фиксирует положительное движение)
                    $stockService->changeStock($toId, $productId, $count, $transfer);
                }

                return $transfer;
            });
        } catch (\Exception $e) {
            // В случае возникновения ошибки откатываем транзакцию и возвращаем сообщение с кодом 422 Unprocessable Entity
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Подгружаем связанные отношения для формирования полного и красивого ответа клиенту
        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);

        return response()->json(['data' => $transfer], Response::HTTP_CREATED);
    }
}