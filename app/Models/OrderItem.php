<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель OrderItem (позиция заказа).
 * Представляет отдельную товарную позицию внутри конкретного заказа, 
 * связывая заказ с каталогом товаров и фиксируя заказанное количество.
 *
 * @property int $id Уникальный идентификатор позиции заказа
 * @property int $order_id Идентификатор родительского заказа
 * @property int $product_id Идентификатор товара
 * @property int $count Количество единиц товара в позиции
 * 
 * @property-read Order $order Заказ, к которому относится данная позиция
 * @property-read Product $product Товар, выбранный в позиции
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * Отключение автоматического управления временными метками (timestamps).
     * Таблица позиций заказа не содержит полей created_at и updated_at, 
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
        'order_id',
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
     * Получить заказ, которому принадлежит данная позиция.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Получить товар, связанный с данной позицией заказа.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}