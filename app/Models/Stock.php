<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель Stock (складской остаток).
 * Представляет запись об остатке конкретного товара на определенном складе.
 * Использует составной первичный ключ (product_id, warehouse_id) и переопределяет 
 * стандартное поведение Eloquent для корректной работы с такими ключами.
 *
 * @property int $warehouse_id Идентификатор склада
 * @property int $product_id Идентификатор товара
 * @property int $stock Текущее количество товара на складе
 */
class Stock extends Model
{
    /**
     * Отключение автоматического управления временными метками (timestamps).
     * Таблица остатков не содержит колонок created_at и updated_at.
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
        'warehouse_id',
        'product_id',
        'stock',
    ];

    /**
     * Отключение автоинкремента.
     * Таблица использует составной ключ, состоящий из внешних ключей, поэтому автоинкремент не нужен.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Определение составного первичного ключа.
     * Необходимо для корректной работы методов обновления (update) и сохранения (save) модели в Eloquent.
     *
     * @var array<int, string>
     */
    protected $primaryKey = ['product_id', 'warehouse_id'];

    /**
     * Переопределяем метод формирования запроса для сохранения/обновления модели, 
     * чтобы Eloquent корректно подставлял условия по всем полям составного первичного ключа.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }
}