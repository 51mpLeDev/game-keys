<script setup lang="ts">
const navigation = [
  'Стать продавцом',
  'Бонусы',
  'Поддержка',
  'Гарантии',
  'Отзывы',
]

const legal = [
  'Политика конфиденциальности',
  'Соглашение',
  'Договор-оферта',
]
</script>

<template>
  <footer class="footer">
    <div class="footer__inner">

      <!-- Navigation -->
      <div class="footer__navigation">
        <nav class="footer__links">
          <a
              v-for="item in navigation"
              :key="item"
              href="#"
          >
            {{ item }}
          </a>
        </nav>
      </div>

      <!-- Social + payments -->
      <div class="footer__middle">
        <div class="footer__socials">
          <a
              href="#"
              aria-label="VK"
              class="footer__social footer__social--vk"
          >
            vk
          </a>

          <a
              href="#"
              aria-label="Telegram"
              class="footer__social"
          >
            ↗
          </a>

          <a
              href="#"
              aria-label="TikTok"
              class="footer__social"
          >
            ♪
          </a>

          <a
              href="#"
              aria-label="YouTube"
              class="footer__social"
          >
            ▶
          </a>
        </div>

        <div class="footer__payments">
          <span class="payment payment--visa">
            VISA
          </span>

          <span class="payment payment--mir">
            мир
          </span>

          <span class="payment payment--mastercard">
            <i></i>
            <i></i>
          </span>
        </div>
      </div>

      <!-- Legal -->
      <div class="footer__legal">
        <nav>
          <a
              v-for="item in legal"
              :key="item"
              href="#"
          >
            {{ item }}
          </a>
        </nav>
      </div>

    </div>
  </footer>
</template>

<style scoped>
.footer {
  width: 100%;

  margin-top: 18px;

  background: #ffffff;
  border: 1px solid #e5e5e5;
}

.footer__inner {
  width: min(1160px, calc(100% - 32px));

  margin: 0 auto;
}

/* Navigation */

.footer__navigation {
  min-height: 43px;

  display: flex;
  align-items: center;

  border-bottom: 1px solid #eeeeee;
}

.footer__links {
  display: flex;
  align-items: center;
  gap: 24px;
}

.footer__links a {
  color: #313b48;

  font-size: 9px;
  line-height: 12px;
  font-weight: 700;
}

.footer__links a:hover {
  color: #000000;
}

/* Middle */

.footer__middle {
  min-height: 45px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  border-bottom: 1px solid #eeeeee;
}

.footer__socials {
  display: flex;
  align-items: center;
  gap: 7px;
}

.footer__social {
  width: 24px;
  height: 24px;

  display: grid;
  place-items: center;

  border-radius: 6px;

  background: #f1f3f6;
  color: #4d5866;

  font-size: 9px;
  font-weight: 800;

  transition:
      background-color 0.15s ease,
      color 0.15s ease;
}

.footer__social:hover {
  background: #e7e9ed;
  color: #111111;
}

.footer__social--vk {
  font-size: 7px;
}

/* Payments */

.footer__payments {
  display: flex;
  align-items: center;
  gap: 7px;
}

.payment {
  height: 20px;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 0 7px;

  border: 1px solid #e5e7ea;
  border-radius: 5px;

  background: #ffffff;

  font-size: 7px;
  font-weight: 800;
}

.payment--visa {
  color: #344b9a;
  font-style: italic;
}

.payment--mir {
  color: #32a87a;
}

.payment--mastercard {
  width: 34px;

  position: relative;

  padding: 0;
}

.payment--mastercard i {
  width: 11px;
  height: 11px;

  position: absolute;

  border-radius: 50%;
}

.payment--mastercard i:first-child {
  left: 8px;

  background: #e21d2a;
}

.payment--mastercard i:last-child {
  right: 8px;

  background: #f3a700;
}

/* Legal */

.footer__legal {
  min-height: 38px;

  display: flex;
  align-items: center;
}

.footer__legal nav {
  display: flex;
  align-items: center;
  gap: 25px;
}

.footer__legal a {
  color: #8d96a1;

  font-size: 8px;
  line-height: 11px;

  text-decoration: underline;
  text-decoration-color: #d8dadd;
  text-underline-offset: 2px;
}

.footer__legal a:hover {
  color: #555555;
}
</style>