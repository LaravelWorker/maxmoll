<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель TransferItem (позиция перемещения).
 * Представляет отдельную товарную позицию внутри конкретного документа межскладского перемещения, 
 * связывая перемещение с каталогом товаров и фиксируя перемещаемое количество.
 *
 * @property int $id Уникальный идентификатор позиции перемещения
 * @property int $transfer_id Идентификатор родительского документа перемещения
 * @property int $product_id Идентификатор перемещаемого товара
 * @property int $count Количество единиц товара в позиции
 * 
 * @property-read Transfer $transfer Документ перемещения, к которому относится данная позиция
 * @property-read Product $product Товар, зафиксированный в позиции
 */
class TransferItem extends Model
{
    use HasFactory;

    /**
     * Отключение автоматического управления временными метками (timestamps).
     * Таблица позиций перемещений не содержит колонок created_at и updated_at, 
     * поэтому свойство устанавливается в false, чтобы Eloquent не пытался их заполнять.
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
        'transfer_id',
        'product_id',
        'count',
    ];

    /**
     * Преобразование типов данных для атрибутов (Casts).
     * Гарантирует приведение количества к целочисленному типу (integer).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'count' => 'integer',
    ];

    /**
     * Получить документ перемещения, которому принадлежит данная позиция.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * Получить товар, связанный с данной позицией перемещения.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}