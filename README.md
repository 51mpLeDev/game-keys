# Game Keys Marketplace

Тестовое задание — marketplace цифровых товаров.

## Стек

- Laravel
- Vue 3
- TypeScript
- MySQL
- Docker
- Nginx
- PHP 8.4
- Node.js 22

## Быстрый запуск

Клонировать репозиторий:

```bash
git clone https://github.com/51mpLeDev/game-keys.git
cd game-keys
```

Собрать и запустить контейнеры:

```bash
make build
make up
```

```bash
make install
```

Запустить миграции и сидеры:
```bash
  make fresh
```

Запустить queue worker:

```bash
make queue
```

Frontend:

http://localhost:5173

Backend API:

http://localhost:8080

Admin:

http://localhost:5173/admin/orders

## Make commands

### Запуск проекта

```bash
make up
```

Запускает Docker-контейнеры.

### Остановка проекта

```bash
make down
```

### Просмотр состояния контейнеров

```bash
make ps
```

### Просмотр логов

```bash
make logs
```

### Queue worker

```bash
make queue
```

Queue worker необходимо запустить отдельно после запуска контейнеров.

Для production-like сценария queue worker должен работать постоянно.

### Тесты

Обычные тесты:

```bash
make test
```

Concurrency tests:

```bash
make test-race
```

### Полный сценарий

Для проверки проекта с нуля:

```bash
make build
make up
make queue
make test
make test-race
```

## Основной функционал

Реализовано:

- каталог цифровых товаров;
- создание заказа;
- idempotency для создания заказа;
- mock payment webhook;
- автоматическая выдача ключей;
- защита inventory от двойной выдачи;
- страница заказа;
- отслеживание статуса заказа;
- повторная выдача товара;
- обработка отсутствия товара;
- mock delivery providers;
- retry после ошибок provider;
- promo codes;
- защита лимита использования промокодов при concurrency;
- admin interface для просмотра заказов.

## Stage 1

Основной сценарий:

```text
Выбор товара
    ↓
Создание заказа
    ↓
Оплата
    ↓
Payment webhook
    ↓
Резервирование ключа
    ↓
Delivery provider
    ↓
Выдача ключа
    ↓
delivered
```

После успешной оплаты доступный ключ автоматически резервируется и выдаётся заказу.

Один inventory key не может быть выдан двум заказам.

## Stage 2 — Exactly Once / Concurrency

Критические операции защищены комбинацией:

- database transactions;
- `SELECT ... FOR UPDATE`;
- unique constraints;
- idempotency keys;
- проверок текущего состояния заказа.

### Создание заказа

Frontend генерирует `order_id` до отправки запроса.

Повторный запрос с тем же `order_id` возвращает уже существующий заказ вместо создания нового.

Это защищает от:

```text
double click
network retry
duplicate request
```

### Payment webhook

Endpoint:

```text
POST /api/webhooks/payment
```

Payload:

```json
{
    "event_id": "payment-event-001",
    "order_id": "order-uuid",
    "status": "paid",
    "amount": 1290,
    "currency": "RUB",
    "created_at": "2026-09-06T10:00:00Z"
}
```

Webhook считается at-least-once.

`event_id` используется как idempotency key.

В базе есть unique constraint на `payment_events.event_id`.

Поэтому повторная доставка одного события не приводит к повторной обработке.

Также поддерживается ситуация, когда webhook приходит раньше завершения создания заказа.

Событие сохраняется и обрабатывается после появления заказа.

### Inventory

При выдаче ключ блокируется внутри транзакции.

Упрощённая схема:

```text
BEGIN
    ↓
LOCK order
    ↓
CHECK current state
    ↓
LOCK inventory key
    ↓
RESERVE key
    ↓
UPDATE order
    ↓
COMMIT
```

После получения результата provider:

```text
BEGIN
    ↓
LOCK issuance
    ↓
LOCK inventory key
    ↓
MARK key as issued
    ↓
MARK issuance as issued
    ↓
MARK order as delivered
    ↓
COMMIT
```

Дополнительные unique constraints в базе являются последним уровнем защиты от race conditions.

## Race tests

Запуск:

```bash
make test-race
```

Тесты выполняют реальные конкурентные HTTP-запросы к приложению.

Проверяются:

- 50 одновременных запросов создания заказа;
- 50 одновременных payment webhook для одного заказа;
- 50 одновременных запросов с одним promo code.

### Order creation race

50 одновременных запросов с одним `order_id` должны создать только один заказ.

### Payment webhook race

50 одновременных webhook для одного заказа должны привести только к одной выдаче ключа.

### Promo race

50 одновременных заказов используют один промокод с ограниченным количеством применений.

Количество успешных применений не может превышать `max_uses`.

## Stage 3 — Delivery Recovery

Для выдачи используются mock providers:

```text
provider_a
provider_b
```

Provider поддерживает сценарии:

- success;
- error;
- timeout;
- timeout_once;
- configurable delay.

### Provider idempotency

Каждый запрос к provider получает:

```text
request_id
```

Например:

```text
order-123-item-456
```

Если provider уже выдал ключ для этого `request_id`, повторный запрос возвращает тот же результат.

Это защищает от ситуации:

```text
request
    ↓
provider issued key
    ↓
network timeout
    ↓
retry
    ↓
same request_id
    ↓
same key
```

Таким образом, timeout не приводит к повторной выдаче.

### Out of stock

Если оплата прошла, но свободного ключа нет:

```text
paid
 ↓
out_of_stock
```

Заказ сохраняется.

После пополнения inventory можно выполнить повторную выдачу:

```text
out_of_stock
 ↓
retry
 ↓
delivering
 ↓
delivered
```

Retry является idempotent.

## Stage 4 — Promo Codes

Промокод передаётся серверу:

```json
{
    "promo_code": "PROMO10"
}
```

Размер скидки рассчитывает backend.

Frontend не является источником истины для цены.

Для защиты `max_uses` строка промокода блокируется:

```sql
SELECT ... FOR UPDATE
```

Использование промокода также имеет unique constraint:

```text
(promo_code_id, order_id)
```

Поэтому один заказ не может применить один промокод несколько раз.

При конкурентных запросах количество использований не превышает `max_uses`.

## Order statuses

Используются следующие статусы:

```text
created
paid
delivering
delivered
payment_failed
out_of_stock
delivery_failed
```

## Admin

Admin interface:

http://localhost:5173/admin/orders

В демо-версии authentication для admin interface не реализована.

Admin позволяет:

- просматривать заказы;
- фильтровать заказы;
- видеть `out_of_stock`;
- видеть `delivery_failed`;
- запускать повторную выдачу товара.

## Demo сценарий

### 1. Создать заказ

Открыть:

http://localhost:5173

Выбрать товар и нажать `Купить`.

### 2. Применить промокод

Ввести:

```text
PROMO10
```

Промокод передаётся на backend.

Сервер самостоятельно рассчитывает итоговую стоимость.

### 3. Оплатить

На странице заказа нажать:

```text
Оплатить
```

Используется mock payment webhook.

### 4. Получить ключ

После успешной оплаты система автоматически резервирует доступный ключ и выдаёт его пользователю.

### 5. Проверить out of stock

Для товара без доступных ключей заказ после оплаты перейдёт в:

```text
out_of_stock
```

После добавления ключа в inventory можно выполнить retry delivery через admin interface.

## API

### Create order

```text
POST /api/orders
```

Пример:

```json
{
    "sku": "KEY-CS2-PRIME",
    "quantity": 1,
    "order_id": "uuid",
    "promo_code": "PROMO10"
}
```

### Get order

```text
GET /api/orders/{id}
```

### Payment webhook

```text
POST /api/webhooks/payment
```

### Retry delivery

```text
POST /api/orders/{id}/retry-delivery
```

### Admin orders

```text
GET /api/admin/orders
```

## Architecture

Упрощённая структура:

```text
Vue 3
  │
  ▼
Laravel API
  │
  ├── OrderService
  │
  ├── PaymentWebhookService
  │
  ├── OrderDeliveryService
  │
  ├── DeliveryProviderService
  │
  └── PromoCodeService
          │
          ▼
        MySQL
```

Основная бизнес-логика находится в сервисах.

HTTP controllers отвечают за валидацию и API response.

Критические операции выполняются внутри database transactions.

## Queue

Для фоновой обработки используется Laravel queue.

Queue worker запускается отдельно:

```bash
make queue
```

В отдельном терминале рекомендуется оставить worker запущенным:

```text
Terminal 1:
make up

Terminal 2:
make queue
```

После этого приложение готово к работе.

## Project structure

```text
game-keys/
├── backend/
│   ├── app/
│   │   ├── Enums/
│   │   ├── Exceptions/
│   │   ├── Http/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   └── tests/
│
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── data/
│   │   ├── router/
│   │   └── views/
│   └── ...
│
├── docker/
│   ├── nginx/
│   └── php/
│
├── docker-compose.yml
├── docker-compose.test.yml
├── Makefile
└── README.md
```

## Tests

Обычные тесты:

```bash
make test
```

Concurrency tests:

```bash
make test-race
```

Проверяются:

- создание заказа;
- idempotency заказа;
- payment webhook;
- duplicate webhook;
- concurrent webhook;
- inventory locking;
- delivery;
- provider errors;
- provider timeout;
- delivery recovery;
- promo codes;
- promo max uses;
- concurrent promo usage.

## Что не реализовано

В рамках тестового задания намеренно не реализованы:

- реальная платёжная система;
- реальный delivery provider;
- production authentication для admin;
- полноценная пользовательская система;
- production monitoring.

Вместо внешних сервисов используются mock-реализации.

## Основной архитектурный принцип

Для критических операций используется принцип:

> Проверка состояния и изменение состояния должны происходить атомарно.

То есть недостаточно сделать:

```text
check
 ↓
update
```

отдельными запросами.

Вместо этого используется:

```text
BEGIN
 ↓
LOCK
 ↓
CHECK
 ↓
UPDATE
 ↓
COMMIT
```

А database unique constraints используются как дополнительная гарантия целостности данных.

Это позволяет безопасно обрабатывать повторные запросы и конкурентные операции.