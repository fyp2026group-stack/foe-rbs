<template>
  <aside class="sidebar">
     <div class="brand-section">
      <div class="logo-box">
        <img :src="systemStore.logo || '/logo.png'" :alt="systemStore.name" class="logo-img fixed-logo" />
      </div>
      <div class="brand-info">
        <h1 class="brand-name">{{ systemStore.name }}</h1>
        <p class="brand-role">Admin</p>
      </div>
    </div>

    <nav class="nav-container">
      <div class="nav-group">
        <span class="group-label">Core</span>
        
        <router-link to="/admin/dashboard" class="nav-link" :class="{ active: isActive('/admin/dashboard') }">
          <div class="icon-box"><i class="bi bi-grid-fill"></i></div>
          <span>Dashboard</span>
        </router-link>

        <router-link v-if="hasPermission('manage_resources')" to="/admin/resource" class="nav-link" :class="{ active: isActive('/admin/resource') }">
          <div class="icon-box"><i class="bi bi-stack"></i></div>
          <span>Resources</span>
        </router-link>

        <router-link v-if="hasPermission('manage_bookings')" to="/admin/booking" class="nav-link" :class="{ active: isActive('/admin/booking') }">
          <div class="icon-box"><i class="bi bi-calendar-check-fill"></i></div>
          <span>Bookings</span>
        </router-link>
      </div>

      <div class="nav-group mb-4">
        <span class="group-label">Management</span>

         <router-link v-if="hasPermission('manage_users')" to="/admin/users" class="nav-link" :class="{ active: isActive('/admin/users') }">
          <div class="icon-box"><i class="bi bi-people-fill"></i></div>
          <span>Users</span>
        </router-link>
        

        <router-link v-if="hasPermission('view_reports')" to="/admin/reports" class="nav-link" :class="{ active: isActive('/admin/reports') }">
          <div class="icon-box"><i class="bi bi-bar-chart-line-fill"></i></div>
          <span>Reports</span>
        </router-link>
      </div>

      <div class="nav-group border-top pt-3 mt-2">
        <span class="group-label">Settings</span>
        <router-link to="/admin/setting" class="nav-link" :class="{ active: isActive('/admin/setting') }">
          <div class="icon-box"><i class="bi bi-gear-fill"></i></div>
          <span>System Settings</span>
        </router-link>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-pill">
        <div class="avatar">A</div>
        <div class="user-meta">
          <span class="u-name">Admin</span>
          <span class="u-status">Online</span>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';
import { onMounted, ref } from 'vue';
import { systemStore } from '../../store/systemSettings';

const route = useRoute();
const permissions = ref<string[]>([]);

const isActive = (path: string): boolean => {
  return route.path === path;
};

const hasPermission = (slug: string): boolean => {
  // If no permissions are explicitly loaded, default to false
  // Master Admins would have ['*'], handle it if necessary (though this is Admin sidebar)
  return permissions.value.includes(slug) || permissions.value.includes('*');
};

onMounted(() => {
  systemStore.loadSettings();
  const storedPerms = localStorage.getItem('userPermissions');
  if (storedPerms) {
      try {
          permissions.value = JSON.parse(storedPerms);
      } catch (e) {
          permissions.value = [];
      }
  }
});
</script>

<style scoped>
.sidebar {
  width: 260px;
  position: fixed;
  top: 60px;
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
  color: #4BB66D; /* Admin specific color */
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
  background: #eef9f2;
  color: #1e4449;
}

.nav-link.active .icon-box {
  background: #4BB66D;
  color: white;
  box-shadow: 0 4px 10px rgba(75, 182, 109, 0.3);
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
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar {
  width: 35px;
  height: 35px;
  background: #4BB66D;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.8rem;
}

.u-name { font-size: 0.85rem; font-weight: 700; display: block; color: #1e293b;}
.u-status { font-size: 0.65rem; color: #4BB66D; display: block; font-weight: 600;}

@media (max-width: 768px) {
  .sidebar { width: 85px; }
  .brand-info, .group-label, .nav-link span, .user-meta { display: none; }
}
</style>
