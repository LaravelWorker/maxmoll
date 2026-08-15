<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Модель User (пользователь системы / администратор / менеджер).
 * Представляет учетную запись пользователя в базе данных, поддерживает аутентификацию 
 * через Laravel Sanctum, генерацию токенов и систему уведомлений.
 *
 * @property int $id Уникальный идентификатор пользователя
 * @property string $name Имя пользователя
 * @property string $email Адрес электронной почты (используется для входа)
 * @property \Illuminate\Support\Carbon|null $email_verified_at Дата и время подтверждения электронной почты
 * @property string $password Хэш пароля пользователя
 * @property string|null $remember_token Токен для функционала "Запомнить меня"
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время создания учетной записи
 * @property \Illuminate\Support\Carbon|null $updated_at Дата и время последнего обновления учетной записи
 * 
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications Коллекция уведомлений пользователя
 * @property-read \Laravel\Sanctum\PersonalAccessTokenCollection|\Laravel\Sanctum\PersonalAccessToken[] $tokens Коллекция персональных токенов доступа Sanctum
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Атрибуты, разрешенные для массового заполнения (Mass Assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Атрибуты, которые должны быть скрыты при сериализации модели (например, в JSON-ответах).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Преобразование типов данных для атрибутов (Casts).
     * Гарантирует приведение даты верификации email к объекту Carbon.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}