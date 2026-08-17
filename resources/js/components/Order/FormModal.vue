<template>
  <div class="modal fade show d-block tab-index-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg" style="margin-top: 200px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ isEdit ? `Редактирование Заказа #${order.id}` : 'Создание нового заказа' }}
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        <div class="modal-body">
          <div v-if="errorMessage" class="alert alert-danger mb-3">
            {{ errorMessage }}
          </div>

          <!-- Покупатель и Склад -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Покупатель</label>
              <select v-model="form.customer_id" class="form-select" :disabled="isEdit && form.status !== 'active'">
                <option value="" disabled>Выберите покупателя</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Склад отгрузки</label>
              <select v-model="form.warehouse_id" class="form-select" :disabled="isEdit">
                <option value="" disabled>Выберите склад</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
          </div>

          <!-- Состав позиций заказа -->
          <h6 class="mt-4 mb-2">Товары в заказе</h6>
          <div v-for="(item, index) in form.items" :key="index" class="row g-2 align-items-center mb-2">
            <div class="col-md-7">
              <select v-model="item.product_id" class="form-select" :disabled="isEdit && form.status !== 'active'">
                <option value="" disabled>Выберите товар</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.price }} ₽)</option>
              </select>
            </div>
            <div class="col-md-3">
              <input 
                type="number" 
                v-model.number="item.count" 
                min="1" 
                class="form-control" 
                placeholder="Кол-во"
                :disabled="isEdit && form.status !== 'active'"
              />
            </div>
            <div class="col-md-2" v-if="!isEdit || form.status === 'active'">
              <button class="btn btn-outline-danger w-100" @click="removeItem(index)">✕</button>
            </div>
          </div>

          <button 
            v-if="!isEdit || form.status === 'active'" 
            class="btn btn-sm btn-outline-secondary mt-2" 
            @click="addItem"
          >
            + Добавить товар
          </button>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">Закрыть</button>
          <button 
            v-if="!isEdit || form.status === 'active'" 
            type="button" 
            class="btn btn-primary" 
            @click="saveOrder" 
            :disabled="saving"
          >
            Сохранить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  order: Object,
});
const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.order);
const customers = ref([]);
const warehouses = ref([]);
const products = ref([]);
const saving = ref(false);
const errorMessage = ref('');

const form = reactive({
  customer_id: props.order?.customer?.id || '',
  warehouse_id: props.order?.warehouse?.id || '',
  status: props.order?.status || 'active',
  items: props.order?.items ? props.order.items.map(i => ({ product_id: i.product_id, count: i.count })) : [{ product_id: '', count: 1 }],
});

const loadDictionaries = async () => {
  try {
    const [custRes, whRes, prodRes] = await Promise.all([
      axios.get('/api/customers?per_page=100'),
      axios.get('/api/warehouses'),
      axios.get('/api/products'),
    ]);
    customers.value = custRes.data.data;
    warehouses.value = whRes.data.data;
    products.value = prodRes.data.data;
  } catch (e) {
    errorMessage.value = 'Ошибка загрузки справочников';
  }
};

const addItem = () => form.items.push({ product_id: '', count: 1 });
const removeItem = (idx) => form.items.splice(idx, 1);

const saveOrder = async () => {
  saving.value = true;
  errorMessage.value = '';
  try {
    if (isEdit.value) {
      await axios.patch(`/api/orders/${props.order.id}`, form);
    } else {
      await axios.post('/api/orders', form);
    }
    emit('saved');
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Ошибка сохранения заказа';
  } finally {
    saving.value = false;
  }
};


onMounted(() => loadDictionaries());
</script>