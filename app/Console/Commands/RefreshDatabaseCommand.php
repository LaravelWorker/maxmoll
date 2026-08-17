<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshDatabaseCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'app:db-refresh {--force : Принудительный запуск без подтверждения}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Пересоздает структуру БД и заполняет её тестовыми данными';

    /**
     * Выполнить консольную команду.
     */
    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('Вы запускаете команду в продакшн-окружении! Продолжить?')) {
                $this->warn('Операция отменена.');
                return self::FAILURE;
            }
        }

        $this->info('Сброс и пересоздание всех таблиц...');
        
        $exitCode = $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        if ($exitCode === self::SUCCESS) {
            $this->newLine();
            $this->info('База данных успешно обновлена и заполнена тестовыми данными!');
            return self::SUCCESS;
        }

        $this->error('Произошла ошибка при сидинге базы данных.');
        return self::FAILURE;
    }
}