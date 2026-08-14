<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'doc_type',
        'doc_id',
        'created_at',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Связь с документом-источником (Order, Supply, Transfer)
     */
    public function doc(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'doc_type', 'doc_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}