<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="m-0">Список заказов</h4>
      <button class="btn btn-primary btn-sm" @click="openCreateModal">
        + Создать новый заказ
      </button>
    </div>

    <!-- Блок фильтров и поиска -->
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Покупатель</label>
            <input 
              type="text" 
              v-model="filters.customer_search" 
              class="form-control form-control-sm" 
              placeholder="Поиск по покупателю..." 
              @input="debouncedFetchOrders"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-bold">Склад</label>
            <input 
              type="text" 
              v-model="filters.warehouse_search" 
              class="form-control form-control-sm" 
              placeholder="Поиск по складу..." 
              @input="debouncedFetchOrders"
            />
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Статус</label>
            <select v-model="filters.status" class="form-select form-select-sm" @change="fetchOrders(1)">
              <option value="">Все статусы</option>
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="canceled">Canceled</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Показывать по</label>
            <select 
              v-model="perPage" 
              class="form-select form-select-sm" 
              @change="changePerPage"
            >
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

    <!-- Таблица заказов -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th># ID</th>
              <th>Покупатель</th>
              <th>Склад</th>
              <th>Состав заказа</th>
              <th>Статус</th>
              <th>Дата создания</th>
              <th class="text-end">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-4">Загрузка заказов...</td>
            </tr>
            <tr v-else-if="orders.length === 0">
              <td colspan="7" class="text-center py-4 text-muted">Заказы не найдены</td>
            </tr>
            <tr v-for="order in orders" :key="order.id">
              <td><strong>#{{ order.id }}</strong></td>
              <td>{{ order.customer?.name || '—' }}</td>
              <td>{{ order.warehouse?.name || '—' }}</td>
              <td>
                <!-- Полный состав заказа -->
                <div v-if="order.items && order.items.length" class="py-1">
                  <div 
                    v-for="item in order.items" 
                    :key="item.id || item.product_id" 
                    class="small text-nowrap"
                  >
                    • {{ `${item.product_name}` }} 
                    <span class="fw-bold text-secondary">({{ item.count }} шт.)</span>
                  </div>
                </div>
                <span v-else class="text-muted small">—</span>
              </td>
              <td>
                <span :class="getStatusBadgeClass(order.status)" class="badge">
                  {{ order.status }}
                </span>
              </td>
              <td>{{ order.created_at }}</td>
              <td class="text-end">
                <button v-if="order.status == 'active'" class="btn btn-sm btn-outline-primary me-1" @click="openEditModal(order)">
                  ✏️ Редактировать
                </button>

                <button v-if="order.status == 'canceled'" class="btn btn-sm btn-outline-success me-1" @click="restoreOrder(order)">
                  🔄 Возобновить
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Пагинация -->
    <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
      <div class="text-muted small">
      </div>
      <ul class="pagination pagination-sm m-0">
        <li class="page-item" :class="{ disabled: !pagination.prev }">
          <button class="page-link" @click="fetchOrders(pagination.currentPage - 1)">Назад</button>
        </li>
        <li class="page-item disabled">
          <span class="page-link">Стр. {{ pagination.currentPage }} из {{ pagination.lastPage }}</span>
        </li>
        <li class="page-item" :class="{ disabled: !pagination.next }">
          <button class="page-link" @click="fetchOrders(pagination.currentPage + 1)">Вперед</button>
        </li>
      </ul>
    </div>

    <!-- Модальное окно создания/редактирования -->
    <OrderFormModal 
      v-if="showModal" 
      :order="selectedOrder" 
      @close="showModal = false" 
      @saved="onOrderSaved" 
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import OrderFormModal from './OrderFormModal.vue';

const orders = ref([]);
const loading = ref(false);
const showModal = ref(false);
const selectedOrder = ref(null);
const perPage = ref(15);

const filters = reactive({
  customer_search: '',
  warehouse_search: '',
  status: '',
});

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  from: 0,
  to: 0,
  prev: null,
  next: null,
});

const restoreOrder = async (order) => {
  if (!confirm(`Вы действительно хотите возобновить заказ #${order.id}?`)) return;
  
  try {
    await axios.post(`/api/orders/${order.id}/restore`);
    fetchOrders(pagination.value.currentPage);
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка возобновления заказа');
  }
};

const fetchOrders = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters,
    };
    const res = await axios.get('/api/orders', { params });
    orders.value = res.data.data;
    pagination.value = {
      currentPage: res.data.meta.current_page,
      lastPage: res.data.meta.last_page,
      total: res.data.meta.total,
      from: res.data.meta.from,
      to: res.data.meta.to,
      prev: res.data.links.prev,
      next: res.data.links.next,
    };
  } catch (err) {
    console.error('Ошибка при загрузке заказов:', err);
    alert('Ошибка при загрузке заказов');
  } finally {
    loading.value = false;
  }
};

let searchTimeout = null;
const debouncedFetchOrders = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchOrders(1);
  }, 300);
};

const changePerPage = () => {
  fetchOrders(1);
};

const resetFilters = () => {
  filters.customer_search = '';
  filters.warehouse_search = '';
  filters.status = '';
  perPage.value = 15;
  fetchOrders(1);
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'active': return 'bg-warning text-dark';
    case 'completed': return 'bg-success';
    case 'canceled': return 'bg-danger';
    default: return 'bg-secondary';
  }
};

const openCreateModal = () => {
  selectedOrder.value = null;
  showModal.value = true;
};

const openEditModal = (order) => {
  selectedOrder.value = order;
  showModal.value = true;
};

const onOrderSaved = () => {
  showModal.value = false;
  fetchOrders(pagination.value.currentPage);
};

onMounted(() => fetchOrders());
</script>