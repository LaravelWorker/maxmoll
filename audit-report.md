# 📋 Отчет об аудите проекта

> Проект: складской учёт на **Laravel + Vue 3** (`maxmoll`).
> Аудит проведён на соответствие ТЗ из `.github/TZ.md`.
> Все критические правки внесены прямо в файлы проекта. Тесты (17 шт., 45 проверок) и сборка фронтенда (`vite build`) — зелёные после исправлений.

---

## 1. Сводный вердикт

- [x] **Требуются доработки** → доработки выполнены в рамках данного аудита.
- [x] После исправлений: проект **соответствует ТЗ** (схема БД строго приведена к ТЗ, устранён скрытый баг API-ресурса, унифицирована работа со стоком через транзакции).

Архитектура в целом сильная: единый `StockService`, тонкие контроллеры, FormRequest-валидация, API Resources, пессимистические блокировки, покрытие тестами, сидеры. Найденные проблемы носили точечный характер (расхождения схемы с ТЗ + один латентный баг сериализации + несогласованность транзакций).

---

## 2. Найденные критические ошибки и несоответствия ТЗ

### 🔴 2.1. Схема `orders.status` не соответствовала ТЗ (был `enum`, требуется `varchar(255)`)
**Файл:** `database/migrations/2026_08_14_092617_create_orders_table.php:44`

Было:
```php
$table->enum('status', OrderStatus::values())->default(OrderStatus::ACTIVE->value);
```
ТЗ прямо требует `status - varchar(255)`. `enum` — это иной тип столбца (жёстко зашитый в DDL перечень значений), что нарушает пункт «поля НЕ менялись» и усложняет добавление статусов/миграции между СУБД.

**Исправлено** → `varchar(255)` + индекс по статусу (ускоряет расчёт резервов активных заказов). Ограничение значений обеспечивается на уровне приложения (Enum-каст в `Order` и `Rule::enum` в `OrderIndexRequest`):
```php
$table->string('status', 255)->default(OrderStatus::ACTIVE->value);
$table->index('status');
```
Проверено: `SHOW COLUMNS FROM orders` → `status varchar(255)`.

### 🔴 2.2. Таблица `stocks` не соответствовала ТЗ + конфликт миграции с моделью
**Файл:** `database/migrations/2026_08_14_093526_create_stocks_table.php` и `app/Models/Stock.php`

ТЗ описывает `stocks` строго тремя полями: `product_id`, `warehouse_id`, `stock`. В миграции же были добавлены `$table->id()` (авто-инкрементный PK) и `$table->timestamps()`.

Это порождало **архитектурное противоречие**: модель `App\Models\Stock` объявляет составной первичный ключ
```php
public $incrementing = false;
protected $primaryKey = ['product_id', 'warehouse_id'];
```
и переопределяет `setKeysForSaveQuery()`, то есть колонка `id` в БД существовала, но моделью полностью игнорировалась (мёртвый столбец), а `created_at/updated_at` не использовались (`$timestamps = false`). Комментарий в миграции утверждал, что `id` нужен «для `$stock->id` в StockService и StockResource» — по факту ни один из них `id` не использует.

**Исправлено** → таблица приведена строго к ТЗ; составной первичный ключ вынесен в БД, что делает модель и миграцию согласованными:
```php
$table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
$table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
$table->integer('stock')->default(0);
$table->primary(['warehouse_id', 'product_id']); // = уникальность пары «склад+товар»
// + сохранён CHECK (stock >= 0)
```
Проверено: `SHOW CREATE TABLE stocks` → поля `(product_id, warehouse_id, stock)`, `PRIMARY KEY (warehouse_id, product_id)`, `CHECK (stock >= 0)`. Все тесты со `Stock::create()` / `increment` / `decrement` / `lockForUpdate` продолжают проходить.

### 🔴 2.3. Латентный баг: `ProductResource.stocks` возвращал `null`-поля
**Файл:** `app/Http/Resources/ProductResource.php:20`

Было:
```php
'stocks' => StockResource::collection($this->whenLoaded('warehouses')),
```
Связь `Product::warehouses()` — это `belongsToMany` через таблицу `stocks`, то есть в коллекцию попадают модели **`Warehouse`**, а остаток лежит в `pivot->stock`. `StockResource` же читает поля `warehouse_id / product_id / stock` напрямую с модели — у `Warehouse` таких атрибутов нет. В результате эндпоинт `GET /api/products` отдавал массив вида `{"warehouse_id": null, "product_id": null, "stock": null}`.

**Исправлено** → корректное чтение pivot:
```php
'stocks' => $this->whenLoaded('warehouses', fn () => $this->warehouses->map(fn ($warehouse) => [
    'warehouse_id' => $warehouse->id,
    'warehouse'    => $warehouse->name,
    'stock'        => (int) $warehouse->pivot->stock,
])->values()),
```
Проверено через `tinker`: теперь возвращаются реальные остатки по складам (`244`, `210`, `305`, …).

### 🟠 2.4. `order_items.count` — `unsignedInteger` вместо `integer` (ТЗ: `integer`)
**Файл:** `database/migrations/2026_08_14_092928_create_order_items_table.php:39`

ТЗ: `count - integer`. Также это создавало рассогласование с `supply_items.count` и `transfer_items.count` (оба `integer`). **Исправлено** → `integer` (нижняя граница `min:1` уже гарантируется `StoreOrderRequest`).

---

## 3. Замечания по архитектуре и коду

### 3.1. Единый `StockService` — соблюдён ✅ (транзакции унифицированы)
Все физические изменения остатков и запись движений проходят строго через `StockService` (`incrementStock` / `decrementStock` / `recordMovement`); прямых `Stock::...->save()/update()` в бизнес-коде нет. `SupplyService`, `TransferService`, `OrderService::complete()` используют только сервис.

**Недочёт (исправлен):** `decrementStock()` использовал `lockForUpdate()`, но, в отличие от `incrementStock()`, **не оборачивал** операцию в `DB::transaction()`. Пессимистическая блокировка вне транзакции немедленно снимается (autocommit) → в сценарии прямого вызова (в т.ч. в `StockServiceTest`) она была неэффективна.
**Файл:** `app/Services/StockService.php`
**Исправлено** → `decrementStock()` обёрнут в `DB::transaction()` (при вложенном вызове из `OrderService`/`TransferService` создаётся savepoint — целостность не нарушается, блокировка теперь всегда удерживается до коммита). Заодно убрано мёртвое `if ($document)` в `incrementStock()` (аргумент нетипизируемо-нулевой: `Model $document`).

### 3.2. Тонкие контроллеры — в основном соблюдено ✅
Контроллеры делегируют бизнес-логику сервисам, используют FormRequest и Resource. Логика фильтрации/пагинации в `index()`-методах — это построение запроса представления, допустимо.

### 3.3. Замечания (рекомендации, не критично)
- **Дублирование листинга остатков:** `StockController@index` и `WarehouseController@stocks` реализуют почти одинаковую выборку `stocks` с фильтрами. Стоит оставить один канонический эндпоинт (или вынести общий query-scope на модель `Stock`), чтобы не поддерживать две копии.
- **`WarehouseController` перегружен:** содержит `storeTransfer`, `stocks`, `products` — смешение зон ответственности (склады/перемещения/товары/остатки). Перемещения логичнее вынести в отдельный `TransferController` (создание уже тонкое — делегирует `TransferService`). Также в системе нет `GET`-эндпоинта списка перемещений — только создание.
- **`doc_type` в `stock_movements`** хранит FQCN (`App\Models\Order`). Работает корректно, но для стабильности API и читаемости фронта имеет смысл задать `Relation::enforceMorphMap([...])` (короткие алиасы `order`/`supply`/`transfer`).
- **PHPDoc / комментарии** — очень полное и качественное покрытие во всех слоях (модели, сервисы, ресурсы, миграции), пункт ТЗ выполнен.

### 3.4. Параллельность и блокировки ✅
`DB::transaction()` + `lockForUpdate()` применяются во всех критичных операциях (создание/обновление/возобновление заказа, поставки, перемещения, инкремент/декремент стока). Атомарность обновления остатка обеспечена `increment()/decrement()` (SQL `stock = stock ± N`). Дополнительно на уровне БД стоит `CHECK (stock >= 0)`.

---

## 4. Чек-лист соответствия функционала

- [x] **Справочники и Клиенты** — `warehouses`, `products` (+ `/warehouses/products` для выпадающих списков), `customers` (index/store/update с фильтрами и пагинацией).
- [x] **Заказы и списки** — `index` (фильтры по покупателю/складу/статусу + пагинация), `store`, `update`, `destroy`, `complete`, `cancel`, `restore`.
- [x] **Проверка остатков при списании/возобновлении** — `OrderService::validateStockAvailability()` учитывает **физический остаток** и **резерв активных заказов** с `lockForUpdate`; `restore()` выполняет ту же проверку и бросает ошибку при нехватке. Списание при `complete()` — через `StockService::decrementStock()`.
- [x] **Запрет редактирования completed/canceled** — `OrderService::update()` / `destroy()` бросают исключение для не-`active` заказов (покрыто тестом `test_update_completed_or_canceled_order_throws_exception`).
- [x] **История движений (`stock_movements`)** — таблица с `warehouse_id, product_id, quantity, doc_type, doc_id, created_at` (полиморфный `morphs('doc')`); эндпоинт `GET /api/stock-movements` с фильтрами (склад, товар, тип документа, диапазон дат) и экраном истории на фронте.
- [x] **Перемещения между складами** — таблицы `transfers` / `transfer_items`; `TransferService` (проверка остатка+резерва, списание с источника, зачисление получателю, парные проводки); эндпоинт `POST /api/warehouses/transfers`.
- [x] **Frontend Vue 3** — все 11 компонентов на `<script setup>`; статусы локализованы («Активен/Завершен/Отменен») через composable `useOrderStatus`; есть форма создания/редактирования заказа (`Order/FormModal.vue`) и экран истории движений (`Stock/Movements.vue`); вынесены общие компоненты `Common/Pagination`, `Common/OrderStatusBadge`.

---

## 5. Рекомендации по исправлению (что сделано и что ещё стоит сделать)

### ✅ Уже исправлено в этом аудите
1. `orders.status`: `enum` → `varchar(255)` + индекс (`create_orders_table`).
2. `stocks`: удалены `id()` и `timestamps()`, задан составной PK `(warehouse_id, product_id)` — таблица строго по ТЗ, согласована с моделью (`create_stocks_table`).
3. `order_items.count`: `unsignedInteger` → `integer` (`create_order_items_table`).
4. `ProductResource.stocks`: исправлена сериализация pivot — эндпоинт `/api/products` больше не возвращает `null`-остатки.
5. `StockService::decrementStock()`: обёрнут в `DB::transaction()` (эффективный `lockForUpdate`), убран мёртвый `if ($document)`.
6. Frontend `Order/List.vue`: удалён дублирующий словарь `statusLabels`/`getStatusLabel`, фильтр статусов теперь строится из единого composable `useOrderStatus` (единый источник правды для локализации).

### 🔧 Рекомендуется к доработке (некритично, вне рамок правок)
1. **Консолидировать листинг остатков** — убрать дублирование между `StockController@index` и `WarehouseController@stocks`; общий фильтр вынести в query-scope модели `Stock`.
2. **Вынести перемещения** в отдельный `TransferController` и добавить `GET /api/transfers` (список перемещений с фильтрами), симметрично поставкам.
3. **Morph map** для `stock_movements.doc_type` (`Relation::enforceMorphMap`) — короткие алиасы вместо FQCN, стабильнее для API/фронта.
4. **Vue-дедупликация (продолжение):**
   - вынести обёртку модального окна (backdrop + header + footer) в `Common/BaseModal.vue` — она повторяется в `Order/FormModal`, `Customer/FormModal`, `Supplies/FormModal`;
   - вынести axios-загрузку справочников (customers/warehouses/products) в composable `useDictionaries()` (сейчас дублируется в формах);
   - привести структуру `pagination` к единому виду (в `Order/List` объект инициализируется полями `currentPage/lastPage`, а с бэка приходит `current_page/last_page` — совпадает с ожиданиями `Common/Pagination`, но локальная инициализация вводит в заблуждение).
5. **Валидация обновления склада заказа:** в `OrderService::update()` при смене только `warehouse_id` (без `items`) остатки на новом складе не перепроверяются — стоит валидировать доступность при изменении склада.

---

### Приложение: как проверялось
- `composer install` + `php artisan migrate:fresh --seed` на MySQL — миграции и сидеры отрабатывают без ошибок (сгенерировано: 69 остатков, 13 заказов, 121 движение, 12 поставок, 4 перемещения).
- `php artisan test` → **17 passed (45 assertions)** до и после правок.
- `npm run build` (`vite build`) → сборка фронтенда успешна после Vue-правок.
- Схема БД проверена через `SHOW CREATE TABLE` / `SHOW COLUMNS`; вывод `ProductResource` — через `tinker`.
