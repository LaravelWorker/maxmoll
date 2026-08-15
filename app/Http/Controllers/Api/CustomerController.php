<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerIndexRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{       
    /**
     * Получить список клиентов с фильтрацией и пагинацией.
     *
     * Выполняет построение запроса к модели `Customer` в зависимости от
     * переданных параметров фильтрации, затем возвращает коллекцию
     * ресурсов `CustomerResource` с пагинацией.
     *
     * @param  \App\Http\Requests\CustomerIndexRequest  $request  Запрос с параметрами фильтрации и пагинации
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Коллекция ресурсов клиентов
     */
    public function index(CustomerIndexRequest $request): AnonymousResourceCollection
    {
        // Инициализируем базовый запрос к модели клиентов
        $query = Customer::query();

        // Если задано фильтр по имени — добавляем условие LIKE
        if ($request->filled('name')) {
            // Используем поиск подстроки по полю `name`
            $query->where('name', 'like', "%$request->input('name')%");
        }

        // Если задан фильтр по телефону — добавляем условие LIKE
        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%$request->input('phone')%");
        }

        // Если задан фильтр по email — добавляем условие LIKE
        if ($request->filled('email')) {
            $query->where('email', 'like', "%$request->input('email')%");
        }

        // Если задан общий параметр `search` — выполняем поиск по нескольким полям
        if ($request->filled('search')) {
            // Сохраняем значение поиска в переменную для использования в замыкании
            $search = $request->input('search');

            // Группируем OR-условия в замыкании, чтобы корректно сочетать с другими фильтрами
            $query->where(function ($q) use ($search) {
                // Поиск совпадений в `name`, `phone` или `email`
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Получаем параметр пагинации `per_page`, по умолчанию 15
        $perPage = $request->input('per_page', 15);

        // Выполняем сортировку по убыванию id и пагинацию результатов
        $customers = $query->orderByDesc('id')->paginate($perPage);

        // Возвращаем коллекцию ресурсов клиентов (форматированный API-ответ)
        return CustomerResource::collection($customers);
    }

    /**
     * Создать нового клиента.
     *
     * Валидирует входящие данные через `StoreCustomerRequest`, создаёт
     * запись в базе и возвращает созданный ресурс клиента.
     *
     * @param  \App\Http\Requests\StoreCustomerRequest  $request  Валидированный запрос для создания клиента
     * @return \App\Http\Resources\CustomerResource  Созданный ресурс клиента
     */
    public function store(StoreCustomerRequest $request): CustomerResource
    {
        // Создаём клиента на основе валидированных данных
        $customer = Customer::create($request->validated());

        // Возвращаем ресурс клиента для единообразного API-ответа
        return CustomerResource::make($customer);
    }

    /**
     * Обновить данные клиента.
     *
     * Применяет валидированные изменения к существующей модели `Customer`
     * и возвращает обновлённый ресурс.
     *
     * @param  \App\Http\Requests\UpdateCustomerRequest  $request  Валидированный запрос с изменениями
     * @param  \App\Models\Customer  $customer  Модель клиента, которую нужно обновить
     * @return \App\Http\Resources\CustomerResource  Ресурс с обновлёнными данными клиента
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        // Обновляем модель клиента валидированными данными из запроса
        $customer->update($request->validated());

        // Возвращаем ресурс с обновлёнными данными
        return CustomerResource::make($customer);
    }
}