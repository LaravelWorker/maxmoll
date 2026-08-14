<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'stock',
    ];

    // Указываем, что у модели нет автоинкрементного id
    public $incrementing = false;

    // Указываем составной первичный ключ (для корректной работы update/save)
    protected $primaryKey = ['product_id', 'warehouse_id'];

    /**
     * Переопределяем метод получения ключа для сохранения, 
     * чтобы Eloquent корректно работал с составным ключом.
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