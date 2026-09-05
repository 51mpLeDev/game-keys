<script setup lang="ts">
import ProductCard from './ProductCard.vue'

import {
  popularProducts,
  recommendedProducts,
  otherProducts,
  type Product,
} from '../data/products'

const categories = [
  'Донат',
  'Подписки',
  'Предметы',
  'Аккаунты',
  'Ключи',
  'Игровая валюта',
  'Другое',
]

const API_URL = 'http://localhost:8080/api'

async function handleBuy(product: Product) {
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
      }),
    })

    if (!response.ok) {
      const error = await response.text()

      console.error('Order creation failed:', error)

      return
    }

    const result = await response.json()

    console.log('Order created:', result)

    window.location.href = `/orders/${result.data.id}`
  } catch (error) {
    console.error('Order creation failed:', error)
  }
}
</script>

<template>
  <section class="products">

    <!-- Popular -->
    <div class="product-section">
      <div class="product-section__header product-section__header--popular">
        <h2 class="product-section__title">
          Популярные товары
        </h2>

        <div class="categories">
          <button
              v-for="(category, index) in categories"
              :key="category"
              type="button"
              class="category"
              :class="{
              'category--active': index === 0,
            }"
          >
            <span
                v-if="index === 0"
                class="category__icon"
            >
              ◈
            </span>

            {{ category }}
          </button>
        </div>
      </div>

      <div class="products__grid">
        <ProductCard
            v-for="product in popularProducts"
            :key="product.sku"
            :product="product"
            @buy="handleBuy"
        />
      </div>
    </div>

    <!-- Recommended -->
    <div class="product-section">
      <div class="product-section__header">
        <h2 class="product-section__title">
          Рекомендованные товары
        </h2>

        <button
            class="show-all"
            type="button"
        >
          Показать все
        </button>
      </div>

      <div class="products__grid">
        <ProductCard
            v-for="product in recommendedProducts"
            :key="product.sku"
            :product="product"
            @buy="handleBuy"
        />
      </div>
    </div>

    <!-- Other -->
    <div class="product-section">
      <div class="product-section__header">
        <h2 class="product-section__title">
          Другие товары
        </h2>

        <button
            class="show-all"
            type="button"
        >
          Показать все
        </button>
      </div>

      <div class="products__grid">
        <ProductCard
            v-for="product in otherProducts"
            :key="product.sku"
            :product="product"
            @buy="handleBuy"
        />
      </div>
    </div>

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

  font-size: 8px;
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
</style>