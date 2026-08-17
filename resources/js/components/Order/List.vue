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
              <option v-for="(label, value) in statusLabels" :key="value" :value="value">
                {{ label }}
              </option>
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
                <div v-if="order.items && order.items.length" class="py-1">
                  <div 
                    v-for="item in order.items" 
                    :key="item.id || item.product_id" 
                    class="small text-nowrap"
                  >
                    • {{ item.product_name }} 
                    <span class="fw-bold text-secondary">({{ item.count }} шт.)</span>
                  </div>
                </div>
                <span v-else class="text-muted small">—</span>
              </td>
              <td>
                <OrderStatusBadge :status="order.status" />
              </td>
              <td>{{ order.created_at }}</td>
              <td class="text-end">
                <!-- Единая панель кнопок действий -->
                <div class="btn-group btn-group-sm" role="group">
                  <!-- Действия для АКТИВНОГО заказа -->
                  <template v-if="order.status === 'active'">
                    <button 
                      class="btn btn-outline-primary" 
                      title="Редактировать"
                      @click="openEditModal(order)"
                    >
                      ✏️
                    </button>
                    <button 
                      class="btn btn-outline-success" 
                      title="Завершить и списать со склада"
                      @click="completeOrder(order)"
                    >
                      ✅ Завершить
                    </button>
                    <button 
                      class="btn btn-outline-warning" 
                      title="Отменить заказ"
                      @click="cancelOrder(order)"
                    >
                      🚫 Отменить
                    </button>
                    <button 
                      class="btn btn-outline-danger" 
                      title="Удалить заказ"
                      @click="deleteOrder(order)"
                    >
                      🗑️
                    </button>
                  </template>

                  <!-- Действия для ОТМЕНЕННОГО заказа -->
                  <template v-else-if="order.status === 'canceled'">
                    <button 
                      class="btn btn-outline-success" 
                      title="Возобновить заказ"
                      @click="restoreOrder(order)"
                    >
                      🔄 Возобновить
                    </button>
                  </template>

                  <!-- Действия для ЗАВЕРШЕННОГО заказа -->
                  <template v-else-if="order.status === 'completed'">
                    <button 
                      class="btn btn-outline-secondary" 
                      title="Просмотр деталей"
                      @click="openEditModal(order)"
                    >
                      👁️ Просмотр
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Компонент пагинации -->
    <Pagination :pagination="pagination" @change="fetchOrders" />

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
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';
import OrderFormModal from './FormModal.vue';
import Pagination from '../Common/Pagination.vue';
import OrderStatusBadge from '../Common/OrderStatusBadge.vue';
import { useOrderStatus } from '../Composables/useOrderStatus';

const { statusLabels } = useOrderStatus();

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
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

 const fetchOrders = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters,
    };

    const response = await axios.get('/api/orders', { params });
    orders.value = response.data.data;
    pagination.value = response.data.meta;
  } catch (err) {
    console.error('Ошибка при загрузке заказов:', err);
    alert('Ошибка при загрузке заказов');
  } finally {
    loading.value = false;
  }
};

// Автоматический watcher для всех изменений в фильтрах и perPage
let searchTimeout = null;
watch(
  [filters, perPage],
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      fetchOrders(1);
    }, 300);
  },
  { deep: true }
);

const resetFilters = () => {
  filters.customer_search = '';
  filters.warehouse_search = '';
  filters.status = '';
  perPage.value = 15;
  fetchOrders(1);
};

const openCreateModal = () => {
  selectedOrder.value = null;
  showModal.value = true;
};

const openEditModal = (order) => {
  selectedOrder.value = order;
  showModal.value = true;
};

const completeOrder = async (order) => {
  if (!confirm(`Завершить заказ #${order.id}? Товары будут списаны со склада.`)) return;

  try {
    await axios.post(`/api/orders/${order.id}/complete`);
    fetchOrders(pagination.value.current_page);
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка при завершении заказа');
  }
};

const cancelOrder = async (order) => {
  if (!confirm(`Вы действительно хотите отменить заказ #${order.id}?`)) return;

  try {
    await axios.post(`/api/orders/${order.id}/cancel`);
    fetchOrders(pagination.value.current_page);
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка отмены заказа');
  }
};

const restoreOrder = async (order) => {
  if (!confirm(`Вы действительно хотите возобновить заказ #${order.id}?`)) return;

  try {
    await axios.post(`/api/orders/${order.id}/restore`);
    fetchOrders(pagination.value.current_page);
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка возобновления заказа');
  }
};

const deleteOrder = async (order) => {
  if (!confirm(`Вы действительно хотите удалить заказ #${order.id}?`)) return;

  try {
    await axios.delete(`/api/orders/${order.id}`);
    
    const pageToFetch = (orders.value.length === 1 && pagination.value.current_page > 1)
      ? pagination.value.current_page - 1
      : pagination.value.current_page;

    fetchOrders(pageToFetch);
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления заказа');
  }
};

const onOrderSaved = () => {
  showModal.value = false;
  fetchOrders(pagination.value.current_page);
};

onMounted(() => fetchOrders(1));
</script>