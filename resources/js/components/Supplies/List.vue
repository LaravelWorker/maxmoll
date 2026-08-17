<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="m-0">История поставок</h4>
      <button 
        class="btn btn-sm btn-success"
        @click="openSupplyModal"
      >
        + Новая поставка
      </button>
    </div>

    <!-- Плавающее уведомление -->
    <div 
      v-if="notification" 
      class="position-fixed top-0 end-0 p-3" 
      style="z-index: 1080;"
    >
      <div :class="['alert shadow-lg mb-0', notification.type === 'success' ? 'alert-success' : 'alert-danger']" role="alert">
        {{ notification.text }}
      </div>
    </div>

    <!-- Блок фильтров -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Склад</label>
            <select 
              v-model="filters.warehouse_id" 
              class="form-select form-select-sm" 
              @change="fetchSupplies(1)"
            >
              <option value="">Все склады</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">
                {{ w.name }}
              </option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Дата с</label>
            <input 
              type="date" 
              v-model="filters.date_from" 
              class="form-control form-control-sm"
              @change="fetchSupplies(1)"
            />
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-bold">Дата по</label>
            <input 
              type="date" 
              v-model="filters.date_to" 
              class="form-control form-control-sm"
              @change="fetchSupplies(1)"
            />
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

          <div class="col-md-3">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">
              Сбросить
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Таблица поставок -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th># ID</th>
              <th>Дата создания</th>
              <th>Склад назначения</th>
              <th>Состав поставки</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="4" class="text-center py-4">Загрузка поставок...</td>
            </tr>
            <tr v-else-if="supplies.length === 0">
              <td colspan="4" class="text-center py-4 text-muted">Поставки не найдены</td>
            </tr>
            <tr v-for="supply in supplies" :key="supply.id">
              <td><strong>#{{ supply.id }}</strong></td>
              <td>{{ supply.created_at || '—' }}</td>
              <td><strong>{{ supply.warehouse?.name || '—' }}</strong></td>
              <td>
                <div v-if="supply.items && supply.items.length" class="py-1">
                  <div 
                    v-for="item in supply.items" 
                    :key="item.id || item.product_id" 
                    class="small text-nowrap"
                  >
                    {{ item.product_name }} 
                    <span class="fw-bold text-success">(+{{ item.count || item.quantity }} шт.)</span>
                  </div>
                </div>
                <span v-else class="text-muted small">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Компонент пагинации -->
    <Pagination :pagination="pagination" @change="fetchSupplies" />

    <!-- Модальное окно создания поставки -->
    <div v-if="isSupplyModalOpen" class="modal show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog" style="margin-top: 100px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Оформление новой поставки</h5>
            <button type="button" class="btn-close" @click="isSupplyModalOpen = false"></button>
          </div>
          
          <form @submit.prevent="submitSupply">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Склад назначения</label>
                <select 
                  v-model="supplyForm.warehouse_id" 
                  required
                  class="form-select form-select-sm"
                >
                  <option value="">Выберите склад</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Позиции товаров</label>
                <div v-for="(item, index) in supplyForm.items" :key="index" class="input-group input-group-sm mb-2">
                  <select 
                    v-model="item.product_id"
                    required
                    class="form-select"
                  >
                    <option value="">Выберите товар</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                  <input 
                    type="number" 
                    min="1"
                    v-model.number="item.count"
                    required
                    class="form-control"
                    style="max-width: 90px;"
                    placeholder="Кол-во"
                  />
                  <button 
                    type="button" 
                    @click="removeSupplyItem(index)"
                    v-if="supplyForm.items.length > 1"
                    class="btn btn-outline-danger"
                  >
                    ✕
                  </button>
                </div>
                <button 
                  type="button" 
                  @click="addSupplyItem"
                  class="btn btn-sm btn-outline-secondary mt-1 w-100"
                >
                  + Добавить еще товар
                </button>
              </div>
            </div>

            <div class="modal-footer">
              <button 
                type="button" 
                @click="isSupplyModalOpen = false"
                class="btn btn-sm btn-secondary"
              >
                Отмена
              </button>
              <button 
                type="submit" 
                :disabled="submitting"
                class="btn btn-sm btn-success"
              >
                {{ submitting ? 'Сохранение...' : 'Создать поставку' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import Pagination from '../Common/Pagination.vue';
import { usePagination } from '../Composables/usePagination';

const { perPage, pagination, setMeta } = usePagination(15);

const supplies = ref([]);
const warehouses = ref([]);
const products = ref([]);
const loading = ref(false);
const submitting = ref(false);

const filters = reactive({
  warehouse_id: '',
  date_from: '',
  date_to: '',
});

const isSupplyModalOpen = ref(false);
const notification = ref(null);

const supplyForm = reactive({
  warehouse_id: '',
  items: [{ product_id: '', count: 1 }],
});

const fetchSupplies = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters,
    };
    const res = await axios.get('/api/supplies', { params });
    supplies.value = res.data.data || [];
    setMeta(res.data.meta);
  } catch (err) {
    showNotification('Ошибка загрузки списка поставок', 'danger');
  } finally {
    loading.value = false;
  }
};

const fetchWarehouses = async () => {
  try {
    const res = await axios.get('/api/warehouses');
    warehouses.value = res.data.data || res.data;
  } catch (e) {
    showNotification('Ошибка загрузки складов', 'danger');
  }
};

const fetchProducts = async () => {
  try {
    const res = await axios.get('/api/products');
    products.value = res.data.data || res.data;
  } catch (e) {
    showNotification('Ошибка загрузки товаров', 'danger');
  }
};

const changePerPage = () => {
  fetchSupplies(1);
};

const resetFilters = () => {
  filters.warehouse_id = '';
  filters.date_from = '';
  filters.date_to = '';
  perPage.value = 15;
  fetchSupplies(1);
};

const openSupplyModal = () => {
  supplyForm.warehouse_id = '';
  supplyForm.items = [{ product_id: '', count: 1 }];
  isSupplyModalOpen.value = true;
};

const addSupplyItem = () => {
  supplyForm.items.push({ product_id: '', count: 1 });
};

const removeSupplyItem = (index) => {
  supplyForm.items.splice(index, 1);
};

const submitSupply = async () => {
  submitting.value = true;
  notification.value = null;

  try {
    const res = await axios.post('/api/supplies', supplyForm);
    const msg = res.data?.message || 'Поставка успешно создана и проведена!';
    showNotification(msg, 'success');
    isSupplyModalOpen.value = false;
    fetchSupplies(1);
  } catch (err) {
    const resData = err.response?.data;
    if (resData?.errors) {
      const msgs = Object.values(resData.errors).flat().join(' ');
      showNotification(msgs, 'danger');
    } else {
      showNotification(resData?.message || 'Ошибка создания поставки', 'danger');
    }
  } finally {
    submitting.value = false;
  }
};

const showNotification = (text, type) => {
  notification.value = { text, type };
  setTimeout(() => {
    if (notification.value?.text === text) {
      notification.value = null;
    }
  }, 4000);
};

onMounted(() => {
  fetchWarehouses();
  fetchProducts();
  fetchSupplies(1);
});
</script>