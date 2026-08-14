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
     * Получить список клиентов с фильтрацией и пагинацией
     */
    public function index(CustomerIndexRequest $request): AnonymousResourceCollection
    {
        $query = Customer::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', "%$request->input('name')%");
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%$request->input('phone')%");
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', "%$request->input('email')%");
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $customers = $query->orderByDesc('id')->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * Создать нового клиента
     */
    public function store(StoreCustomerRequest $request): CustomerResource
    {
        $customer = Customer::create($request->validated());

        return CustomerResource::make($customer);
    }

    /**
     * Обновить данные клиента
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return CustomerResource::make($customer);
    }
}