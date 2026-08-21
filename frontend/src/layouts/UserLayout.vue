<template>
  <div class="user-layout">
    <Navbar />
    <UserSidebar />
    <main class="main-content">
      <slot></slot>
    </main>
  </div>
</template>

<script setup lang="ts">
import Navbar from '../components/Navbar.vue';
import UserSidebar from '../components/Sidebar/UserSidebar.vue';
</script>

<style scoped>
.user-layout {
  display: flex;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  /* Sidebar handling logic is somewhat handled by the sidebar CSS and section classes in the child views.
     We just structure the top level here. */
  width: 100%;
}
</style>
