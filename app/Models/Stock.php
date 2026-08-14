<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    /**
     * Указываем составной первичный ключ
     */
    protected $primaryKey = ['product_id', 'warehouse_id'];

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'stock',
    ];

    protected $casts = [
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}