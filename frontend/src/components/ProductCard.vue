<script setup lang="ts">
import type { Product } from '../data/products'

defineProps<{
  product: Product
}>()

const images = import.meta.glob(
    '../assets/**/*.{png,jpg,jpeg,webp}',
    {
      eager: true,
      query: '?url',
      import: 'default',
    }
)

function img(name: string) {
  const path = `../${name}`

  return images[path] as string
}
</script>

<template>
  <article class="product-card">
    <div class="product-card__image-wrapper">
      <img
          class="product-card__image"
          :src="img(product.image)"
          :alt="product.name"
      />
    </div>

    <div class="product-card__body">
      <h3 class="product-card__name">
        {{ product.name }}
      </h3>

      <div class="product-card__prices">
        <span class="product-card__price">
          {{ product.price }} ₽
        </span>

        <span
            v-if="product.oldPrice"
            class="product-card__old-price"
        >
          {{ product.oldPrice }} ₽
        </span>
      </div>

      <button
          class="product-card__button"
          type="button"
          @click="handleBuy(product)"
      >
        Купить
      </button>
    </div>
  </article>
</template>

<style scoped>
.product-card {
  min-width: 0;

  overflow: hidden;

  border-radius: 10px;

  background: #ffffff;

  box-shadow:
      0 4px 14px rgba(0, 0, 0, 0.06);

  transition:
      transform 0.15s ease,
      box-shadow 0.15s ease;
}

.product-card:hover {
  transform: translateY(-2px);

  box-shadow:
      0 8px 22px rgba(0, 0, 0, 0.09);
}

/* Image */

.product-card__image-wrapper {
  width: 100%;

  aspect-ratio: 1.55 / 1;

  overflow: hidden;

  background: #eeeeee;
}

.product-card__image {
  width: 100%;
  height: 100%;

  display: block;

  object-fit: cover;

  transition: transform 0.25s ease;
}

.product-card:hover .product-card__image {
  transform: scale(1.025);
}

/* Body */

.product-card__body {
  padding: 7px 9px 9px;
}

.product-card__name {
  height: 29px;

  overflow: hidden;

  margin: 0;

  color: #171717;

  font-size: 8px;
  line-height: 11px;
  font-weight: 700;

  text-transform: uppercase;
}

/* Prices */

.product-card__prices {
  display: flex;
  align-items: baseline;
  gap: 6px;

  margin-top: 4px;
}

.product-card__price {
  color: #49a532;

  font-size: 15px;
  line-height: 18px;
  font-weight: 800;
}

.product-card__old-price {
  color: #9da3aa;

  font-size: 8px;
  line-height: 12px;

  text-decoration: line-through;
}

/* Button */

.product-card__button {
  width: 100%;
  height: 31px;

  margin-top: 5px;

  border: 0;
  border-radius: 7px;

  background: #050505;
  color: #ffffff;

  font-size: 9px;
  line-height: 1;
  font-weight: 700;

  transition:
      background-color 0.15s ease,
      transform 0.15s ease;
}

.product-card__button:hover {
  background: #222222;

  transform: translateY(-1px);
}
</style>