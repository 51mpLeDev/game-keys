<script setup lang="ts">
import type { Component } from 'vue'

import Steam from './svg/Steam.vue'
import Telegram from './svg/Telegram.vue'
import Roblox from './svg/Roblox.vue'
import BrawlStars from './svg/BrawlStars.vue'
import PUBGMobile from './svg/PUBGMobile.vue'
import AppStore from './svg/AppStore.vue'
import PlayStation from './svg/PlayStation.vue'
import ChatGPT from './svg/ChatGPT.vue'
import TikTok from './svg/TikTok.vue'
import MLBB from './svg/MLBB.vue'
import More from './svg/More.vue'

interface Service {
  name: string
  icon: Component
}

const services: Service[] = [
  { name: 'Steam', icon: Steam },
  { name: 'Telegram', icon: Telegram },
  { name: 'Roblox', icon: Roblox },
  { name: 'Brawl Stars', icon: BrawlStars },
  { name: 'PUBG Mobile', icon: PUBGMobile },
  { name: 'App Store', icon: AppStore },
  { name: 'PlayStation', icon: PlayStation },
  { name: 'ChatGPT', icon: ChatGPT },
  { name: 'TikTok', icon: TikTok },
  { name: 'Mobile Legends', icon: MLBB },
  { name: 'ещё 641', icon: More },
]
</script>

<template>
  <section class="services">
    <button
        v-for="service in services"
        :key="service.name"
        class="service"
        type="button"
    >
      <span class="service__icon">
        <component :is="service.icon" />
      </span>

      <span class="service__name">
        {{ service.name }}
      </span>
    </button>
  </section>
</template>

<style scoped>
.services {
  display: grid;
  grid-template-columns: repeat(11, minmax(0, 1fr));
  gap: 10px;

  margin-top: 14px;
}

.service {
  min-width: 0;
  height: 78px;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 6px;

  padding: 8px;

  border: 1px solid #e5e5e5;
  border-radius: 12px;

  background: #ffffff;

  color: #222222;

  transition:
      border-color 0.15s ease,
      box-shadow 0.15s ease;
}

.service:hover {
  border-color: #d5d5d5;

  box-shadow: 0 5px 16px rgba(0, 0, 0, 0.06);
}

.service__icon {
  width: 36px;
  height: 36px;

  display: flex;
  align-items: center;
  justify-content: center;

  flex-shrink: 0;
}

.service__icon :deep(svg) {
  width: 36px;
  height: 36px;

  display: block;
}

.service__name {
  max-width: 100%;

  overflow: hidden;

  font-size: 10px;
  line-height: 12px;
  font-weight: 600;

  white-space: nowrap;
  text-overflow: ellipsis;

  text-align: center;
}

@media (max-width: 1000px) {
  .services {
    grid-template-columns: repeat(6, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .services {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
</style>