<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель StockMovement (движение товара / запись журнала).
 * Представляет запись об изменении остатка товара на складе с привязкой 
 * к конкретному документу-основанию (Заказ, Поставка, Перемещение).
 *
 * @property int $id Уникальный идентификатор записи движения
 * @property int $warehouse_id Идентификатор склада, на котором произошло изменение
 * @property int $product_id Идентификатор товара
 * @property int $quantity Величина изменения остатка (положительное — приход, отрицательное — списание)
 * @property string $doc_type Тип полиморфного документа-источника
 * @property int $doc_id Идентификатор документа-источника
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время фиксации движения
 * 
 * @property-read Model|Order|Supply|Transfer $doc Документ-основание, инициировавший движение
 * @property-read Warehouse $warehouse Склад, на котором зафиксировано движение
 * @property-read Product $product Товар, по которому произошло движение
 */
class StockMovement extends Model
{
    use HasFactory;

    /**
     * Отключение поля updated_at.
     * Записи в журнале движений являются неизменяемыми (immutable), 
     * поэтому обновление данных не предусмотрено.
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
        'product_id',
        'quantity',
        'doc_type',
        'doc_id',
        'created_at',
    ];

    /**
     * Преобразование типов данных для атрибутов (Casts).
     * Количество приводится к целяку, а дата создания — к объекту Carbon.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity'   => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Получить документ-основание (полиморфная связь).
     * Позволяет получить экземпляр связанного документа (Order, Supply, Transfer и т.д.), 
     * который привел к созданию этой записи в журнале остатков.
     *
     * @return MorphTo
     */
    public function doc(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Получить склад, на котором произошло движение товара.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Получить товар, участвующий в движении.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}