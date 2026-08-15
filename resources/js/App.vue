<template>
  <div class="container py-4">
    <header class="pb-3 mb-4 border-bottom d-flex justify-content-between align-items-center">
      <h1 class="h3 fw-bold text-dark m-0">MaxMoll</h1>
      <a href="/docs/api" target="_blank" class="btn btn-outline-secondary btn-sm">
        📖 Документация API (Scramble)
      </a>
    </header>

    <!-- Навигация по вкладкам -->
    <ul class="nav nav-pills mb-4">
      <li class="nav-item">
        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'orders' }"
          @click="activeTab = 'orders'"
        >
          📦 Заказы
        </button>
      </li>
      <li class="nav-item">
        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'customers' }"
          @click="activeTab = 'customers'"
        >
          👥 Покупатели
        </button>
      </li>
      <li class="nav-item">
        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'warehouses' }"
          @click="activeTab = 'warehouses'"
        >
          🎯 Склады
        </button>
      </li>
      <li class="nav-item">
        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'movements' }"
          @click="activeTab = 'movements'"
        >
          📊 История движений товаров
        </button>
      </li>
    </ul>

    <!-- Контент активной вкладки -->
    <main>
      <OrdersList v-if="activeTab === 'orders'" />
      <StockMovements v-if="activeTab === 'movements'" />
      <WarehouseStocks v-if="activeTab === 'warehouses'" />
      <CustomersList v-if="activeTab === 'customers'" />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import OrdersList from './components/OrdersList.vue';
import CustomersList from './components/CustomersList.vue';
import StockMovements from './components/StockMovements.vue';
import WarehouseStocks from './components/WarehouseStocks.vue';

const activeTab = ref('orders');
</script>