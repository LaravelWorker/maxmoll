<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="m-0">Список поставок</h4>
      <button class="btn btn-primary btn-sm" @click="showModal = true">
        + Создать поставку
      </button>
    </div>

    <!-- Таблица списка поставок (твоя текущая реализация таблицы) -->
    <!-- ... -->

    <!-- Модальное окно создания поставки -->
    <SupplyFormModal 
      v-if="showModal" 
      @close="showModal = false" 
      @saved="onSupplySaved" 
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SupplyFormModal from './SupplyFormModal.vue';

const showModal = ref(false);

const onSupplySaved = () => {
  showModal.value = false;
  // Здесь вызов функции перезагрузки таблицы поставок (например, fetchSupplies(1))
};
</script>