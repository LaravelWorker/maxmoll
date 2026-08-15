<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель Customer (покупатель/клиент).
 * Представляет запись о покупателе в базе данных и управляет связями с заказами.
 *
 * @property int $id Уникальный идентификатор покупателя
 * @property string $name Имя или ФИО клиента
 * @property string|null $phone Контактный номер телефона
 * @property string|null $email Адрес электронной почты
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время создания записи
 */
class Customer extends Model
{
    use HasFactory;

    /**
     * Отключение поля updated_at.
     * Так как таблица клиентов не предполагает обновление данных (или система не отслеживает время изменения),
     * константа устанавливается в null, чтобы Eloquent не пытался записать значение в несуществующую колонку.
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
        'name',
        'phone',
        'email',
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
}