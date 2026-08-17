import { ref } from 'vue';

/**
 * Переиспользуемая логика пагинации и поиска для списочных компонентов.
 *
 * Инкапсулирует дублирующееся ранее по всем спискам (Заказы, Клиенты, Поставки,
 * Остатки, Движения) состояние: размер страницы, метаданные пагинации и debounce
 * для текстового поиска.
 *
 * @param {number} defaultPerPage Размер страницы по умолчанию
 * @returns {{
 *   perPage: import('vue').Ref<number>,
 *   pagination: import('vue').Ref<object>,
 *   setMeta: (meta: object|undefined) => void,
 *   debounce: (fn: Function, delay?: number) => (...args: any[]) => void
 * }}
 */
export function usePagination(defaultPerPage = 15) {
  const emptyMeta = () => ({
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0,
  });

  const perPage = ref(defaultPerPage);
  const pagination = ref(emptyMeta());

  // Безопасно применяет метаданные пагинации из ответа API
  const setMeta = (meta) => {
    pagination.value = meta || emptyMeta();
  };

  // Фабрика debounce-обработчиков для полей поиска
  const debounce = (fn, delay = 300) => {
    let timeout = null;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => fn(...args), delay);
    };
  };

  return { perPage, pagination, setMeta, debounce };
}
