<script setup>
import { useOrderStatus } from '../Composables/useOrderStatus'

const props = defineProps({
  status: {
    type: String,
    required: true,
    default: ''
  }
})

const { getStatusLabel, getStatusBadgeClass } = useOrderStatus()
</script>

<template>
  <span :class="['badge', getStatusBadgeClass(status)]">
    {{ getStatusLabel(status) }}
  </span>
</template>