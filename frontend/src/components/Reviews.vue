<script setup lang="ts">
interface Review {
  id: number
  avatar: string
  name: string
  rating: string
  date: string
  text: string
  productImage: string
  productName: string
  productPrice: number
}

const reviews: Review[] = [
  {
    id: 1,
    avatar: 'assets/img.png',
    name: 'Bizidin',
    rating: '5.0',
    date: 'Сегодня в 11:48',
    text: 'Отзывчивый и приятный продавец, помог не только с товаром, но и с другим вопросом. Рекомендую!',
    productImage: 'assets/img.png',
    productName: 'FunTime | Полностью готовый сервер под ключ 🔑',
    productPrice: 139,
  },
  {
    id: 2,
    avatar: 'assets/img.png',
    name: 'Bizidin',
    rating: '5.0',
    date: 'Сегодня в 11:48',
    text: 'Отзывчивый и приятный продавец, помог не только с товаром, но и с другим вопросом. Рекомендую!',
    productImage: 'assets/img.png',
    productName: 'FunTime | Полностью готовый сервер под ключ 🔑',
    productPrice: 139,
  },
  {
    id: 3,
    avatar: 'assets/img.png',
    name: 'Bizidin',
    rating: '5.0',
    date: 'Сегодня в 11:48',
    text: 'Отзывчивый и приятный продавец, помог не только с товаром, но и с другим вопросом. Рекомендую!',
    productImage: 'assets/img.png',
    productName: 'FunTime | Полностью готовый сервер под ключ 🔑',
    productPrice: 139,
  },
]
</script>

<template>
  <section class="reviews">
    <div class="reviews__header">
      <div>
        <h2 class="reviews__title">
          Последние отзывы
        </h2>

        <p class="reviews__subtitle">
          Все отзывы взяты с независимой площадки
        </p>
      </div>

      <button
          class="reviews__all"
          type="button"
      >
        Показать все
      </button>
    </div>

    <div class="reviews__grid">
      <article
          v-for="review in reviews"
          :key="review.id"
          class="review-card"
      >
        <!-- User -->
        <div class="review-card__user">
          <img
              class="review-card__avatar"
              :src="`/src/${review.avatar}`"
              :alt="review.name"
          />

          <div class="review-card__user-info">
            <div class="review-card__name">
              {{ review.name }}
            </div>

            <div class="review-card__rating">
              ★★★★★
              <span>{{ review.rating }}</span>
            </div>
          </div>

          <time class="review-card__date">
            {{ review.date }}
          </time>
        </div>

        <!-- Text -->
        <div class="review-card__text">
          {{ review.text }}
        </div>

        <!-- Product -->
        <div class="review-card__product">
          <img
              class="review-card__product-image"
              :src="`/src/${review.productImage}`"
              alt=""
          />

          <span class="review-card__product-name">
            {{ review.productName }}
          </span>

          <span class="review-card__product-price">
            {{ review.productPrice }}₽
          </span>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.reviews {
  width: 100%;

  margin-top: 28px;
  margin-bottom: 18px;
}

.reviews__header {
  min-height: 38px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 10px;
}

.reviews__title {
  margin: 0;

  color: #263242;

  font-size: 14px;
  line-height: 18px;
  font-weight: 800;
}

.reviews__subtitle {
  margin: 2px 0 0;

  color: #a0a6ad;

  font-size: 8px;
  line-height: 11px;
}

.reviews__all {
  height: 25px;

  padding: 0 12px;

  border: 0;
  border-radius: 7px;

  background: #f1f3f6;
  color: #4e5866;

  font-size: 8px;
  font-weight: 700;
}

.reviews__all:hover {
  background: #e8eaed;
}

/* Cards */

.reviews__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.review-card {
  min-width: 0;

  padding: 12px;

  border-radius: 14px;

  background: #ffffff;

  box-shadow:
      0 5px 18px rgba(0, 0, 0, 0.06);
}

/* User */

.review-card__user {
  display: flex;
  align-items: center;

  min-width: 0;
}

.review-card__avatar {
  width: 29px;
  height: 29px;

  flex-shrink: 0;

  border-radius: 50%;

  object-fit: cover;
}

.review-card__user-info {
  min-width: 0;

  margin-left: 8px;
}

.review-card__name {
  color: #1d2733;

  font-size: 10px;
  line-height: 12px;
  font-weight: 800;
}

.review-card__rating {
  margin-top: 1px;

  color: #171717;

  font-size: 8px;
  line-height: 10px;
  letter-spacing: 0.5px;
}

.review-card__rating span {
  margin-left: 4px;

  letter-spacing: 0;

  font-weight: 700;
}

.review-card__date {
  margin-left: auto;

  color: #a2a9b1;

  font-size: 7px;
  line-height: 10px;

  white-space: nowrap;
}

/* Text */

.review-card__text {
  min-height: 55px;

  margin-top: 9px;
  padding: 10px;

  border-radius: 9px;

  background: #f1f3f6;

  color: #7e8792;

  font-size: 8px;
  line-height: 12px;
}

/* Product */

.review-card__product {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 7px;

  margin-top: 9px;
}

.review-card__product-image {
  width: 38px;
  height: 30px;

  flex-shrink: 0;

  border-radius: 7px;

  object-fit: cover;
}

.review-card__product-name {
  min-width: 0;

  overflow: hidden;

  color: #202a36;

  font-size: 8px;
  line-height: 10px;
  font-weight: 700;

  text-overflow: ellipsis;
}

.review-card__product-price {
  flex-shrink: 0;

  margin-left: auto;

  padding: 5px 8px;

  border-radius: 6px;

  background: #050505;
  color: #ffffff;

  font-size: 8px;
  line-height: 1;
  font-weight: 700;
}
</style>