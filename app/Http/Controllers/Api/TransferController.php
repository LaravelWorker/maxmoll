<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Resources\TransferResource;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransferController extends Controller
{
    /**
     * Создать и провести документ межскладского перемещения.
     *
     * Метод принимает валидированные данные из StoreTransferRequest,
     * передает управление сервису проведения (в рамках транзакции)
     * и возвращает созданный документ в виде TransferResource.
     *
     * @param StoreTransferRequest $request
     * @param TransferService $transferService
     * @return JsonResponse
     */
    public function store(StoreTransferRequest $request, TransferService $transferService): JsonResponse
    {
        try {
            // Выполняем бизнес-логику через сервис в рамках транзакции
            $transfer = $transferService->createAndExecute($request->validated());

            // Загружаем связи для корректной выдачи через Resource
            $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);

            return (new TransferResource($transfer))
                ->additional(['message' => 'Перемещение успешно создано и проведено.'])
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Возвращаем понятную ошибку в случае сбоя транзакции или нехватки остатков
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
