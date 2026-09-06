<script setup lang="ts">
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {useRoute} from 'vue-router'

interface OrderItem {
  sku: string
  name: string
  price: number
  currency: string
  quantity: number
}

interface OrderKey {
  code: string
}

interface Order {
  id: string
  status: string
  amount: number
  currency: string
  items: OrderItem[]
  keys: OrderKey[]
  paid_at: string | null
  delivered_at: string | null
}

const route = useRoute()

const order = ref<Order | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

let pollingTimer: ReturnType<typeof setInterval> | null = null

const statusText = computed(() => {
  switch (order.value?.status) {
    case 'created':
      return 'Ожидает оплаты'

    case 'paid':
      return 'Оплата получена'

    case 'delivering':
      return 'Выдаём товар'

    case 'delivered':
      return 'Товар выдан'

    case 'payment_failed':
      return 'Ошибка оплаты'

    case 'out_of_stock':
      return 'Товар закончился'

    case 'delivery_failed':
      return 'Ошибка выдачи'

    default:
      return 'Неизвестный статус'
  }
})

const isFinished = computed(() => {
  return [
    'delivered',
    'payment_failed',
    'out_of_stock',
    'delivery_failed',
  ].includes(order.value?.status ?? '')
})

const steps = computed(() => [
  {
    title: 'Заказ создан',
    done: true,
  },
  {
    title: 'Оплата',
    done: ['paid', 'delivering', 'delivered'].includes(
        order.value?.status ?? '',
    ),
  },
  {
    title: 'Выдача',
    done: ['delivered'].includes(
        order.value?.status ?? '',
    ),
  },
  {
    title: 'Готово',
    done: order.value?.status === 'delivered',
  },
])

async function loadOrder() {
  try {
    const response = await fetch(
        `http://localhost:8080/api/orders/${route.params.id}`,
        {
          headers: {
            Accept: 'application/json',
          },
        },
    )

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const result = await response.json()

    order.value = result.data
    error.value = null
  } catch (err) {
    console.error(err)
    error.value = 'Не удалось загрузить заказ'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadOrder()

  pollingTimer = setInterval(async () => {
    if (isFinished.value) {
      return
    }

    await loadOrder()
  }, 2000)
})

onUnmounted(() => {
  if (pollingTimer) {
    clearInterval(pollingTimer)
  }
})

const paying = ref(false)
const retrying = ref(false)

async function payOrder() {
  if (!order.value || paying.value) {
    return
  }

  try {
    paying.value = true

    const response = await fetch(
        'http://localhost:8080/api/webhooks/payment',
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            event_id: `demo-payment-${order.value.id}`,
            order_id: order.value.id,
            status: 'paid',
            amount: order.value.amount,
            currency: order.value.currency,
            created_at: new Date().toISOString(),
          }),
        },
    )

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    await loadOrder()
  } catch (err) {
    console.error(err)
    error.value = 'Не удалось провести оплату'
  } finally {
    paying.value = false
  }

  const originalAmount = computed(() => {
    return order.value?.items.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0,
    ) ?? 0
  })

  const discount = computed(() => {
    if (!order.value) {
      return 0
    }

    return Math.max(0, originalAmount.value - order.value.amount)
  })
}

async function retryDelivery() {
  if (!order.value || retrying.value) {
    return
  }

  try {
    retrying.value = true

    const response = await fetch(
        `http://localhost:8080/api/orders/${order.value.id}/retry-delivery`,
        {
          method: 'POST',
          headers: {
            Accept: 'application/json',
          },
        },
    )

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const result = await response.json()

    order.value = result.data
  } catch (err) {
    console.error(err)
    error.value = 'Не удалось повторить выдачу'
  } finally {
    retrying.value = false
  }
}
</script>

<template>
  <div class="order-page">
    <div class="order-page__container">

      <a href="/" class="back-link">
        ← Вернуться в магазин
      </a>

      <div v-if="loading" class="state">
        Загрузка заказа...
      </div>

      <div v-else-if="error" class="state state--error">
        {{ error }}
      </div>

      <template v-else-if="order">

        <header class="order-header">
          <div>
            <div class="order-header__label">
              Заказ
            </div>

            <h1>
              #{{ order.id }}
            </h1>
          </div>

          <div
              class="status"
              :class="`status--${order.status}`"
          >
            {{ statusText }}
          </div>
        </header>

        <section class="card">

          <h2>Статус заказа</h2>

          <div class="steps">

            <div
                v-for="(step, index) in steps"
                :key="step.title"
                class="step"
                :class="{
                                'step--done': step.done,
                            }"
            >
              <div class="step__icon">
                {{ step.done ? '✓' : index + 1 }}
              </div>

              <div class="step__title">
                {{ step.title }}
              </div>
            </div>

          </div>

        </section>

        <section class="card">

          <h2>Товар</h2>

          <div
              v-for="item in order.items"
              :key="item.sku"
              class="product"
          >
            <div>
              <div class="product__name">
                {{ item.name }}
              </div>

              <div class="product__sku">
                {{ item.sku }}
              </div>
            </div>

            <div class="product__price">
              {{ item.price }} {{ item.currency }}
            </div>
          </div>

          <div class="total-details">
            <div class="total-details__row">
              <span>Стоимость товара</span>

              <span>
                {{ originalAmount }} {{ order.currency }}
              </span>
            </div>

            <div
                v-if="discount > 0"
                class="total-details__row total-details__row--discount"
            >
              <span>Скидка</span>

              <span> −{{ discount }} {{ order.currency }} </span>
            </div>

            <div class="total-details__row total-details__row--total">
              <span>Итого</span>

              <strong>
                {{ order.amount }} {{ order.currency }}
              </strong>
            </div>
          </div>

        </section>

        <section
            v-if="order.status === 'created'"
            class="card payment-card"
        >
          <h2>Оплата заказа</h2>

          <p>
            После оплаты товар будет выдан автоматически.
          </p>

          <button
              class="action-button"
              type="button"
              :disabled="paying"
              @click="payOrder"
          >
            {{ paying ? 'Оплата...' : `Оплатить ${order.amount} ${order.currency}` }}
          </button>
        </section>

        <section
            v-if="order.keys.length"
            class="card key-card"
        >
          <div class="key-card__header">
            <div>
              <h2>Ваш ключ</h2>

              <p>
                Скопируйте ключ и активируйте его
                в соответствующем сервисе.
              </p>
            </div>

            <span class="key-card__check">
                            ✓
                        </span>
          </div>

          <div
              v-for="key in order.keys"
              :key="key.code"
              class="key"
          >
            <code>{{ key.code }}</code>

            <button
                type="button"
                @click="navigator.clipboard.writeText(key.code)"
            >
              Копировать
            </button>
          </div>
        </section>

        <section
            v-else-if="order.status === 'out_of_stock'"
            class="card card--warning"
        >
          <h2>Товар временно закончился</h2>

          <p>
            Оплата получена, но сейчас нет доступного
            ключа. Заказ можно будет выдать после
            пополнения товара.
          </p>

          <button
              class="action-button"
              type="button"
              :disabled="retrying"
              @click="retryDelivery"
          >
            {{ retrying ? 'Повторяем...' : 'Повторить выдачу' }}
          </button>
        </section>

        <section
            v-else-if="order.status === 'delivery_failed'"
            class="card card--error"
        >
          <h2>Не удалось выдать товар</h2>

          <p>
            Оплата получена. Мы сможем повторить
            выдачу после устранения проблемы.
          </p>
        </section>

      </template>

    </div>
  </div>
</template>

<style scoped>
.order-page {
  min-height: 100vh;
  padding: 40px 20px 80px;
  background: #f6f6f6;
}

.order-page__container {
  width: min(760px, 100%);
  margin: 0 auto;
}

.back-link {
  display: inline-block;
  margin-bottom: 24px;
  color: #777;
  font-size: 14px;
  text-decoration: none;
}

.back-link:hover {
  color: #111;
}

.state {
  padding: 60px;
  background: #fff;
  border-radius: 16px;
  text-align: center;
}

.state--error {
  color: #b42318;
}

.order-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 20px;
}

.order-header__label {
  margin-bottom: 4px;
  color: #999;
  font-size: 14px;
}

.order-header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.status {
  padding: 9px 13px;
  border-radius: 9px;
  background: #eee;
  font-size: 14px;
  white-space: nowrap;
}

.status--paid,
.status--delivering {
  background: #fff1c7;
}

.status--delivered {
  background: #d9f7e7;
}

.status--payment_failed,
.status--delivery_failed {
  background: #ffe1e1;
}

.status--out_of_stock {
  background: #fff1c7;
}

.card {
  margin-bottom: 16px;
  padding: 24px;
  background: #fff;
  border-radius: 16px;
}

.card h2 {
  margin: 0 0 20px;
  font-size: 18px;
}

.steps {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.step {
  position: relative;
  color: #aaa;
  text-align: center;
}

.step__icon {
  width: 34px;
  height: 34px;
  margin: 0 auto 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #eee;
  font-size: 14px;
}

.step--done {
  color: #111;
}

.step--done .step__icon {
  background: #111;
  color: #fff;
}

.step__title {
  font-size: 12px;
}

.product {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 16px 0;
  border-bottom: 1px solid #eee;
}

.product__name {
  font-weight: 600;
}

.product__sku {
  margin-top: 5px;
  color: #999;
  font-size: 13px;
}

.product__price {
  white-space: nowrap;
}

.total {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
  font-size: 18px;
}

.key-card {
  border: 1px solid #d9f7e7;
}

.key-card__header {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.key-card__header p {
  margin: -12px 0 20px;
  color: #777;
  font-size: 14px;
}

.key-card__check {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 50%;
  background: #d9f7e7;
}

.key {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px;
  background: #f5f5f5;
  border-radius: 10px;
}

.key code {
  font-family: monospace;
  font-size: 15px;
  word-break: break-all;
}

.key button {
  padding: 8px 12px;
  border: 0;
  border-radius: 8px;
  background: #111;
  color: #fff;
  cursor: pointer;
}

.card--warning {
  background: #fff9e8;
}

.card--error {
  background: #fff0f0;
}

@media (max-width: 600px) {
  .order-header {
    flex-direction: column;
  }

  .steps {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }

  .product {
    align-items: flex-start;
    flex-direction: column;
    gap: 8px;
  }

  .key {
    align-items: stretch;
    flex-direction: column;
  }
}

.action-button {
  width: 100%;
  margin-top: 16px;
  padding: 13px 18px;
  border: 0;
  border-radius: 10px;
  background: #111;
  color: #fff;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

.action-button:hover {
  background: #222;
}

.action-button:disabled {
  opacity: .5;
  cursor: default;
}

.payment-card p {
  margin: -10px 0 0;
  color: #777;
  font-size: 14px;
}

.total-details {
  margin-top: 20px;
  padding-top: 14px;
  border-top: 1px solid #eee;
}

.total-details__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;

  padding: 5px 0;

  color: #777;
  font-size: 14px;
}

.total-details__row--discount {
  color: #16834b;
}

.total-details__row--total {
  margin-top: 8px;
  padding-top: 12px;

  color: #111;

  border-top: 1px solid #eee;

  font-size: 18px;
}
</style>