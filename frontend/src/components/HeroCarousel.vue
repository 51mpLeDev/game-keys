<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

interface Slide {
  id: number
  title: string
  subtitle: string
  button: string
  className: string
}

const slides: Slide[] = [
  {
    id: 1,
    title: 'Пополняй баланс Steam',
    subtitle: 'Быстрое пополнение игрового аккаунта',
    button: 'Пополнить',
    className: 'hero-slide--steam',
  },
  {
    id: 2,
    title: 'Игровые ключи',
    subtitle: 'Ключи для популярных игр по выгодным ценам',
    button: 'Смотреть товары',
    className: 'hero-slide--games',
  },
  {
    id: 3,
    title: 'Подписки и подарочные карты',
    subtitle: 'Все необходимое для вашего игрового аккаунта',
    button: 'Перейти в каталог',
    className: 'hero-slide--gift',
  },
]

const currentSlide = ref(0)
let intervalId: ReturnType<typeof setInterval> | null = null

const activeSlide = computed(() => slides[currentSlide.value])

function nextSlide() {
  currentSlide.value = (currentSlide.value + 1) % slides.length
}

function previousSlide() {
  currentSlide.value =
      (currentSlide.value - 1 + slides.length) % slides.length
}

function selectSlide(index: number) {
  currentSlide.value = index
  restartAutoplay()
}

function startAutoplay() {
  intervalId = setInterval(nextSlide, 5000)
}

function stopAutoplay() {
  if (intervalId !== null) {
    clearInterval(intervalId)
    intervalId = null
  }
}

function restartAutoplay() {
  stopAutoplay()
  startAutoplay()
}

onMounted(startAutoplay)
onBeforeUnmount(stopAutoplay)
</script>

<template>
  <section
      class="hero"
      @mouseenter="stopAutoplay"
      @mouseleave="startAutoplay"
  >
    <div
        class="hero__slide"
        :class="activeSlide.className"
        :key="activeSlide.id"
    >
      <div class="hero__content">
        <span class="hero__badge">GAME KEYS</span>

        <h1 class="hero__title">
          {{ activeSlide.title }}
        </h1>

        <p class="hero__subtitle">
          {{ activeSlide.subtitle }}
        </p>

        <button class="hero__button" type="button">
          {{ activeSlide.button }}
        </button>
      </div>

      <div class="hero__decoration">
        <div class="hero__orb hero__orb--one"></div>
        <div class="hero__orb hero__orb--two"></div>
        <div class="hero__grid"></div>
      </div>
    </div>

    <button
        class="hero__arrow hero__arrow--prev"
        type="button"
        aria-label="Предыдущий слайд"
        @click="previousSlide"
    >
      ‹
    </button>

    <button
        class="hero__arrow hero__arrow--next"
        type="button"
        aria-label="Следующий слайд"
        @click="nextSlide"
    >
      ›
    </button>

    <div class="hero__dots">
      <button
          v-for="(slide, index) in slides"
          :key="slide.id"
          class="hero__dot"
          :class="{ 'hero__dot--active': index === currentSlide }"
          type="button"
          :aria-label="`Слайд ${index + 1}`"
          @click="selectSlide(index)"
      />
    </div>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  margin-top: 24px;
  overflow: hidden;
  border-radius: 20px;
}

.hero__slide {
  position: relative;
  min-height: 330px;
  overflow: hidden;
  isolation: isolate;

  display: flex;
  align-items: center;

  padding: 48px 64px;

  background: #171717;
  color: #ffffff;
}

.hero-slide--steam {
  background: linear-gradient(120deg, #101820 0%, #1c3444 100%);
}

.hero-slide--games {
  background: linear-gradient(120deg, #1d1725 0%, #3b2450 100%);
}

.hero-slide--gift {
  background: linear-gradient(120deg, #171b16 0%, #35462b 100%);
}

.hero__content {
  position: relative;
  z-index: 2;
  max-width: 540px;
}

.hero__badge {
  display: inline-flex;
  align-items: center;

  margin-bottom: 16px;
  padding: 6px 10px;

  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 7px;

  background: rgba(255, 255, 255, 0.08);

  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.hero__title {
  margin: 0;

  font-size: 42px;
  line-height: 1.05;
  font-weight: 800;
  letter-spacing: -1.5px;
}

.hero__subtitle {
  max-width: 430px;
  margin: 16px 0 26px;

  color: rgba(255, 255, 255, 0.7);
  font-size: 16px;
  line-height: 1.5;
}

.hero__button {
  height: 44px;
  padding: 0 20px;

  border: 0;
  border-radius: 10px;

  background: #ffffff;
  color: #171717;

  font-size: 14px;
  font-weight: 700;

  transition:
      transform 0.2s ease,
      opacity 0.2s ease;
}

.hero__button:hover {
  transform: translateY(-1px);
  opacity: 0.9;
}

.hero__decoration {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
}

.hero__orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(1px);
}

.hero__orb--one {
  width: 360px;
  height: 360px;

  top: -140px;
  right: 80px;

  background: rgba(255, 255, 255, 0.08);
}

.hero__orb--two {
  width: 240px;
  height: 240px;

  right: -30px;
  bottom: -120px;

  background: rgba(255, 255, 255, 0.05);
}

.hero__grid {
  position: absolute;
  width: 560px;
  height: 560px;

  right: -60px;
  top: -100px;

  opacity: 0.15;

  background-image:
      linear-gradient(rgba(255, 255, 255, 0.3) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255, 255, 255, 0.3) 1px, transparent 1px);
  background-size: 40px 40px;

  transform: perspective(500px) rotateX(55deg) rotateZ(-15deg);
}

.hero__arrow {
  position: absolute;
  top: 50%;
  z-index: 3;

  width: 42px;
  height: 42px;

  display: grid;
  place-items: center;

  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 50%;

  background: rgba(0, 0, 0, 0.2);
  color: #ffffff;

  font-size: 28px;
  line-height: 1;

  transform: translateY(-50%);

  transition:
      background 0.2s ease,
      transform 0.2s ease;
}

.hero__arrow:hover {
  background: rgba(0, 0, 0, 0.4);
  transform: translateY(-50%) scale(1.05);
}

.hero__arrow--prev {
  left: 20px;
}

.hero__arrow--next {
  right: 20px;
}

.hero__dots {
  position: absolute;
  z-index: 3;

  left: 50%;
  bottom: 18px;

  display: flex;
  gap: 7px;

  transform: translateX(-50%);
}

.hero__dot {
  width: 7px;
  height: 7px;

  padding: 0;

  border: 0;
  border-radius: 50%;

  background: rgba(255, 255, 255, 0.35);

  transition:
      width 0.2s ease,
      background 0.2s ease;
}

.hero__dot--active {
  width: 22px;
  border-radius: 5px;
  background: #ffffff;
}
</style>