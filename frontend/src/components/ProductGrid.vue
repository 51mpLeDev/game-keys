<script setup lang="ts">
import {computed, onMounted, onUnmounted, ref, watch} from 'vue'
import echo from '../echo'
import ProductCard from './ProductCard.vue'

import type {Product} from '../data/products'

const categories = [
  {label: 'Донат', type: 'topup'},
  {label: 'Подписки', type: 'subscription'},
  {label: 'Предметы', type: 'item'},
  {label: 'Аккаунты', type: 'account'},
  {label: 'Ключи', type: 'key'},
  {label: 'Игровая валюта', type: 'currency'},
  {label: 'Другое', type: 'other'},
]

const API_URL = import.meta.env.VITE_API_URL

const search = ref('')
const type = ref('')
const minPrice = ref('')
const maxPrice = ref('')

const promoCode = ref('')
const promoError = ref('')
const buyingSku = ref<string | null>(null)

let requestController: AbortController | null = null
let requestId = 0

type ServerProduct = Product & {
  id: number
}

const serverProducts = ref<Record<string, ServerProduct>>({})

const hasFilters = computed(() =>
    search.value.trim() !== '' ||
    type.value !== '' ||
    minPrice.value !== '' ||
    maxPrice.value !== '',
)

const productsWithState = computed(() =>
    Object.values(serverProducts.value),
)

function selectCategory(categoryType: string) {
  type.value = type.value === categoryType ? '' : categoryType
}

function readFiltersFromUrl() {
  const params = new URLSearchParams(window.location.search)

  search.value = params.get('search') ?? ''
  type.value = params.get('type') ?? ''
  minPrice.value = params.get('min_price') ?? ''
  maxPrice.value = params.get('max_price') ?? ''
}

function updateUrl() {
  const params = new URLSearchParams()

  if (search.value.trim()) {
    params.set('search', search.value.trim())
  }

  if (type.value) {
    params.set('type', type.value)
  }

  if (minPrice.value !== '') {
    params.set('min_price', minPrice.value)
  }

  if (maxPrice.value !== '') {
    params.set('max_price', maxPrice.value)
  }

  const query = params.toString()

  window.history.replaceState(
      {},
      '',
      query
          ? `${window.location.pathname}?${query}`
          : window.location.pathname,
  )
}

async function loadProducts() {
  const currentRequestId = ++requestId

  requestController?.abort()
  requestController = new AbortController()

  const params = new URLSearchParams()

  if (search.value.trim()) {
    params.set('search', search.value.trim())
  }

  if (type.value) {
    params.set('type', type.value)
  }

  if (minPrice.value !== '') {
    params.set('min_price', minPrice.value)
  }

  if (maxPrice.value !== '') {
    params.set('max_price', maxPrice.value)
  }

  const query = params.toString()

  const response = await fetch(
      `${API_URL}/products${query ? `?${query}` : ''}`,
      {
        signal: requestController.signal,
      },
  )

  if (!response.ok) {
    throw new Error('Failed to load products')
  }

  const result = await response.json()

  if (currentRequestId !== requestId) {
    return
  }

  serverProducts.value = Object.fromEntries(
      result.data.map((product: ServerProduct) => [
        product.sku,
        product,
      ]),
  )
}

function handleStockUpdate(event: {
  product_id: number
  stock: number
}) {
  const product = Object.values(serverProducts.value)
      .find(item => item.id === event.product_id)

  if (!product) {
    return
  }

  product.stock = event.stock

  console.log('[Stock] updated:', {
    sku: product.sku,
    stock: product.stock,
  })
}

function handlePriceUpdate(event: {
  product_id: number
  price: number
  currency: 'RUB'
}) {
  const product = Object.values(serverProducts.value)
      .find(item => item.id === event.product_id)

  if (!product) {
    return
  }

  product.price = event.price
  product.currency = event.currency

  console.log('[Price] updated:', {
    sku: product.sku,
    price: product.price,
    currency: product.currency,
  })
}

async function handleBuy(product: Product) {
  if (buyingSku.value === product.sku) {
    return
  }

  buyingSku.value = product.sku
  promoError.value = ''

  const orderId = crypto.randomUUID()

  try {
    const response = await fetch(`${API_URL}/orders`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        sku: product.sku,
        quantity: 1,
        order_id: orderId,
        promo_code: promoCode.value.trim() || null,
      }),
    })

    const result = await response.json()

    if (!response.ok) {
      promoError.value =
          result.message || 'Не удалось создать заказ.'

      return
    }

    console.log('Order created:', result)

    window.location.href = `/orders/${result.data.id}`
  } catch (error) {
    console.error('Order creation failed:', error)

    promoError.value = 'Не удалось соединиться с сервером.'
  } finally {
    buyingSku.value = null
  }
}

function handleHeaderSearch(event: Event) {
  const customEvent = event as CustomEvent<string>

  search.value = customEvent.detail ?? ''
}

watch(
    [search, type, minPrice, maxPrice],
    () => {
      console.log('[Filters]', {
        search: search.value,
        type: type.value,
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
      })

      updateUrl()

      loadProducts().catch(error => {
        if (error?.name !== 'AbortError') {
          console.error('Failed to load products:', error)
        }
      })
    },
)

onMounted(() => {
  readFiltersFromUrl()

  window.addEventListener(
      'catalog-search-changed',
      handleHeaderSearch
  )


  loadProducts().catch(error => {
    if (error?.name !== 'AbortError') {
      console.error('Failed to load products:', error)
    }
  })

  echo
      .channel('products')
      .listen('.product.stock.updated', handleStockUpdate)
      .listen('.product.price.updated', handlePriceUpdate)
})

onUnmounted(() => {
  requestController?.abort()

  window.removeEventListener(
      'catalog-search-changed',
      handleHeaderSearch
  )

  echo.leaveChannel('products')
})
</script>

<template>
  <section class="products">

    <!-- Promo -->
    <div class="promo">
      <div class="promo__content">
        <div class="promo__title">
          Промокод
        </div>

        <div class="promo__form">
          <input
              v-model="promoCode"
              type="text"
              class="promo__input"
              placeholder="Введите промокод"
              maxlength="50"
              @input="promoError = ''"
          />

          <span
              v-if="promoCode"
              class="promo__hint"
          >
            Применится при покупке
          </span>
        </div>
      </div>

      <div
          v-if="promoError"
          class="promo__error"
      >
        {{ promoError }}
      </div>
    </div>


    <!-- Search results -->
    <div
        v-if="hasFilters"
        class="product-section"
    >
      <div class="product-section__header">
        <h2 class="product-section__title">
          Результаты поиска
        </h2>

        <span class="results-count">
          {{ productsWithState.length }} товаров
        </span>
      </div>

      <div
          v-if="productsWithState.length"
          class="products__grid"
      >
        <ProductCard
            v-for="product in productsWithState"
            :key="product.sku"
            :product="product"
            @buy="handleBuy"
        />
      </div>

      <div
          v-else
          class="empty-results"
      >
        Ничего не найдено
      </div>
    </div>

    <!-- Normal catalog -->
    <template v-else>

      <!-- Popular -->
      <div class="product-section">
        <div class="product-section__header product-section__header--popular">
          <h2 class="product-section__title">
            Популярные товары
          </h2>

          <div class="categories">
            <button
                v-for="category in categories"
                :key="category.label"
                type="button"
                class="category"
                :class="{
            'category--active': type === category.type,
        }"
                @click="selectCategory(category.type)"
            >
        <span
            v-if="category.type === 'topup'"
            class="category__icon"
        >
            ◈
        </span>

              {{ category.label }}
            </button>
          </div>
        </div>

        <div class="products__grid">
          <ProductCard
              v-for="product in productsWithState.slice(0, 5)"
              :key="product.sku"
              :product="product"
              @buy="handleBuy"
          />
        </div>
      </div>

      <!-- Remaining products -->
      <div
          v-if="productsWithState.length > 5"
          class="product-section"
      >
        <div class="product-section__header">
          <h2 class="product-section__title">
            Другие товары
          </h2>
        </div>

        <div class="products__grid">
          <ProductCard
              v-for="product in productsWithState.slice(5)"
              :key="product.sku"
              :product="product"
              @buy="handleBuy"
          />
        </div>
      </div>

    </template>

  </section>
</template>

<style scoped>
.products {
  margin-top: 12px;
  margin-bottom: 40px;
}

.product-section {
  margin-bottom: 20px;
}

.product-section:last-child {
  margin-bottom: 0;
}

/* Header */

.product-section__header {
  min-height: 29px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;

  margin-bottom: 7px;
}

.product-section__header--popular {
  align-items: center;
}

.product-section__title {
  flex-shrink: 0;

  margin: 0;

  color: #263242;

  font-size: 14px;
  line-height: 20px;
  font-weight: 800;
}

/* Categories */

.categories {
  min-width: 0;

  display: flex;
  align-items: center;
  justify-content: flex-end;

  gap: 4px;
}

.category {
  height: 25px;

  display: flex;
  align-items: center;
  gap: 4px;

  padding: 0 9px;

  border: 0;
  border-radius: 7px;

  background: #f1f3f6;
  color: #a0a8b3;

  font-size: 9px;
  line-height: 1;
  font-weight: 700;

  white-space: nowrap;

  transition: background-color 0.15s ease,
  color 0.15s ease;
}

.category:hover {
  background: #e7e9ed;
  color: #56606d;
}

.category--active {
  background: #050505;
  color: #ffffff;
}

.category--active:hover {
  background: #050505;
  color: #ffffff;
}

.category__icon {
  font-size: 8px;
}

/* Show all */

.show-all {
  height: 27px;

  padding: 0 12px;

  border: 0;
  border-radius: 7px;

  background: #f1f3f6;
  color: #4e5866;

  font-size: 8px;
  line-height: 1;
  font-weight: 700;
}

.show-all:hover {
  background: #e7e9ed;
}

/* Grid */

.products__grid {
  display: grid;

  grid-template-columns:
    repeat(5, minmax(0, 1fr));

  gap: 9px;
}

/* Responsive */

@media (max-width: 1000px) {
  .products__grid {
    grid-template-columns:
      repeat(4, minmax(0, 1fr));
  }

  .categories {
    overflow-x: auto;
    justify-content: flex-start;
  }
}

@media (max-width: 750px) {
  .products__grid {
    grid-template-columns:
      repeat(3, minmax(0, 1fr));
  }

  .product-section__header--popular {
    align-items: flex-start;
    flex-direction: column;
  }

  .categories {
    width: 100%;
  }
}

@media (max-width: 520px) {
  .products__grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }
}

/* Promo */

.promo {
  margin-bottom: 14px;
  padding: 10px 12px;

  border-radius: 9px;

  background: #f7f8fa;
  border: 1px solid #eef0f3;
}

.promo__content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.promo__title {
  flex-shrink: 0;

  color: #263242;

  font-size: 10px;
  line-height: 14px;
  font-weight: 800;
}

.promo__form {
  min-width: 0;

  display: flex;
  align-items: center;
  gap: 8px;

  flex: 1;
}

.promo__input {
  width: 180px;
  height: 27px;

  padding: 0 10px;

  border: 1px solid #e2e5e9;
  border-radius: 7px;

  outline: none;

  background: #ffffff;
  color: #263242;

  font-size: 9px;
  font-weight: 600;

  transition: border-color 0.15s ease,
  box-shadow 0.15s ease;
}

.promo__input::placeholder {
  color: #a0a8b3;
}

.promo__input:focus {
  border-color: #b9c0c9;
  box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.04);
}

.promo__hint {
  color: #9aa2ad;

  font-size: 8px;
  line-height: 12px;
  font-weight: 600;
}

.promo__error {
  margin-top: 6px;

  color: #d34b4b;

  font-size: 8px;
  line-height: 12px;
  font-weight: 700;
}

/* Mobile */

@media (max-width: 520px) {
  .promo__content {
    align-items: flex-start;
    flex-direction: column;
    gap: 7px;
  }

  .promo__form {
    width: 100%;
  }

  .promo__input {
    width: 100%;
  }

  .promo__hint {
    display: none;
  }
}
</style>