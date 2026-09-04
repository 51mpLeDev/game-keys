<script setup lang="ts">
import {onBeforeUnmount, onMounted, ref} from 'vue'

const catalogOpen = ref(false)

const categories = [
  'Игры и игровые сервисы',
  'Игровые ценности',
  'Мобильные игры',
  'Сервисы и соцсети',
  'Программы',
]

const columns = [
  {
    title: 'Steam',
    items: [
      'Игры и DLC',
      'Пополнение баланса',
      'Подарочные карты',
      'Коллекционные карточки',
      'Смена региона',
    ],
  },
  {
    title: 'PlayStation',
    items: [
      'Игры и DLC',
      'Пополнение баланса',
      'Новые аккаунты',
      'PS Plus',
      'EA Play',
    ],
  },
  {
    title: 'Xbox',
    items: [
      'Игры и DLC',
      'Пополнение баланса',
      'Новые аккаунты',
      'Xbox Game Pass',
      'Услуги',
    ],
  },
  {
    title: 'Nintendo',
    items: [
      'Игры и DLC',
      'Подарочные карты',
      'Новые аккаунты',
      'NS Online',
    ],
  },
  {
    title: 'Battle.net',
    items: [
      'Игры и DLC',
      'World of Warcraft',
      'Подарочные карты',
      'Прямое пополнение',
      'Новые аккаунты',
      'Смена региона',
    ],
  },
]

const selections = [
  'Скидки 90%',
  'Популярные издатели',
  'Лучшие серии игр',
  'Steam Deck',
  'Бандл-наборы',
]

function toggleCatalog() {
  catalogOpen.value = !catalogOpen.value
}

function closeCatalog() {
  catalogOpen.value = false
}

function handleDocumentClick(event: MouseEvent) {
  const target = event.target as HTMLElement

  if (!target.closest('.header__catalog')) {
    closeCatalog()
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})
</script>

<template>
  <header class="header">
    <div class="header__inner">

      <!-- Catalog -->
      <div class="header__catalog">
        <button
            class="catalog-button"
            :class="{ 'catalog-button--active': catalogOpen }"
            type="button"
            @click.stop="toggleCatalog"
        >
          <span class="catalog-button__icon">
            ▦
          </span>

          <span>Каталог</span>
        </button>

        <div
            v-if="catalogOpen"
            class="catalog-menu"
            @click.stop
        >
          <!-- Left categories -->
          <aside class="catalog-menu__sidebar">
            <button
                v-for="(category, index) in categories"
                :key="category"
                class="catalog-menu__category"
                :class="{
                'catalog-menu__category--active': index === 0,
              }"
                type="button"
            >
              <span>
                {{ category }}
              </span>

              <span class="catalog-menu__category-arrow">
                ›
              </span>
            </button>
          </aside>

          <!-- Main -->
          <div class="catalog-menu__main">
            <div class="catalog-menu__platforms">
              <div
                  v-for="column in columns"
                  :key="column.title"
                  class="catalog-menu__column"
              >
                <a
                    href="#"
                    class="catalog-menu__platform"
                    @click="closeCatalog"
                >
                  {{ column.title }}

                  <span>›</span>
                </a>

                <a
                    v-for="item in column.items"
                    :key="item"
                    href="#"
                    class="catalog-menu__item"
                    @click="closeCatalog"
                >
                  {{ item }}
                </a>
              </div>
            </div>

            <!-- Collections -->
            <div class="catalog-menu__collections">
              <a
                  href="#"
                  class="catalog-menu__collections-title"
                  @click="closeCatalog"
              >
                Подборки
                <span>›</span>
              </a>

              <div class="catalog-menu__collection-list">
                <a
                    v-for="item in selections"
                    :key="item"
                    href="#"
                    @click="closeCatalog"
                >
                  {{ item }}
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="header__search">
        <input
            type="search"
            placeholder="Игра, приложение или услуга..."
        />

        <button
            class="header__search-favorite"
            type="button"
            aria-label="Избранное"
        >
          ♥
        </button>

        <button
            class="header__search-button"
            type="button"
            aria-label="Поиск"
        >
          ⌕
        </button>
      </div>

      <!-- Profile -->
      <button
          class="header__profile"
          type="button"
          aria-label="Профиль"
      >
        ♟
      </button>

    </div>
  </header>
</template>

<style scoped>
.header {
  position: relative;
  z-index: 50;

  height: 58px;

  background: #ffffff;
  border-bottom: 1px solid #eeeeee;
}

.header__inner {
  width: min(1160px, calc(100% - 32px));
  height: 100%;
  margin: 0 auto;

  display: grid;
  grid-template-columns: 92px minmax(300px, 1fr) 32px;

  align-items: center;

  gap: 16px;
}

/* =========================================================
   CATALOG BUTTON
========================================================= */

.header__catalog {
  position: relative;
}

.catalog-button {
  width: 92px;
  height: 32px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 5px;

  padding: 0;

  border: 0;
  border-radius: 7px;

  background: #050505;
  color: #ffffff;

  font-size: 9px;
  line-height: 1;
  font-weight: 700;
}

.catalog-button__icon {
  font-size: 12px;
  line-height: 1;
}

/* =========================================================
   MEGA MENU
========================================================= */
.catalog-menu {
  position: fixed;

  top: 58px;
  left: 50%;

  width: min(1160px, calc(100vw - 32px));

  transform: translateX(-50%);

  min-height: 330px;

  display: grid;
  grid-template-columns: 155px 1fr;

  overflow: hidden;

  background: #ffffff;

  border: 1px solid #eeeeee;
  border-radius: 0 0 10px 10px;

  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
}

/* =========================================================
   LEFT SIDEBAR
========================================================= */

.catalog-menu__sidebar {
  padding: 8px 0;

  background: #f7f8fa;

  border-right: 1px solid #eeeeee;
}

.catalog-menu__category {
  width: 100%;
  height: 31px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 0 14px;

  border: 0;

  background: transparent;
  color: #4e5865;

  text-align: left;

  font-size: 8px;
  line-height: 1;
  font-weight: 600;
}

.catalog-menu__category:hover,
.catalog-menu__category--active {
  background: #ffffff;
  color: #111111;
}

.catalog-menu__category-arrow {
  color: #a1a7ae;

  font-size: 13px;
}

/* =========================================================
   MAIN MENU
========================================================= */

.catalog-menu__main {
  padding: 15px 16px 20px;
}

.catalog-menu__platforms {
  display: grid;

  grid-template-columns:
    repeat(5, minmax(0, 1fr));

  gap: 15px;
}

.catalog-menu__column {
  min-width: 0;

  display: flex;
  flex-direction: column;

  gap: 9px;
}

.catalog-menu__platform {
  display: flex;
  align-items: center;

  gap: 4px;

  margin-bottom: 1px;

  color: #252d37;

  font-size: 8px;
  line-height: 11px;
  font-weight: 800;
}

.catalog-menu__platform span {
  font-size: 11px;
}

.catalog-menu__item {
  color: #6f7883;

  font-size: 7px;
  line-height: 10px;

  white-space: nowrap;
}

.catalog-menu__item:hover {
  color: #111111;
}

/* =========================================================
   COLLECTIONS
========================================================= */

.catalog-menu__collections {
  width: 120px;

  margin-top: 24px;
}

.catalog-menu__collections-title {
  display: flex;
  align-items: center;

  gap: 4px;

  color: #252d37;

  font-size: 8px;
  line-height: 11px;
  font-weight: 800;
}

.catalog-menu__collections-title span {
  font-size: 11px;
}

.catalog-menu__collection-list {
  display: flex;
  flex-direction: column;

  gap: 8px;

  margin-top: 9px;
}

.catalog-menu__collection-list a {
  color: #6f7883;

  font-size: 7px;
  line-height: 10px;

  white-space: nowrap;
}

.catalog-menu__collection-list a:hover {
  color: #111111;
}

/* =========================================================
   SEARCH
========================================================= */

.header__search {
  position: relative;

  height: 32px;

  display: flex;
  align-items: center;

  overflow: hidden;

  border: 1px solid #111111;
  border-radius: 7px;

  background: #ffffff;
}

.header__search input {
  width: 100%;
  height: 100%;

  padding: 0 68px 0 12px;

  border: 0;
  outline: none;

  background: transparent;

  color: #222222;

  font-size: 9px;
}

.header__search input::placeholder {
  color: #8d96a5;
}

.header__search-favorite {
  position: absolute;

  right: 29px;

  width: 24px;
  height: 24px;

  padding: 0;

  border: 0;
  border-radius: 5px;

  background: #eef1f6;
  color: #778399;

  font-size: 10px;
}

.header__search-button {
  width: 30px;
  height: 100%;

  flex-shrink: 0;

  padding: 0;

  border: 0;

  background: #050505;
  color: #ffffff;

  font-size: 14px;
}

/* =========================================================
   PROFILE
========================================================= */

.header__profile {
  width: 32px;
  height: 32px;

  display: grid;
  place-items: center;

  padding: 0;

  border: 0;
  border-radius: 7px;

  background: #f2f4f7;
  color: #8290a3;

  font-size: 11px;
}
</style>