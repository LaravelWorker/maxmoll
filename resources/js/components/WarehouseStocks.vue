<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="m-0">Остатки товаров на складах</h4>
      <div class="d-flex gap-2">
        <button 
          class="btn btn-sm btn-success"
          @click="openSupplyModal"
        >
          + Новая поставка
        </button>
        <button 
          class="btn btn-sm btn-primary"
          @click="openTransferModal"
        >
          Межскладское перемещение
        </button>
      </div>
    </div>

    <!-- Глобальное плавающее уведомление поверх всех окон (в правом верхнем углу) -->
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
              @change="fetchStocks(1)"
            >
              <option value="">Все склады</option>
              <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                {{ warehouse.name }}
              </option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label small fw-bold">Поиск товара</label>
            <input 
              type="text" 
              v-model="filters.search" 
              @input="debouncedFetchStocks"
              placeholder="Введите название товара..." 
              class="form-control form-control-sm"
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
          <div class="col-md-2">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="resetFilters">
              Сбросить
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Таблица остатков -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Склад</th>
              <th>Товар</th>
              <th>Цена</th>
              <th>Остаток</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="4" class="text-center py-4">Загрузка остатков...</td>
            </tr>
            <tr v-else-if="stocks.length === 0">
              <td colspan="4" class="text-center py-4 text-muted">Остатки не найдены</td>
            </tr>
            <tr v-for="stock in stocks" :key="`${stock.warehouse_id}-${stock.product_id}`">
              <td><strong>{{ stock.warehouse?.name || '—' }}</strong></td>
              <td>{{ stock.product?.name || '—' }}</td>
              <td>{{ stock.product?.price }} ₽</td>
              <td>
                <span class="fw-bold text-primary">{{ stock.stock }} шт.</span>
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
        <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
          <button class="page-link" @click="changePage(pagination.currentPage - 1)">Назад</button>
        </li>
        <li class="page-item disabled">
          <span class="page-link">Стр. {{ pagination.currentPage }} из {{ pagination.lastPage }}</span>
        </li>
        <li class="page-item" :class="{ disabled: pagination.currentPage === pagination.lastPage }">
          <button class="page-link" @click="changePage(pagination.currentPage + 1)">Вперед</button>
        </li>
      </ul>
    </div>

    <!-- Модальное окно перемещения -->
    <div v-if="isModalOpen" class="modal show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog" style="margin-top: 100px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Новое межскладское перемещение</h5>
            <button type="button" class="btn-close" @click="isModalOpen = false"></button>
          </div>
          
          <form @submit.prevent="submitTransfer">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label small fw-bold">Склад-отправитель</label>
                <select 
                  v-model="transferForm.from_warehouse_id" 
                  required
                  class="form-select form-select-sm"
                >
                  <option value="">Выберите склад отправки</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Склад-получатель</label>
                <select 
                  v-model="transferForm.to_warehouse_id" 
                  required
                  class="form-select form-select-sm"
                >
                  <option value="">Выберите склад назначения</option>
                  <template v-for="w in warehouses" :key="w.id">
                    <option v-if="w.id !== Number(transferForm.from_warehouse_id)" :value="w.id">
                      {{ w.name }}
                    </option>
                  </template>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Позиции товаров</label>
                <div v-for="(item, index) in transferForm.items" :key="index" class="input-group input-group-sm mb-2">
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
                    @click="removeTransferItem(index)"
                    v-if="transferForm.items.length > 1"
                    class="btn btn-outline-danger"
                  >
                    ✕
                  </button>
                </div>
                <button 
                  type="button" 
                  @click="addTransferItem"
                  class="btn btn-sm btn-outline-secondary mt-1 w-100"
                >
                  + Добавить еще товар
                </button>
              </div>
            </div>

            <div class="modal-footer">
              <button 
                type="button" 
                @click="isModalOpen = false"
                class="btn btn-sm btn-secondary"
              >
                Отмена
              </button>
              <button 
                type="submit" 
                :disabled="loading"
                class="btn btn-sm btn-primary"
              >
                {{ loading ? 'Проведение...' : 'Провести перемещение' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

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
                :disabled="loading"
                class="btn btn-sm btn-success"
              >
                {{ loading ? 'Сохранение...' : 'Создать поставку' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'

const stocks = ref([])
const warehouses = ref([])
const products = ref([])
const loading = ref(false)
const perPage = ref(15)

const filters = reactive({
  warehouse_id: '',
  search: '',
  page: 1
})

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  from: 0,
  to: 0
})

const isModalOpen = ref(false)
const isSupplyModalOpen = ref(false)
const notification = ref(null)

const transferForm = reactive({
  from_warehouse_id: '',
  to_warehouse_id: '',
  items: [{ product_id: '', count: 1 }]
})

const supplyForm = reactive({
  warehouse_id: '',
  items: [{ product_id: '', count: 1 }]
})

onMounted(async () => {
  await fetchWarehouses()
  await fetchProducts()
  await fetchStocks(1)
})

const fetchWarehouses = async () => {
  try {
    const res = await fetch('/api/warehouses')
    const json = await res.json()
    warehouses.value = json.data || json
  } catch (e) {
    showNotification('Ошибка загрузки складов', 'danger')
  }
}

const fetchProducts = async () => {
  try {
    const res = await fetch('/api/warehouses/products')
    const json = await res.json()
    products.value = json.data || json
  } catch (e) {
    showNotification('Ошибка загрузки товаров', 'danger')
  }
}

const fetchStocks = async (page = 1) => {
  loading.value = true
  try {
    filters.page = page
    let url = `/api/warehouses/stocks?page=${page}&per_page=${perPage.value}`
    if (filters.warehouse_id) url += `&warehouse_id=${filters.warehouse_id}`
    if (filters.search) url += `&search=${filters.search}`

    const res = await fetch(url)
    const json = await res.json()
    
    stocks.value = json.data || []
    pagination.value = {
      currentPage: json.current_page || json.meta?.current_page || 1,
      lastPage: json.last_page || json.meta?.last_page || 1,
      total: json.total || json.meta?.total || 0,
      from: json.from || json.meta?.from || 0,
      to: json.to || json.meta?.to || 0
    }
  } catch (e) {
    showNotification('Ошибка загрузки остатков', 'danger')
  } finally {
    loading.value = false
  }
}

const changePerPage = () => {
  fetchStocks(1)
}

let searchTimeout = null
const debouncedFetchStocks = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchStocks(1)
  }, 300)
}

const resetFilters = () => {
  filters.warehouse_id = ''
  filters.search = ''
  perPage.value = 15
  fetchStocks(1)
}

const changePage = (page) => {
  fetchStocks(page)
}

const openTransferModal = () => {
  transferForm.from_warehouse_id = ''
  transferForm.to_warehouse_id = ''
  transferForm.items = [{ product_id: '', count: 1 }]
  isModalOpen.value = true
}

const addTransferItem = () => {
  transferForm.items.push({ product_id: '', count: 1 })
}

const removeTransferItem = (index) => {
  transferForm.items.splice(index, 1)
}

const submitTransfer = async () => {
  loading.value = true
  notification.value = null

  try {
    const res = await fetch('/api/warehouses/transfers', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(transferForm)
    })

    const data = await res.json()

    if (!res.ok) throw new Error(data.message || 'Ошибка проведения перемещения')

    showNotification('Перемещение успешно создано и проведено!', 'success')
    isModalOpen.value = false
    fetchStocks(1)
  } catch (err) {
    showNotification(err.message, 'danger')
  } finally {
    loading.value = false
  }
}

const openSupplyModal = () => {
  supplyForm.warehouse_id = ''
  supplyForm.items = [{ product_id: '', count: 1 }]
  isSupplyModalOpen.value = true
}

const addSupplyItem = () => {
  supplyForm.items.push({ product_id: '', count: 1 })
}

const removeSupplyItem = (index) => {
  supplyForm.items.splice(index, 1)
}

const submitSupply = async () => {
  loading.value = true
  notification.value = null

  try {
    const res = await fetch('/api/supplies', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(supplyForm)
    })

    const data = await res.json()

    if (!res.ok) {
      if (data.errors) {
        const errorMsgs = []
        for (const field in data.errors) {
          errorMsgs.push(...data.errors[field])
        }
        throw new Error(errorMsgs.join(' '))
      }
      throw new Error(data.message || 'Ошибка создания поставки')
    }

    showNotification('Поставка успешно создана и проведена!', 'success')
    isSupplyModalOpen.value = false
    fetchStocks(1)
  } catch (err) {
    showNotification(err.message, 'danger')
  } finally {
    loading.value = false
  }
}

const showNotification = (text, type) => {
  notification.value = { text, type }
  setTimeout(() => {
    if (notification.value?.text === text) {
      notification.value = null
    }
  }, 4000)
}
</script>