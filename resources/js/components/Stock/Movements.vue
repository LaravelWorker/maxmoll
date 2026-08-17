<template>
  <div>
    <h4 class="mb-3">История движения товаров</h4>

    <!-- Блок фильтров -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Склад</label>
            <select v-model="filters.warehouse_id" class="form-select form-select-sm" @change="fetchMovements(1)">
              <option value="">Все склады</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-bold">Поиск товара</label>
            <input 
              type="text" 
              v-model="filters.search" 
              class="form-control form-control-sm" 
              placeholder="Введите название товара..." 
              @input="debouncedFetchMovements"
            />
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Тип источника</label>
            <select v-model="filters.doc_type" class="form-select form-select-sm" @change="fetchMovements(1)">
              <option value="">Все типы</option>
              <option value="order">Заказ</option>
              <option value="supply">Поставка</option>
              <option value="transfer">Перемещение</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Показывать по</label>
            <select v-model="perPage" class="form-select form-select-sm" @change="changePerPage">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">
              Сбросить
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Таблица движений -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th># ID</th>
              <th>Дата</th>
              <th>Склад</th>
              <th>Товар</th>
              <th>Количество (изменение)</th>
              <th>Источник</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center py-4">Загрузка истории...</td>
            </tr>
            <tr v-else-if="movements.length === 0">
              <td colspan="6" class="text-center py-4 text-muted">Записи не найдены</td>
            </tr>
            <tr v-for="m in movements" :key="m.id">
              <td><strong>#{{ m.id }}</strong></td>
              <td>{{ m.created_at }}</td>
              <td>{{ m.warehouse || '—' }}</td>
              <td>{{ m.product_name || '—' }}</td>
              <td>
                <span class="fw-bold" :class="m.quantity > 0 ? 'text-success' : 'text-danger'">
                  {{ m.quantity > 0 ? `+${m.quantity}` : m.quantity }}
                </span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  {{ getDocumentLabel(m.doc_type) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Компонент пагинации -->
    <Pagination :pagination="pagination" @change="fetchMovements" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import Pagination from '../Common/Pagination.vue';

const movements = ref([]);
const warehouses = ref([]);
const loading = ref(false);
const perPage = ref(15);

const filters = reactive({
  warehouse_id: '',
  search: '',
  doc_type: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

const getDocumentLabel = (docType) => {
  const typeName = docType ? docType.split('\\').pop() : '';

  switch (typeName.toLowerCase()) {
    case 'order':
      return 'Заказ';
    case 'supply':
      return 'Поставка';
    case 'transfer':
      return 'Перемещение';
    default:
      return typeName || 'Документ';
  }
};

const loadWarehouses = async () => {
  try {
    const res = await axios.get('/api/warehouses');
    warehouses.value = res.data.data;
  } catch (e) {
    console.error('Ошибка загрузки складов');
  }
};

const fetchMovements = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters,
    };
    const res = await axios.get('/api/stock-movements', { params });
    movements.value = res.data.data;
    pagination.value = res.data.meta;
  } catch (err) {
    alert('Ошибка загрузки истории движений');
  } finally {
    loading.value = false;
  }
};

const changePerPage = () => {
  fetchMovements(1);
};

let searchTimeout = null;
const debouncedFetchMovements = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchMovements(1);
  }, 300);
};

const resetFilters = () => {
  filters.warehouse_id = '';
  filters.search = '';
  filters.doc_type = '';
  perPage.value = 15;
  fetchMovements(1);
};

onMounted(() => {
  loadWarehouses();
  fetchMovements(1);
});
</script>