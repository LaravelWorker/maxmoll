<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Модель Warehouse (склад).
 * Представляет складское помещение или точку хранения товаров, 
 * управляющую связанными заказами, поставками и остатками товаров.
 *
 * @property int $id Уникальный идентификатор склада
 * @property string $name Наименование склада
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|Order[] $orders Заказы, отгружаемые с данного склада
 * @property-read \Illuminate\Database\Eloquent\Collection|Product[] $products Товары, хранящиеся на складе (с pivot-данными об остатках)
 * @property-read \Illuminate\Database\Eloquent\Collection|Supply[] $supplies Поставки, поступающие на данный склад
 */
class Warehouse extends Model
{
    use HasFactory;

    /**
     * Отключение автоматического управления временными метками (timestamps).
     * Таблица складов не содержит колонок created_at и updated_at, 
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
    ];

    /**
     * Получить список заказов, привязанных к данному складу для отгрузки.
     * Отношение "Один к Многим" (HasMany).
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Получить товары, находящиеся на данном складе.
     * Отношение "Многие ко Многим" (BelongsToMany) через промежуточную таблицу 'stocks'.
     * Метод подтягивает текущий остаток из pivot-колонки 'stock'.
     *
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'stocks')
                ->withPivot('stock');
    }

    /**
     * Получить список документов поставок, поступивших на данный склад.
     * Отношение "Один к Многим" (HasMany).
     *
     * @return HasMany
     */
    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }
}