<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementIndexRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    /**
     * Просмотр истории движений остатков
     */
    public function index(StockMovementIndexRequest $request): AnonymousResourceCollection
    {
        $query = StockMovement::query()->with(['warehouse', 'product']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('doc_type')) {
            $query->where('doc_type', $request->input('doc_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = $request->input('per_page', 15);
        $movements = $query->orderByDesc('id')->paginate($perPage);

        return StockMovementResource::collection($movements);
    }
}