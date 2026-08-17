<script setup>
const props = defineProps({
  pagination: {
    type: Object,
    required: true,
    default: () => ({
      current_page: 1,
      last_page: 1,
      total: 0,
      from: 0,
      to: 0
    })
  }
})

const emit = defineEmits(['change'])

const changePage = (page) => {
  if (
    page >= 1 && 
    page <= props.pagination.last_page && 
    page !== props.pagination.current_page
  ) {
    emit('change', page)
  }
}
</script>

<template>
  <div 
    v-if="pagination && pagination.last_page > 1" 
    class="d-flex justify-content-between align-items-center mt-3"
  >
    <div class="text-muted small">
      Показано {{ pagination.from || 0 }}–{{ pagination.to || 0 }} из {{ pagination.total || 0 }}
    </div>

    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li 
          class="page-item" 
          :class="{ disabled: pagination.current_page === 1 }"
        >
          <button 
            class="page-link" 
            type="button" 
            @click="changePage(pagination.current_page - 1)"
          >
            &laquo; Назад
          </button>
        </li>

        <li
          v-for="page in pagination.last_page"
          :key="page"
          class="page-item"
          :class="{ active: page === pagination.current_page }"
        >
          <button 
            class="page-link" 
            type="button" 
            @click="changePage(page)"
          >
            {{ page }}
          </button>
        </li>

        <li 
          class="page-item" 
          :class="{ disabled: pagination.current_page === pagination.last_page }"
        >
          <button 
            class="page-link" 
            type="button" 
            @click="changePage(pagination.current_page + 1)"
          >
            Вперед &raquo;
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>