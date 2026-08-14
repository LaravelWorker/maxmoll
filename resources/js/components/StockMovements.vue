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
            <label class="form-label small fw-bold">Товар</label>
            <select v-model="filters.product_id" class="form-select form-select-sm" @change="fetchMovements(1)">
              <option value="">Все товары</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Тип документа</label>
            <select v-model="filters.doc_type" class="form-select form-select-sm" @change="fetchMovements(1)">
              <option value="">Все типы</option>
              <option value="order">Order (Заказ)</option>
              <option value="supply">Supply (Поставка)</option>
              <option value="transfer">Transfer (Перемещение)</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">
              Сбросить фильтры
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
              <th>Документ-источник</th>
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
                  {{ m.doc_type }} #{{ m.doc_id }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Пагинация -->
    <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
      <div class="text-muted small">
        Записей: {{ pagination.total }}
      </div>
      <ul class="pagination pagination-sm m-0">
        <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
          <button class="page-link" @click="fetchMovements(pagination.currentPage - 1)">Назад</button>
        </li>
        <li class="page-item disabled">
          <span class="page-link">Стр. {{ pagination.currentPage }} из {{ pagination.lastPage }}</span>
        </li>
        <li class="page-item" :class="{ disabled: pagination.currentPage === pagination.lastPage }">
          <button class="page-link" @click="fetchMovements(pagination.currentPage + 1)">Вперед</button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const movements = ref([]);
const warehouses = ref([]);
const products = ref([]);
const loading = ref(false);

const filters = reactive({
  warehouse_id: '',
  product_id: '',
  doc_type: '',
});

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
});

const loadDictionaries = async () => {
  const [whRes, prodRes] = await Promise.all([
    axios.get('/api/warehouses'),
    axios.get('/api/products'),
  ]);
  warehouses.value = whRes.data.data;
  products.value = prodRes.data.data;
};

const fetchMovements = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: 15,
      ...filters,
    };
    const res = await axios.get('/api/stock-movements', { params });
    movements.value = res.data.data;
    pagination.value = {
      currentPage: res.data.meta.current_page,
      lastPage: res.data.meta.last_page,
      total: res.data.meta.total,
    };
  } catch (err) {
    alert('Ошибка загрузки истории движений');
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.warehouse_id = '';
  filters.product_id = '';
  filters.doc_type = '';
  fetchMovements(1);
};

onMounted(() => {
  loadDictionaries();
  fetchMovements(1);
});
</script>