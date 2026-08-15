<?php

namespace App\Models;

use App\Consts\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель Order (заказ).
 * Представляет документ заказа клиента, содержащий информацию о статусе, складе отгрузки, 
 * а также связанные товарные позиции и покупателя.
 *
 * @property int $id Уникальный идентификатор заказа
 * @property int $customer_id Идентификатор клиента, оформившего заказ
 * @property int $warehouse_id Идентификатор склада, с которого производится отгрузка
 * @property OrderStatus $status Текущий статус заказа (из перечисления OrderStatus)
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время создания заказа
 * @property \Illuminate\Support\Carbon|null $completed_at Дата и время фактического завершения/закрытия заказа
 * 
 * @property-read Customer $customer Покупатель, связанный с заказом
 * @property-read Warehouse $warehouse Склад отгрузки
 * @property-read \Illuminate\Database\Eloquent\Collection|OrderItem[] $items Список товарных позиций в заказе
 */
class Order extends Model
{
    use HasFactory;

    /**
     * Отключение поля updated_at.
     * Так как таблица заказов не предполагает изменения после создания (статусы меняются или документ проводится единоразово),
     * константа устанавливается в null, чтобы Eloquent не пытался обновлять несуществующую колонку.
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
        'customer_id',
        'warehouse_id',
        'status',
        'created_at',
        'completed_at',
    ];

    /**
     * Преобразование типов данных для атрибутов (Casts).
     * Автоматически преобразует колонку статуса в Enum-класс OrderStatus, 
     * а также даты в объекты Carbon.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'       => OrderStatus::class,
        'created_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Получить покупателя, который оформил данный заказ.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Получить склад, с которого осуществляется отгрузка товаров по заказу.
     * Отношение "Один к Многим (обратное)" (BelongsTo).
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Получить список позиций (товаров и их количеств), входящих в состав заказа.
     * Отношение "Один к Многим" (HasMany).
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}