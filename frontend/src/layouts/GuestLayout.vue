<template>
  <div class="guest-layout">
    <Navbar />
    <GuestSidebar />
    <main class="main-content">
      <slot></slot>
    </main>
  </div>
</template>

<script setup lang="ts">
import Navbar from '../components/Navbar.vue';
import GuestSidebar from '../components/Sidebar/GuestSidebar.vue';
</script>

<style scoped>
.guest-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  padding: 20px;
}
</style>
