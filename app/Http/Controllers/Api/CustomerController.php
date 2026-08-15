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
        $query = Customer::query();

        // Фильтр по имени
        if ($request->filled('name')) {
            $name = $request->input('name');
            $query->where('name', 'like', "%{$name}%");
        }

        // Фильтр по телефону
        if ($request->filled('phone')) {
            $phone = $request->input('phone');
            $query->where('phone', 'like', "%{$phone}%");
        }

        // Фильтр по email
        if ($request->filled('email')) {
            $email = $request->input('email');
            $query->where('email', 'like', "%{$email}%");
        }

        // Общий поиск по всем полям (резервный)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Устанавливаем количество элементов на страницу на основе запроса клиента
        $perPage = $request->input('per_page', 15);

        // Выполняем сортировку по убыванию id и пагинацию
        $customers = $query->orderByDesc('id')->paginate($perPage);

        // Возвращаем коллекцию ресурсов клиентов
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

        // Подготавливаем ресурс для ответа API
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
        // Вносим обновления в существующую запись клиента
        $customer->update($request->validated());

        // Возвращаем обновлённый ресурс клиента
        return CustomerResource::make($customer);
    }
}