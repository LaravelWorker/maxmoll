<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockIndexRequest;
use App\Http\Resources\StockResource;
use App\Models\Stock;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockController extends Controller
{
    /**
     * Получить список остатков товаров по складам с пагинацией и поиском.
     *
     * @param StockIndexRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(StockIndexRequest $request): AnonymousResourceCollection
    {
        $query = Stock::query()->with(['warehouse', 'product']);

        // Регистронезависимый поиск по названию склада
        if ($request->filled('warehouse')) {
            $warehouseName = $request->input('warehouse');
            $query->whereHas('warehouse', function ($q) use ($warehouseName) {
                $q->where('name', 'like', "%{$warehouseName}%");
            });
        }

        // Регистронезависимый поиск по названию товара
        if ($request->filled('product')) {
            $productName = mb_strtolower(trim($request->input('product')));
            $query->whereHas('product', function ($q) use ($productName) {
                $q->where('name', 'like', "%{$productName}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);

        $stocks = $query->orderBy('warehouse_id')->paginate($perPage);

        return StockResource::collection($stocks);
    }
}