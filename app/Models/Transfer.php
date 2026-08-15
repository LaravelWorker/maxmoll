<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель Transfer (документ межскладского перемещения).
 * Представляет документ перемещения товаров между складами, содержащий информацию 
 * об отправляющем и принимающем складах, дате создания и связанных товарных позициях.
 *
 * @property int $id Уникальный идентификатор перемещения
 * @property int $from_warehouse_id Идентификатор склада-отправителя
 * @property int $to_warehouse_id Идентификатор склада-получателя
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время создания (проведения) перемещения
 * 
 * @property-read Warehouse $fromWarehouse Склад-отправитель
 * @property-read Warehouse $toWarehouse Склад-получатель
 * @property-read \Illuminate\Database\Eloquent\Collection|TransferItem[] $items Список товарных позиций в перемещении
 */
class Transfer extends Model
{
    use HasFactory;

    /**
     * Отключение поля updated_at.
     * Документы перемещений являются неизменяемыми после проведения, 
     * поэтому константа устанавливается в null, чтобы Eloquent не пытался обновлять несуществующую колонку.
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
        'from_warehouse_id',
        'to_warehouse_id',
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
     * Получить склад, с которого производится списание товаров (отправитель).
     * Отношение "Один к Многим (обратное)" (BelongsTo) с явным указанием внешнего ключа.
     *
     * @return BelongsTo
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Получить склад, на который поступают товары (получатель).
     * Отношение "Один к Многим (обратное)" (BelongsTo) с явным указанием внешнего ключа.
     *
     * @return BelongsTo
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Получить список товарных позиций, участвующих в данном перемещении.
     * Отношение "Один к Многим" (HasMany).
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }
}