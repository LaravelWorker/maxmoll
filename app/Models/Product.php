<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Модель Product (товар).
 * Представляет товарную позицию в каталоге интернет-магазина или учетной системы,
 * содержащую информацию о названии, цене и наличии на складах.
 *
 * @property int $id Уникальный идентификатор товара
 * @property string $name Наименование (название) товара
 * @property float $price Базовая стоимость товара
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|Warehouse[] $warehouses Склады, на которых представлен товар (с pivot-данными об остатках)
 */
class Product extends Model
{
    use HasFactory;

    /**
     * Отключение автоматического управления временными метками (timestamps).
     * Таблица товаров не содержит колонок created_at и updated_at, 
     * поэтому свойство устанавливается в false.
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * Атрибуты, разрешенные для массового заполнения (Mass Assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'price',
    ];

    /**
     * Преобразование типов данных для атрибутов (Casts).
     * Гарантирует приведение цены к числу с плавающей точкой (float).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
    ];

    /**
     * Получить склады, на которых хранится данный товар.
     * Отношение "Многие ко Многим" (BelongsToMany) через промежуточную таблицу 'stocks'.
     * Метод также подтягивает колонку 'stock' из связующей (pivot) таблицы для отображения остатков.
     *
     * @return BelongsToMany
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'stocks', 'product_id', 'warehouse_id')
            ->withPivot(['stock']);
    }
}