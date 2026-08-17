export function useOrderStatus() {
  const statusLabels = {
    active: 'Активен',
    completed: 'Завершен',
    canceled: 'Отменен',
  }

  const statusClasses = {
    active: 'bg-warning text-dark',
    completed: 'bg-success',
    canceled: 'bg-danger',
  }

  const getStatusLabel = (status) => statusLabels[status] || status || '—'

  const getStatusBadgeClass = (status) => statusClasses[status] || 'bg-secondary'

  return {
    statusLabels,
    statusClasses,
    getStatusLabel,
    getStatusBadgeClass,
  }
}