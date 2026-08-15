<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransferController extends Controller
{
    /**
     * Оформить и провести межскладское перемещение товаров.
     *
     * Метод обрабатывает входящий запрос на перемещение партии товаров с одного склада на другой.
     * Вся логика инкапсулирована в TransferService и выполняется в рамках транзакции с блокировкой строк.
     *
     * @param StoreTransferRequest $request Запрос, содержащий идентификаторы складов и список позиций
     * @param TransferService $transferService Сервис проведения межскладских перемещений
     * @return JsonResponse Возвращает JSON с данными созданного перемещения и кодом 201 либо ошибку 422
     */
    public function store(StoreTransferRequest $request, TransferService $transferService): JsonResponse
    {
        try {
            // Выполняем бизнес-логику атомарно через сервис
            $transfer = $transferService->createAndExecute($request->validated());

            // Подгружаем связанные отношения для формирования полного ответа клиенту
            $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);

            return response()->json([
                'message' => 'Перемещение успешно создано и проведено.',
                'data'    => $transfer
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            // Возвращаем понятную ошибку валидации/остатков с кодом 422
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}