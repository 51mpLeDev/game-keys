<script setup lang="ts">
import {computed, ref} from 'vue'

import Steam from './svg/Steam.vue'

const currencies = ['$', '₸', '₽'] as const

const activeCurrency = ref<(typeof currencies)[number]>('$')
const login = ref('')
const amount = ref(500)

const formattedAmount = computed(() => {
  const value = Number(amount.value)

  if (!Number.isFinite(value) || value < 0) {
    return 0
  }

  return value
})

function selectCurrency(currency: (typeof currencies)[number]) {
  activeCurrency.value = currency
}

function submit() {
  console.log({
    login: login.value,
    amount: formattedAmount.value,
    currency: activeCurrency.value,
  })
}
</script>

<template>
  <section class="steam-topup">
    <!-- Steam logo + title -->
    <div class="steam-topup__brand">
      <div class="steam-topup__logo">
        <Steam/>
      </div>

      <div class="steam-topup__info">
        <div class="steam-topup__title">
          <span>Пополнение Steam</span>

          <span class="steam-topup__discount">
            5%
          </span>
        </div>

        <button
            class="steam-topup__promo"
            type="button"
        >
          Ввести промокод
          <span>⌄</span>
        </button>
      </div>
    </div>

    <!-- Login -->
    <div class="steam-topup__field">
      <div class="steam-topup__input steam-topup__input--login">
        <span class="steam-topup__field-icon">
          ♟
        </span>

        <input
            v-model="login"
            type="text"
            placeholder="Логин Steam"
        />

        <span class="steam-topup__info-icon">
          i
        </span>
      </div>
    </div>

    <!-- Amount -->
    <div class="steam-topup__field">
      <div class="steam-topup__input steam-topup__input--amount">
        <span class="steam-topup__field-icon">
          ₽
        </span>

        <div class="steam-topup__amount-content">
          <span class="steam-topup__amount-label">
            Сумма
          </span>

          <input
              v-model="amount"
              type="number"
              min="1"
          />
        </div>

        <div class="currency-switcher">
          <button
              v-for="currency in currencies"
              :key="currency"
              type="button"
              class="currency-switcher__button"
              :class="{
              'currency-switcher__button--active':
                activeCurrency === currency,
            }"
              @click="selectCurrency(currency)"
          >
            {{ currency }}
          </button>
        </div>
      </div>
    </div>

    <!-- Pay -->
    <button
        class="steam-topup__button"
        type="button"
        @click="submit"
    >
      Оплатить {{ formattedAmount }}{{ activeCurrency }}
    </button>
  </section>
</template>

<style scoped>
.steam-topup {
  width: 100%;
  height: 68px;

  display: grid;

  /*
   * Лого + название
   * Логин
   * Сумма
   * Кнопка
   */
  grid-template-columns:
    235px
    minmax(170px, 1fr)
    minmax(220px, 1.25fr)
    auto;

  align-items: center;

  gap: 12px;

  margin-top: 10px;
  padding: 7px 10px;

  border: 1px solid #e5e5e5;
  border-radius: 10px;

  background: #ffffff;

  box-shadow: 0 5px 18px rgba(0, 0, 0, 0.04);
}

/*
|--------------------------------------------------------------------------
| Brand
|--------------------------------------------------------------------------
*/

.steam-topup__brand {
  height: 52px;

  display: flex;
  align-items: center;

  gap: 8px;

  min-width: 0;
}

.steam-topup__logo {
  width: 52px;
  height: 52px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  overflow: hidden;

  border-radius: 9px;

  background: #ffffff;
}

.steam-topup__logo :deep(svg) {
  display: block;

  width: 52px;
  height: 52px;
}

.steam-topup__info {
  min-width: 0;

  display: flex;
  flex-direction: column;

  justify-content: center;
}

.steam-topup__title {
  display: flex;
  align-items: center;

  gap: 6px;

  color: #202020;

  font-size: 11px;
  line-height: 14px;
  font-weight: 800;

  white-space: nowrap;
}

.steam-topup__discount {
  height: 14px;

  display: inline-flex;
  align-items: center;

  padding: 0 5px;

  border-radius: 4px;

  background: #dff3d9;
  color: #4a9d3c;

  font-size: 7px;
  line-height: 1;
  font-weight: 800;
}

.steam-topup__promo {
  width: max-content;

  display: flex;
  align-items: center;
  gap: 5px;

  height: 20px;

  margin-top: 3px;
  padding: 0 8px;

  border: 0;
  border-radius: 5px;

  background: #eaf2fa;
  color: #252f3b;

  font-size: 7px;
  line-height: 1;
  font-weight: 700;
}

.steam-topup__promo span {
  font-size: 9px;
}

/*
|--------------------------------------------------------------------------
| Fields
|--------------------------------------------------------------------------
*/

.steam-topup__field {
  min-width: 0;
}

.steam-topup__input {
  position: relative;

  width: 100%;
  height: 46px;

  display: flex;
  align-items: center;

  border-radius: 9px;

  background: #f3f5f8;

  overflow: hidden;
}

.steam-topup__input input {
  width: 100%;
  height: 100%;

  min-width: 0;

  border: 0;
  outline: none;

  background: transparent;

  color: #303030;

  font-size: 11px;
  font-weight: 600;
}

.steam-topup__input input::placeholder {
  color: #8792a3;
}

.steam-topup__field-icon {
  width: 34px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  color: #8792a3;

  font-size: 11px;
  font-weight: 800;
}

.steam-topup__info-icon {
  width: 14px;
  height: 14px;

  flex-shrink: 0;

  margin-right: 10px;

  display: grid;
  place-items: center;

  border-radius: 50%;

  background: #dfe4ea;
  color: #8b95a4;

  font-size: 8px;
  line-height: 1;
  font-weight: 800;
}

/*
|--------------------------------------------------------------------------
| Amount
|--------------------------------------------------------------------------
*/

.steam-topup__amount-content {
  min-width: 0;

  height: 100%;

  display: flex;
  flex-direction: column;
  justify-content: center;
}

.steam-topup__amount-label {
  color: #9ba4b1;

  font-size: 7px;
  line-height: 9px;
  font-weight: 600;
}

.steam-topup__amount-content input {
  height: 16px;

  padding: 0;

  font-size: 11px;
  line-height: 16px;
}

/*
|--------------------------------------------------------------------------
| Currency
|--------------------------------------------------------------------------
*/

.currency-switcher {
  height: 34px;

  display: flex;
  align-items: center;

  margin-right: 4px;
  padding: 2px;

  border-radius: 7px;

  background: #e9ecf0;
}

.currency-switcher__button {
  width: 29px;
  height: 30px;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 0;

  border: 0;
  border-radius: 6px;

  background: transparent;
  color: #9aa3ae;

  font-family: Arial, sans-serif;
  font-size: 14px;
  line-height: 1;
  font-weight: 400;

  appearance: none;
  -webkit-appearance: none;
}

.currency-switcher__button--active {
  background: #111111;
  color: #ffffff;

  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);

  font-weight: 400;
}

/*
|--------------------------------------------------------------------------
| Button
|--------------------------------------------------------------------------
*/

.steam-topup__button {
  height: 46px;

  padding: 0 22px;

  border: 0;
  border-radius: 8px;

  background: #050505;
  color: #ffffff;

  font-size: 10px;
  line-height: 1;
  font-weight: 800;

  white-space: nowrap;

  transition: background-color 0.15s ease,
  transform 0.15s ease;
}

.steam-topup__button:hover {
  background: #222222;

  transform: translateY(-1px);
}

/*
|--------------------------------------------------------------------------
| Desktop fitting
|--------------------------------------------------------------------------
*/

@media (max-width: 900px) {
  .steam-topup {
    grid-template-columns: 1fr 1fr;

    height: auto;
  }

  .steam-topup__brand {
    grid-column: 1 / -1;
  }

  .steam-topup__button {
    width: 100%;
  }
}

@media (max-width: 600px) {
  .steam-topup {
    grid-template-columns: 1fr;
  }

  .steam-topup__brand {
    grid-column: auto;
  }
}
</style>