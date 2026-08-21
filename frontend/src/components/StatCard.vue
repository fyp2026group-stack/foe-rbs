<template>
  <div class="stat-card" :style="{ borderLeftColor: color }">
    <div class="stat-icon" :style="{ backgroundColor: color + '20' }">
      <i :class="icon" :style="{ color: color }"></i>
    </div>
    <div class="stat-content">
      <h3 class="stat-value">{{ value }}</h3>
      <p class="stat-label">{{ label }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  icon: string;
  value: string | number;
  label: string;
  color: string;
}

defineProps<Props>();
</script>

<style scoped>
.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-left: 4px solid;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  color: #1e4449;
}

.stat-label {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
}
</style>
