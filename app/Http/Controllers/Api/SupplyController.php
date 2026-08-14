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
     * Получить список поставок с фильтрацией и пагинацией
     */
    public function index(SupplyIndexRequest $request): AnonymousResourceCollection
    {
        $query = Supply::query()->with(['warehouse', 'items.product']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = $request->input('per_page', 15);
        $supplies = $query->orderByDesc('id')->paginate($perPage);

        return SupplyResource::collection($supplies);
    }

    /**
     * Создать поставку и пополнить остатки на складе
     */
    public function store(StoreSupplyRequest $request, StockService $stockService): SupplyResource
    {
        $supply = DB::transaction(function () use ($request, $stockService) {
            $warehouseId = $request->input('warehouse_id');

            $supply = Supply::create([
                'warehouse_id' => $warehouseId,
                'created_at'   => now(),
            ]);

            foreach ($request->input('items') as $item) {
                $supply->items()->create([
                    'product_id' => $item['product_id'],
                    'count'      => $item['count'],
                ]);

                // Фиксируем приход товара через StockService
                $stockService->changeStock(
                    $warehouseId,
                    $item['product_id'],
                    $item['count'],
                    $supply
                );
            }

            return $supply;
        });

        $supply->load(['warehouse', 'items.product']);

        return SupplyResource::make($supply);
    }
}