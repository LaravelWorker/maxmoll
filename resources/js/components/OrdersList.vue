<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="m-0">Список заказов</h4>
      <button class="btn btn-primary" @click="openCreateModal">
        + Создать новый заказ
      </button>
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
              <th>Позиций</th>
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
              <td>{{ order.items?.length || 0 }}</td>
              <td>
                <span :class="getStatusBadgeClass(order.status)" class="badge">
                  {{ order.status }}
                </span>
              </td>
              <td>{{ order.created_at }}</td>
              <td class="text-end">
                <button v-if="order.status === 'active'" class="btn btn-sm btn-outline-primary me-1" @click="openEditModal(order)">
                  ✏️ Редактировать
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Настраиваемая Пагинация -->
    <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
      <div class="text-muted small">
        Показано {{ pagination.from }}–{{ pagination.to }} из {{ pagination.total }} заказов
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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import OrderFormModal from './OrderFormModal.vue';

const orders = ref([]);
const loading = ref(false);
const showModal = ref(false);
const selectedOrder = ref(null);

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  from: 0,
  to: 0,
  prev: null,
  next: null,
});

const fetchOrders = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get(`/api/orders?page=${page}&per_page=10`);
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