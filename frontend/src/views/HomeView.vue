<script setup lang="ts">
import Header from '../components/Header.vue'
import HeroCarousel from '../components/HeroCarousel.vue'
import ServiceIcons from '../components/ServiceIcons.vue'
import SteamTopup from '../components/SteamTopup.vue'
import ProductGrid from '../components/ProductGrid.vue'
import Footer from "../components/Footer.vue";
import Reviews from "../components/Reviews.vue";
</script>

<template>
  <div class="page">
    <Header />

    <main class="container">
      <HeroCarousel />

      <ServiceIcons />

      <SteamTopup />

      <ProductGrid />

      <Reviews />
    </main>

    <Footer/>
  </div>
</template>