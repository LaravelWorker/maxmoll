<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="m-0">Список покупателей</h4>
      <button class="btn btn-primary btn-sm" @click="openCreateModal">
        ➕ Добавить клиента
      </button>
    </div>

    <!-- Блок фильтрации и поиска -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Имя / ФИО</label>
            <input 
              type="text" 
              v-model="filters.name" 
              @input="debouncedFetchCustomers"
              placeholder="Поиск по имени..." 
              class="form-control form-control-sm"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-bold">Email</label>
            <input 
              type="text" 
              v-model="filters.email" 
              @input="debouncedFetchCustomers"
              placeholder="Поиск по email..." 
              class="form-control form-control-sm"
            />
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Телефон</label>
            <input 
              type="text" 
              v-model="filters.phone" 
              @input="debouncedFetchCustomers"
              placeholder="Поиск по телефону..." 
              class="form-control form-control-sm"
            />
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Показывать по</label>
            <select 
              v-model="perPage" 
              class="form-select form-select-sm" 
              @change="changePerPage"
            >
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <div class="col-md-2">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">
              Сбросить
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Таблица покупателей -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th># ID</th>
              <th>ФИО / Имя</th>
              <th>Email</th>
              <th>Телефон</th>
              <th>Дата создания</th>
              <th class="text-end">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center py-4">Загрузка покупателей...</td>
            </tr>
            <tr v-else-if="customers.length === 0">
              <td colspan="6" class="text-center py-4 text-muted">Клиенты не найдены</td>
            </tr>
            <tr v-for="c in customers" :key="c.id">
              <td><strong>#{{ c.id }}</strong></td>
              <td><strong>{{ c.name }}</strong></td>
              <td>{{ c.email || '—' }}</td>
              <td>{{ c.phone || '—' }}</td>
              <td>{{ c.created_at || '—' }}</td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" @click="openEditModal(c)">
                  ✏️ Редактировать
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Компонент пагинации -->
    <Pagination :pagination="pagination" @change="fetchCustomers" />

    <!-- Модальное окно создания/редактирования -->
    <CustomerFormModal 
      v-if="showModal" 
      :customer="selectedCustomer" 
      @close="showModal = false" 
      @saved="onCustomerSaved" 
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import CustomerFormModal from './FormModal.vue';
import Pagination from '../Common/Pagination.vue';

const customers = ref([]);
const loading = ref(false);
const showModal = ref(false);
const selectedCustomer = ref(null);
const perPage = ref(15);

const filters = reactive({
  name: '',
  email: '',
  phone: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

const fetchCustomers = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters,
    };

    const res = await axios.get('/api/customers', { params });
    customers.value = res.data.data;
    pagination.value = res.data.meta;
  } catch (err) {
    console.error('Ошибка при загрузке покупателей:', err);
    alert('Ошибка при загрузке покупателей');
  } finally {
    loading.value = false;
  }
};

let searchTimeout = null;
const debouncedFetchCustomers = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchCustomers(1);
  }, 300);
};

const changePerPage = () => {
  fetchCustomers(1);
};

const resetFilters = () => {
  filters.name = '';
  filters.email = '';
  filters.phone = '';
  perPage.value = 15;
  fetchCustomers(1);
};

const openCreateModal = () => {
  selectedCustomer.value = null;
  showModal.value = true;
};

const openEditModal = (customer) => {
  selectedCustomer.value = customer;
  showModal.value = true;
};

const onCustomerSaved = () => {
  showModal.value = false;
  fetchCustomers(pagination.value.current_page);
};

onMounted(() => fetchCustomers(1));
</script>