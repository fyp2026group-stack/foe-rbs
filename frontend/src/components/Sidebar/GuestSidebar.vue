<template>
  <aside class="sidebar">
    <div class="brand-section">
      <div class="logo-box">
        <img src="/logo.png" alt="FOE RBS" class="logo-img fixed-logo" />
      </div>
      <div class="brand-info">
        <h1 class="brand-name">FOE RBS</h1>
        <p class="brand-role">Public Portal</p>
      </div>
    </div>

    <nav class="nav-container">
      <div class="nav-group">
        <span class="group-label">Explore</span>
        
        <router-link to="/guest-resources" class="nav-link" :class="{ active: isActive('/guest-resources') }">
          <div class="icon-box"><i class="bi bi-shop"></i></div>
          <span>Resource Gallery</span>
        </router-link>
      </div>

      <div class="nav-group border-top pt-3 mt-2">
        <span class="group-label">Tracking</span>
        <router-link to="/public-bookings" class="nav-link" :class="{ active: isActive('/public-bookings') }">
          <div class="icon-box"><i class="bi bi-search"></i></div>
          <span>Booking Lookup</span>
        </router-link>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-pill d-flex flex-column align-items-center text-center">
        <div class="avatar mb-2 bg-secondary">G</div>
        <div class="user-meta">
          <span class="u-name">Guest Visior</span>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';

const route = useRoute();

const isActive = (path: string): boolean => {
  return route.path === path;
};
</script>

<style scoped>
.sidebar {
  width: 260px;
  position: fixed;
  top: 60px; /* Offset for navbar */
  left: 0;
  height: calc(100vh - 60px);
  background-color: #fcfdfe;
  border-right: 1.5px solid #e2e8f0;
  box-shadow: 4px 0 15px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  z-index: 1000;
}

.brand-section {
  padding: 30px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-box {
  background: #ffffff;
  padding: 8px;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 48px;
  min-height: 48px;
}

.fixed-logo {
  width: 32px;
  height: 32px;
  object-fit: contain;
  display: block;
}

.brand-name {
  font-size: 1.1rem;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
}

.brand-role {
  font-size: 0.75rem;
  color: #1e4449;
  font-weight: 600;
  margin: 0;
  text-transform: uppercase;
}

.nav-container {
  flex: 1;
  padding: 0 15px;
  overflow-y: auto;
}

.nav-group { margin-bottom: 25px; }

.group-label {
  display: block;
  font-size: 0.7rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  margin-bottom: 10px;
  padding-left: 10px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 15px;
  text-decoration: none;
  color: #64748b;
  font-weight: 500;
  border-radius: 10px;
  transition: all 0.2s ease;
  margin-bottom: 5px;
}

.icon-box {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #f1f5f9;
  color: #64748b;
}

.nav-link:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.nav-link.active {
  background: #e5f4de;
  color: #1e4449;
}

.nav-link.active .icon-box {
  background: #1e4449;
  color: white;
  box-shadow: 0 4px 10px rgba(30, 68, 73, 0.3);
}

.sidebar-footer {
  padding: 20px;
  border-top: 1px solid #e2e8f0;
}

.user-pill {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 10px;
  border-radius: 12px;
  gap: 10px;
}

.avatar {
  width: 35px;
  height: 35px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.8rem;
}

.u-name { font-size: 0.85rem; font-weight: 700; display: block; color: #1e293b;}
.u-status { font-size: 0.65rem; display: block; font-weight: 600;}

@media (max-width: 768px) {
  .sidebar { width: 85px; }
  .brand-info, .group-label, .nav-link span, .user-meta { display: none; }
}
</style>
