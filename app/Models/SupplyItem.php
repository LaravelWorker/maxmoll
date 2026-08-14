<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'supply_id',
        'product_id',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}