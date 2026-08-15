<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель SupplyItem (позиция поставки).
 * Представляет отдельную товарную позицию внутри конкретного документа поставки, 
 * связывая поставку с каталогом товаров и фиксируя поступившее количество.
 *
 * @property int $id Уникальный идентификатор позиции поставки
 * @property int $supply_id Идентификатор родительского документа поставки
 * @property int $product_id Идентификатор поступившего товара
 * @property int $count Количество единиц товара в позиции
 * 
 * @property-read Supply $supply Поставка, к которой относится данная позиция
 * @property-read Product $product Товар, зафиксированный в позиции
 */
class SupplyItem extends Model
{
    use HasFactory;

    /**
     * Отключение автоматического управления временными метками (timestamps).
     * Таблица позиций поставок не содержит колонок created_at и updated_at, 
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
        'supply_id',
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
     * Получить документ поставки, которому принадлежит данная позиция.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    /**
     * Получить товар, связанный с данной позицией поставки.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}