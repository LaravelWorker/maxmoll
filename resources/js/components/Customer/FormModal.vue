<template>
  <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
    <div class="modal-dialog" style="margin-top: 150px;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ isEdit ? `Редактирование покупателя #${customer.id}` : 'Новый покупатель' }}
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>

        <form @submit.prevent="saveCustomer">
          <div class="modal-body">
            <div v-if="errorMessage" class="alert alert-danger mb-3">
              {{ errorMessage }}
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">ФИО / Имя <span class="text-danger">*</span></label>
              <input 
                type="text" 
                v-model="form.name" 
                required 
                class="form-control" 
                placeholder="Иван Иванов"
              />
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Email</label>
              <input 
                type="email" 
                v-model="form.email" 
                class="form-control" 
                placeholder="example@mail.com"
              />
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Телефон</label>
              <input 
                type="number" 
                v-model="form.phone" 
                class="form-control" 
                placeholder="+7 (999) 000-00-00"
              />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary" @click="$emit('close')">Отмена</button>
            <button type="submit" class="btn btn-sm btn-primary" :disabled="saving">
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  customer: Object,
});
const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.customer);
const saving = ref(false);
const errorMessage = ref('');

const form = reactive({
  name: props.customer?.name || '',
  email: props.customer?.email || '',
  phone: props.customer?.phone || '',
});

const handleApiError = (err) => {
  const resData = err.response?.data;
  if (resData?.errors) {
    const errorMessages = [];
    for (const field in resData.errors) {
      errorMessages.push(...resData.errors[field]);
    }
    errorMessage.value = errorMessages.join(' ');
  } else {
    errorMessage.value = resData?.message || 'Ошибка при сохранении данных покупателя';
  }
};

const saveCustomer = async () => {
  saving.value = true;
  errorMessage.value = '';

  try {
    if (isEdit.value) {
      await axios.patch(`/api/customers/${props.customer.id}`, form);
    } else {
      await axios.post('/api/customers', form);
    }
    emit('saved');
  } catch (err) {
    handleApiError(err);
  } finally {
    saving.value = false;
  }
};
</script>