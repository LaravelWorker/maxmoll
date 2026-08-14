<?php

namespace App\Consts;

enum OrderStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    /**
     * Получить массив всех возможных значений (полезно для валидации в Request)
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}