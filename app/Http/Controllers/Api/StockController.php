<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    /**
     * Получить список остатков товаров по складам с пагинацией и поиском.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Stock::query()->with(['warehouse', 'product']);

        // Регистронезависимый поиск по названию склада
        if ($request->filled('warehouse')) {
            Log::info('Warehouse filter: ' . $request->input('warehouse'));
            $warehouseName = mb_strtolower(trim($request->input('warehouse')));
            $query->whereHas('warehouse', function ($q) use ($warehouseName) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$warehouseName}%"]);
            });
        }

        // Регистронезависимый поиск по названию товара
        if ($request->filled('product')) {
            $productName = mb_strtolower(trim($request->input('product')));
            $query->whereHas('product', function ($q) use ($productName) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$productName}%"]);
            });
        }

        // Строгая фильтрация по ID склада (если передается из других модулей)
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Валидация пагинации (ограничение от 1 до 100 элементов)
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $stocks = $query->orderBy('warehouse_id')->paginate($perPage);

        return response()->json($stocks);
    }
}