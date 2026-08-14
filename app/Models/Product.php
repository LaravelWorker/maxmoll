<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    /**
     * Поля, разрешенные для массового заполнения (Mass Assignment)
     */
    protected $fillable = [
        'name',
        'price',
    ];

    /**
     * Приведение типов атрибутов
     */
    protected $casts = [
        'price' => 'float',
    ];

    /**
     * Склады, на которых есть данный товар (с указанием остатка)
     */
    public function warehouses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'stocks')
                    ->withPivot('stock')
                    ->withTimestamps();
    }
}