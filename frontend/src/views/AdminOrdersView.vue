<template>
  <div class="admin-page">
    <header class="admin-header">
      <div>
        <p class="admin-header__eyebrow">GAME KEYS</p>
        <h1>Заказы</h1>
      </div>

      <button
          class="refresh-button"
          type="button"
          :disabled="loading"
          @click="loadOrders"
      >
        {{ loading ? 'Загрузка...' : 'Обновить' }}
      </button>
    </header>

    <main class="admin-content">
      <nav class="filters">
        <button
            v-for="filter in filters"
            :key="filter.value"
            type="button"
            class="filter"
            :class="{ 'filter--active': activeFilter === filter.value }"
            @click="setFilter(filter.value)"
        >
          {{ filter.label }}
        </button>
      </nav>

      <div v-if="error" class="state state--error">
        {{ error }}
      </div>

      <div v-else-if="loading" class="state">
        Загрузка заказов...
      </div>

      <div v-else-if="orders.length === 0" class="state">
        Заказов нет
      </div>

      <section v-else class="orders">
        <article
            v-for="order in orders"
            :key="order.id"
            class="order-card"
        >
          <div class="order-card__main">
            <div class="order-card__top">
              <div>
                <span class="order-card__label">Заказ</span>
                <code class="order-card__id">{{ order.id }}</code>
              </div>

              <span
                  class="status"
                  :class="`status--${order.status}`"
              >
                {{ statusLabel(order.status) }}
              </span>
            </div>

            <div class="order-card__product">
              <div>
                <strong>{{ order.items[0]?.name ?? 'Товар' }}</strong>
                <span>
                  {{ order.items[0]?.sku }}
                  · {{ order.items[0]?.quantity }} шт.
                </span>
              </div>

              <strong class="order-card__price">
                {{ formatPrice(order.amount, order.currency) }}
              </strong>
            </div>

            <div class="order-card__meta">
              <span>{{ formatDate(order.created_at) }}</span>

              <span v-if="order.keys.length">
                Ключей: {{ order.keys.length }}
              </span>

              <span v-if="order.keys.length">
                {{ order.keys[0].code }}
              </span>
            </div>
          </div>

          <div
              v-if="canRetry(order.status)"
              class="order-card__actions"
          >
            <button
                class="retry-button"
                type="button"
                :disabled="retryingOrderId === order.id"
                @click="retryDelivery(order)"
            >
              {{
                retryingOrderId === order.id
                    ? 'Выдача...'
                    : 'Повторить выдачу'
              }}
            </button>
          </div>
        </article>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

const API_URL = 'http://localhost:8080/api'

type OrderStatus =
    | 'created'
    | 'paid'
    | 'delivering'
    | 'delivered'
    | 'payment_failed'
    | 'out_of_stock'
    | 'delivery_failed'

interface OrderItem {
  sku: string
  name: string
  quantity: number
}

interface OrderKey {
  code: string
  status: string
}

interface Order {
  id: string
  status: OrderStatus
  amount: number
  currency: string
  created_at: string
  items: OrderItem[]
  keys: OrderKey[]
}

type FilterValue = 'all' | 'out_of_stock' | 'delivery_failed' | 'delivered'

const filters: Array<{
  label: string
  value: FilterValue
}> = [
  { label: 'Все', value: 'all' },
  { label: 'Нет ключа', value: 'out_of_stock' },
  { label: 'Ошибка выдачи', value: 'delivery_failed' },
  { label: 'Доставлены', value: 'delivered' },
]

const orders = ref<Order[]>([])
const activeFilter = ref<FilterValue>('all')
const loading = ref(false)
const error = ref('')
const retryingOrderId = ref<string | null>(null)

async function loadOrders() {
  loading.value = true
  error.value = ''

  try {
    const url = new URL(`${API_URL}/admin/orders`)

    if (activeFilter.value !== 'all') {
      url.searchParams.set('status', activeFilter.value)
    }

    const response = await fetch(url)

    if (!response.ok) {
      throw new Error('Не удалось загрузить заказы.')
    }

    const result = await response.json()

    orders.value = result.data?.data ?? []
  } catch (err) {
    console.error(err)

    error.value = 'Не удалось загрузить заказы.'
  } finally {
    loading.value = false
  }
}

async function setFilter(filter: FilterValue) {
  if (activeFilter.value === filter) {
    return
  }

  activeFilter.value = filter

  await loadOrders()
}

function canRetry(status: OrderStatus): boolean {
  return (
      status === 'out_of_stock' ||
      status === 'delivery_failed'
  )
}

async function retryDelivery(order: Order) {
  if (retryingOrderId.value) {
    return
  }

  retryingOrderId.value = order.id
  error.value = ''

  try {
    const response = await fetch(
        `${API_URL}/orders/${order.id}/retry-delivery`,
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
          },
        },
    )

    if (!response.ok) {
      const message = await response.text()

      throw new Error(message || 'Не удалось повторить выдачу.')
    }

    await loadOrders()
  } catch (err) {
    console.error(err)

    error.value = 'Не удалось повторить выдачу.'
  } finally {
    retryingOrderId.value = null
  }
}

function statusLabel(status: OrderStatus): string {
  const labels: Record<OrderStatus, string> = {
    created: 'Создан',
    paid: 'Оплачен',
    delivering: 'Выдаётся',
    delivered: 'Доставлен',
    payment_failed: 'Ошибка оплаты',
    out_of_stock: 'Нет ключа',
    delivery_failed: 'Ошибка выдачи',
  }

  return labels[status]
}

function formatPrice(
    amount: number,
    currency: string,
): string {
  return `${new Intl.NumberFormat('ru-RU').format(amount)} ${currency}`
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat('ru-RU', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

onMounted(loadOrders)
</script>

<style scoped>
.admin-page {
  min-height: 100vh;
  background: #f7f7f8;
  color: #171717;
}

.admin-header {
  width: min(1160px, calc(100% - 32px));
  margin: 0 auto;
  padding: 34px 0 26px;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
}

.admin-header__eyebrow {
  margin: 0 0 6px;
  color: #8b8f96;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.12em;
}

.admin-header h1 {
  margin: 0;
  font-size: 30px;
  line-height: 1.1;
  font-weight: 700;
}

.refresh-button {
  min-height: 40px;
  padding: 0 17px;
  border: 1px solid #dedfe2;
  border-radius: 9px;
  background: #fff;
  color: #202124;
  font-size: 14px;
  cursor: pointer;
}

.refresh-button:disabled {
  opacity: 0.55;
  cursor: default;
}

.admin-content {
  width: min(1160px, calc(100% - 32px));
  margin: 0 auto;
  padding-bottom: 50px;
}

.filters {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 18px;
  overflow-x: auto;
}

.filter {
  flex: 0 0 auto;
  min-height: 38px;
  padding: 0 15px;
  border: 1px solid #dedfe2;
  border-radius: 9px;
  background: #fff;
  color: #656970;
  font-size: 13px;
  cursor: pointer;
}

.filter--active {
  border-color: #171717;
  background: #171717;
  color: #fff;
}

.state {
  padding: 60px 20px;
  border: 1px solid #e8e8ea;
  border-radius: 12px;
  background: #fff;
  color: #85888e;
  text-align: center;
}

.state--error {
  color: #b42318;
}

.orders {
  display: grid;
  gap: 10px;
}

.order-card {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
  gap: 24px;
  border: 1px solid #e7e7e9;
  border-radius: 12px;
  background: #fff;
  overflow: hidden;
}

.order-card__main {
  min-width: 0;
  flex: 1;
  padding: 20px;
}

.order-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
}

.order-card__label {
  display: block;
  margin-bottom: 5px;
  color: #999ca2;
  font-size: 11px;
}

.order-card__id {
  color: #55585e;
  font-size: 12px;
}

.status {
  flex: 0 0 auto;
  padding: 6px 9px;
  border-radius: 7px;
  background: #f0f1f2;
  color: #656970;
  font-size: 11px;
  font-weight: 600;
}

.status--delivered {
  background: #edf8f0;
  color: #278244;
}

.status--out_of_stock,
.status--delivery_failed {
  background: #fff2ed;
  color: #b54722;
}

.status--paid,
.status--delivering {
  background: #eef4ff;
  color: #3567ad;
}

.status--payment_failed {
  background: #fff0f0;
  color: #b42318;
}

.order-card__product {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-top: 18px;
}

.order-card__product strong {
  display: block;
  font-size: 15px;
}

.order-card__product span {
  display: block;
  margin-top: 5px;
  color: #8b8e94;
  font-size: 12px;
}

.order-card__price {
  white-space: nowrap;
}

.order-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 18px;
  color: #8b8e94;
  font-size: 12px;
}

.order-card__actions {
  width: 190px;
  flex: 0 0 190px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  border-left: 1px solid #ededee;
  background: #fcfcfc;
}

.retry-button {
  width: 100%;
  min-height: 40px;
  border: 0;
  border-radius: 8px;
  background: #171717;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.retry-button:disabled {
  opacity: 0.55;
  cursor: default;
}

@media (max-width: 700px) {
  .admin-header {
    align-items: flex-start;
  }

  .order-card {
    display: block;
  }

  .order-card__actions {
    width: auto;
    border-top: 1px solid #ededee;
    border-left: 0;
  }
}
</style>