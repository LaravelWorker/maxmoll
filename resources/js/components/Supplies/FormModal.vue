<template>
  <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
    <div class="modal-dialog modal-lg" style="margin-top: 100px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">📦 Оформление новой поставки</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>

        <form @submit.prevent="submitSupply">
          <div class="modal-body">
            <!-- Вывод глобальной ошибки -->
            <div v-if="errorMessage" class="alert alert-danger mb-3">
              {{ errorMessage }}
            </div>

            <!-- Выбор склада -->
            <div class="mb-3">
              <label class="form-label small fw-bold">Склад назначения</label>
              <select v-model="form.warehouse_id" required class="form-select">
                <option value="" disabled>Выберите склад</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>

            <!-- Список товаров в поставке -->
            <h6 class="mt-4 mb-2">Позиции товаров</h6>
            <div v-for="(item, index) in form.items" :key="index" class="input-group input-group-sm mb-2 align-items-center">
              <select v-model="item.product_id" required class="form-select flex-grow-1">
                <option value="" disabled>Выберите товар</option>
                <option v-for="p in products" :key="p.id" :value="p.id">
                  {{ p.name }} ({{ p.price }} ₽)
                </option>
              </select>

              <input 
                type="number" 
                min="1" 
                v-model.number="item.count" 
                required 
                class="form-control" 
                style="max-width: 100px;" 
                placeholder="Кол-во"
              />

              <button 
                type="button" 
                @click="removeItem(index)" 
                v-if="form.items.length > 1" 
                class="btn btn-outline-danger"
              >
                ✕
              </button>
            </div>

            <button 
              type="button" 
              @click="addItem" 
              class="btn btn-sm btn-outline-secondary mt-1 w-100"
            >
              + Добавить еще товар
            </button>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" @click="$emit('close')">Отмена</button>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="loading">
              {{ loading ? 'Сохранение...' : 'Оформить поставку' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close', 'saved']);

const warehouses = ref([]);
const products = ref([]);
const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  warehouse_id: '',
  items: [
    { product_id: '', count: 1 }
  ]
});

const loadDictionaries = async () => {
  try {
    const [whRes, prodRes] = await Promise.all([
      axios.get('/api/warehouses'),
      axios.get('/api/products'),
    ]);
    warehouses.value = whRes.data.data;
    products.value = prodRes.data.data;
  } catch (e) {
    errorMessage.value = 'Ошибка загрузки справочников';
  }
};

const addItem = () => {
  form.items.push({ product_id: '', count: 1 });
};

const removeItem = (index) => {
  form.items.splice(index, 1);
};

const submitSupply = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    await axios.post('/api/supplies', form);
    emit('saved');
  } catch (err) {
    const resData = err.response?.data;
    if (resData?.errors) {
      // Собираем ошибки валидации Laravel в одну строку
      const errorMessages = [];
      for (const field in resData.errors) {
        errorMessages.push(...resData.errors[field]);
      }
      errorMessage.value = errorMessages.join(' ');
    } else {
      errorMessage.value = resData?.message || 'Ошибка при создании поставки';
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => loadDictionaries());
</script>