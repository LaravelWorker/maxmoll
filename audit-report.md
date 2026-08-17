# Отчёт по аудиту проекта MaxMoll

**Дата:** 2026-08-17
**Стек:** Laravel 10 (PHP 8.2+) + Vue 3 (Composition API, Vite, Bootstrap 5)
**Объём проверки:** бэкенд (контроллеры, сервисы, модели, роуты, ресурсы, реквесты, миграции), фронтенд (`resources/js`), тесты.

---

## 1. Резюме

Проект в целом хорошо структурирован: бизнес-логика вынесена в сервисный слой (`OrderService`, `StockService`, `SupplyService`, `TransferService`), используются `FormRequest`, `API Resource`, транзакции и пессимистические блокировки при работе с остатками, полиморфный журнал движений с `morphMap`.

Тем не менее найден ряд **критических** дефектов (нерабочий фронтенд-обработчик, «красный» набор тестов), **нарушений MVC** (контроллер-«бог», семантически неверные роуты, дублирование эндпоинтов) и **архитектурного мусора** (мёртвый и дублирующийся код). Все они исправлены прямо в файлах проекта.

**Итог после правок:**
- PHP-тесты: **17 passed (45 assertions)** — было **3 failed / 14 passed**.
- Сборка фронтенда: `npm run build` — **успешно** (73 модуля).

---

## 2. Критические ошибки

### 2.1. `Order/List.vue` — вызов несуществующих обработчиков (runtime-краш)
**Файл:** `resources/js/components/Order/List.vue`

В шаблоне поля поиска и селект «Показывать по» ссылались на `@input="debouncedFetchOrders"` и `@change="changePerPage"`, однако в `<script setup>` **эти функции не были объявлены** — вместо них стоял `watch([filters, perPage])`. При первом же вводе символа в фильтр или смене размера страницы Vue выбрасывал `TypeError: debouncedFetchOrders is not a function`. Дополнительно `watch` и явные `@change="fetchOrders(1)"` приводили к **двойному** запросу при смене статуса.

**Исправление:** объявлены `debouncedFetchOrders` и `changePerPage`, удалён избыточный `watch` (и импорт `watch`). Поведение приведено к единому виду с остальными списками.

### 2.2. «Красный» набор автотестов — устаревшие ожидания `doc_type`
**Файлы:** `tests/Feature/TransferServiceTest.php`, `tests/Feature/OrderServiceTest.php`, `tests/Feature/StockServiceTest.php`

В `AppServiceProvider` объявлена карта полиморфных типов (`Relation::morphMap(['order','supply','transfer'])`), поэтому `StockMovement::doc_type` хранит **алиасы** (`order`/`supply`/`transfer`). На эти же алиасы завязан фронтенд (фильтр «Тип источника» и `getDocumentLabel` в `Stock/Movements.vue`). Однако тесты ожидали полное имя класса:

```php
'doc_type' => Transfer::class,   // 'App\Models\Transfer' — в БД лежит 'transfer'
```

Из-за этого **3 теста падали** на «свежесобранном» проекте. Продакшн-код корректен (алиасы — верное, целевое поведение); дефект был в ожиданиях тестов.

**Исправление:** ожидания приведены к алиасу через `(new Order())->getMorphClass()` / `(new Transfer())->getMorphClass()` — теперь тест сверяется с тем же источником истины, что и приложение, и не сломается при переименовании классов.

---

## 3. Нарушения MVC и архитектурные недочёты

### 3.1. `WarehouseController` — контроллер-«бог» (нарушение SRP)
**Файлы:** `app/Http/Controllers/Api/WarehouseController.php`, `app/Http/Controllers/Api/TransferController.php` (новый)

Контроллер складов обслуживал **четыре разные предметные области**: список складов, список остатков (`stocks`), список товаров (`products`) и **проведение межскладского перемещения** (`storeTransfer`). Это грубое нарушение принципа единственной ответственности; при этом для товаров и остатков уже существовали профильные `ProductController` и `StockController`.

**Исправление:**
- Создан отдельный **`TransferController`** с методом `store()` (логика вынесена из `WarehouseController::storeTransfer`, статус ответа — `201 Created`, ошибки — `422`).
- Из `WarehouseController` удалены методы `stocks`, `products`, `storeTransfer` и все ставшие лишними `use`. Контроллер теперь отвечает только за справочник складов (`index`).

### 3.2. Семантически неверные роуты
**Файлы:** `routes/api/warehouses.php`, `routes/api/transfers.php` (новый), `app/Providers/RouteServiceProvider.php`

Перемещения, товары и остатки — самостоятельные ресурсы, а не под-ресурсы конкретного склада. Их URL-адреса были вложены в `/api/warehouses/*`:

| Было | Стало |
|------|-------|
| `POST /api/warehouses/transfers` | `POST /api/transfers` |
| `GET /api/warehouses/stocks` | `GET /api/stocks` (уже существовал) |
| `GET /api/warehouses/products` | `GET /api/products` (уже существовал) |

**Исправление:** в `warehouses.php` оставлен только `GET /`; создан `routes/api/transfers.php` и зарегистрирована группа `api/transfers` в `RouteServiceProvider`. Все вызовы на фронтенде переведены на корректные адреса.

### 3.3. Дублирование эндпоинта и логики выборки остатков
**Файл:** `app/Http/Controllers/Api/StockController.php`

`StockController::index` (`/api/stocks`) и `WarehouseController::stocks` (`/api/warehouses/stocks`) делали почти одно и то же, но с **разными именами параметров** (`warehouse`/`product` против `warehouse_id`/`search`). Фронтенд ходил только во второй, а первый фактически был «мёртвым».

**Исправление:** второй эндпоинт удалён (см. 3.1), а `StockController::index` расширен: теперь понимает и точечный фильтр `warehouse_id`, и общий `search` по названию товара, сохраняя обратную совместимость со старыми параметрами `warehouse`/`product` (все они уже описаны в `StockIndexRequest`).

### 3.4. Несогласованный HTTP-клиент на фронтенде
**Файл:** `resources/js/components/Warehouse/Stocks.vue`

Компонент остатков использовал нативный `fetch()` с ручной сборкой URL и заголовков, тогда как весь остальной фронтенд работает через `axios` (с общими заголовками `X-Requested-With`, обработкой ошибок через `err.response`).

**Исправление:** компонент переведён на `axios`; выборка остатков/товаров/складов и проведение перемещения унифицированы с остальными компонентами.

### 3.5. Мёртвый и дублирующийся код
**Файлы:** `resources/js/components/Warehouse/Stocks.vue`, `resources/js/components/Supplies/FormModal.vue` (удалён)

- В `Warehouse/Stocks.vue` присутствовало **полноценное модальное окно создания поставки** (`isSupplyModalOpen`, `supplyForm`, `submitSupply`, `addSupplyItem`, `removeSupplyItem`) — но **не было ни одной кнопки, открывающей его** (`isSupplyModalOpen` никогда не становился `true`). Это недостижимый код, дублирующий вкладку «Поставки». Удалён.
- `Supplies/FormModal.vue` **не импортировался нигде** (в `Supplies/List.vue` своя встроенная форма) и дублировал её. Удалён.

### 3.6. Дублирование логики пагинации/поиска (запрос из ТЗ по Vue)
**Файлы:** `resources/js/components/Composables/usePagination.js` (новый) + все списки

Пять списочных компонентов (`Order/List`, `Customer/List`, `Supplies/List`, `Warehouse/Stocks`, `Stock/Movements`) построчно повторяли один и тот же код: инициализацию объекта `pagination`, `ref` для `perPage`, ручной `setTimeout`-debounce для поиска.

**Исправление:** создан переиспользуемый composable **`usePagination`**, инкапсулирующий `perPage`, `pagination`, `setMeta()` (безопасное применение `meta` из ответа) и фабрику `debounce()`. Composable подключён во все пять компонентов — устранено ~5 копий boilerplate-кода.

---

## 4. Прочие исправления (гигиена кода / контракт API)

### 4.1. Отладочное логирование в продакшн-коде
**Файлы:** `app/Http/Controllers/Api/OrderController.php`, `app/Http/Controllers/Api/StockMovementController.php`

Удалены оставленные при отладке вызовы `Log::info($request->all())` (в `OrderController::index` — потенциальная утечка входных данных в лог) и `Log::info($request->input('doc_type'))` (в `StockMovementController::index`), а также ставшие ненужными импорты `Log`.

### 4.2. Не реализованный, но задокументированный фильтр по датам
**Файл:** `app/Http/Controllers/Api/StockMovementController.php`

`StockMovementIndexRequest` валидирует `date_from`/`date_to`, а docblock метода обещает фильтрацию «по временному диапазону», но в контроллере она **не применялась**. Добавлена фильтрация `whereDate('created_at', ...)` — контракт API приведён в соответствие с документацией.

### 4.3. Переносимость миграции (разблокировка тестов)
**Файл:** `database/migrations/2026_08_14_093526_create_stocks_table.php`

Ограничение неотрицательного остатка создавалось безусловным `DB::statement('ALTER TABLE stocks ADD CONSTRAINT ... CHECK ...')` — синтаксис MySQL/PostgreSQL, который **падает на sqlite** (используется для in-memory тестов), из-за чего весь набор тестов не мог даже подняться.

**Исправление:** вызов обёрнут в проверку драйвера (`mysql`/`mariadb`/`pgsql`). На боевой БД (MySQL) поведение **не изменилось**, но теперь тесты могут выполняться на sqlite.

---

## 5. Проверка результата

```
# Бэкенд
$ php artisan test           # на sqlite :memory:
Tests:    17 passed (45 assertions)

$ php artisan route:list --path=api
POST  api/transfers  → Api\TransferController@store     # новый, корректный ресурс
GET   api/stocks     → Api\StockController@index
GET   api/products   → Api\ProductController@index
GET   api/warehouses → Api\WarehouseController@index    # только справочник складов

# Фронтенд
$ npm run build
✓ 73 modules transformed. ✓ built in ~0.3s
```

---

## 6. Рекомендации (не вошли в правки — предлагаются к обсуждению)

Эти пункты не являются критичными и не менялись, чтобы не расширять объём правок сверх поставленной задачи:

1. **`created_at` в `$fillable`.** У моделей `Order`, `Customer`, `Supply`, `Transfer` поле `created_at` присутствует в `$fillable`, а сервисы дополнительно передают `'created_at' => now()`. Так как timestamps управляются Eloquent автоматически, это избыточно и потенциально позволяет клиенту подменить дату. Рекомендуется убрать `created_at` из `$fillable`.
2. **Единый `BaseModal.vue`.** Разметка модальных окон (backdrop, header, footer) дублируется в 3+ компонентах — стоит вынести в базовый компонент со слотами.
3. **Единый разбор ошибок валидации.** Логика «собрать `response.data.errors` в строку» повторяется в нескольких формах — вынести в helper/composable (например, `useApiErrors`).
4. **Список как composable/компонент.** Связка «карточка фильтров + fetch + пагинация» повторяется во всех списках; `usePagination` — первый шаг, следующим можно вынести полноценный `useResourceList`.
5. **Авторизация.** Все `FormRequest::authorize()` возвращают `true`, эндпоинты открыты. Для учебного задания приемлемо, но для боевого окружения потребуется аутентификация (Sanctum уже подключён).
6. **Резерв под нагрузкой.** Подсчёт зарезервированного количества (`getReservedStock` / `validateStockAvailability`) читает `order_items` без блокировки. Физический остаток блокируется (`lockForUpdate`), поэтому отрицательный остаток исключён, но при экстремальной конкуренции возможен временный «перерезерв». Как усиление — блокировать/пересчитывать резерв в той же транзакции.

---

## 7. Изменённые и добавленные файлы

**Добавлены:**
- `app/Http/Controllers/Api/TransferController.php`
- `routes/api/transfers.php`
- `resources/js/components/Composables/usePagination.js`

**Удалены:**
- `resources/js/components/Supplies/FormModal.vue` (мёртвый дубль)

**Изменены (бэкенд):**
- `app/Http/Controllers/Api/WarehouseController.php`
- `app/Http/Controllers/Api/StockController.php`
- `app/Http/Controllers/Api/OrderController.php`
- `app/Http/Controllers/Api/StockMovementController.php`
- `app/Providers/RouteServiceProvider.php`
- `routes/api/warehouses.php`
- `database/migrations/2026_08_14_093526_create_stocks_table.php`
- `tests/Feature/OrderServiceTest.php`, `tests/Feature/StockServiceTest.php`, `tests/Feature/TransferServiceTest.php`

**Изменены (фронтенд):**
- `resources/js/components/Order/List.vue`
- `resources/js/components/Customer/List.vue`
- `resources/js/components/Supplies/List.vue`
- `resources/js/components/Warehouse/Stocks.vue`
- `resources/js/components/Stock/Movements.vue`
