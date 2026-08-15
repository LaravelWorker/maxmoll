<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель Supply (документ поставки).
 * Представляет документ поступления товаров на склад, содержащий информацию 
 * о складском помещении, дате проведения и связанных товарных позициях.
 *
 * @property int $id Уникальный идентификатор поставки
 * @property int $warehouse_id Идентификатор склада, на который поступает товар
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время создания (проведения) поставки
 * 
 * @property-read Warehouse $warehouse Склад назначения
 * @property-read \Illuminate\Database\Eloquent\Collection|SupplyItem[] $items Список товарных позиций в поставке
 */
class Supply extends Model
{
    use HasFactory;

    /**
     * Отключение поля updated_at.
     * Документы поставок являются неизменяемыми после проведения, 
     * поэтому константа устанавливается в null, чтобы Eloquent не обновлял несуществующую колонку.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * Атрибуты, разрешенные для массового заполнения (Mass Assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'warehouse_id',
        'created_at',
    ];

    /**
     * Преобразование типов данных для атрибутов (Casts).
     * Обеспечивает автоматическое приведение колонки created_at к объекту даты Carbon.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Получить склад, на который осуществляется поставка товаров.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Получить список товарных позиций, входящих в состав данной поставки.
     * Отношение "Один к Многим" (HasMany).
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(SupplyItem::class);
    }
}