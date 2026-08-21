<template>
  <nav class="navbar navbar-expand-lg fixed-top shadow-sm" :class="{ 'navbar-scrolled': isScrolled }">
    <div class="container-fluid px-4">
      <!-- Brand Area -->
      <router-link to="/" class="navbar-brand d-flex align-items-center">
        <span class="brand-text">University Resource Reservation System</span>
      </router-link>

      <!-- Right Side: User Profile & Logout -->
      <div class="ms-auto d-flex align-items-center gap-3">
        <!-- User Info (Desktop) -->
        <div class="user-profile d-none d-md-flex align-items-center me-2">
          <div class="user-avatar-circle me-2">
            {{ userName.charAt(0).toUpperCase() }}
          </div>
          <div class="user-details d-flex flex-column text-end">
            <span class="user-name">{{ userName }}</span>
            <span class="user-role-badge">{{ userRole }}</span>
          </div>
        </div>

        <!-- Professional Logout Button -->
        <button 
          class="btn-logout d-flex align-items-center" 
          @click="handleLogout"
          :disabled="isLoggingOut"
          title="Logout from your account"
        >
          <div class="logout-icon-wrapper">
            <i v-if="!isLoggingOut" class="bi bi-box-arrow-right"></i>
            <span v-else class="spinner-border spinner-border-sm" role="status"></span>
          </div>
          <span class="logout-text d-none d-sm-inline ms-2">
            {{ isLoggingOut ? 'Logging Out...' : 'Logout' }}
          </span>
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { systemStore } from '../store/systemSettings';

const router = useRouter();
const userName = ref(localStorage.getItem('userName') || 'User');
const userRole = ref(localStorage.getItem('userRole') || 'Member');
const isLoggingOut = ref(false);
const isScrolled = ref(false);

const API_LOGOUT_URL = 'http://localhost:8000/api/logout';

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20;
};

onMounted(async () => {
  window.addEventListener('scroll', handleScroll);
  await systemStore.loadSettings();
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const getAuthToken = (): string | null => {
    return localStorage.getItem('authToken') || 
           localStorage.getItem('auth_token') || 
           localStorage.getItem('token');
};

const handleLogout = async () => {
    const token = getAuthToken();
    isLoggingOut.value = true;

    if (token) {
        try {
            await axios.post(
                API_LOGOUT_URL,
                {},
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                    }
                }
            );
        } catch (error: any) {
            console.error('Logout API call failed:', error.response?.data || error.message);
        }
    }

    localStorage.clear();
    router.push('/login');
    isLoggingOut.value = false;
};
</script>

<style scoped>
/* Navbar Base Styles */
.navbar {
  height: 60px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.3);
  border-top: 3px solid #10b981; /* Green Touch */
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1030;
}

.navbar-scrolled {
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
}

/* Brand Styles */
.navbar-brand {
  text-decoration: none;
  transition: opacity 0.2s ease;
}

.navbar-brand:hover {
  opacity: 0.9;
}

.brand-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: -0.5px;
}

/* User Profile Styles */
.user-avatar-circle {
  width: 36px;
  height: 36px;
  background: #ecfdf5;
  border: 1.5px solid #10b981; /* Green Touch */
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #10b981;
  font-size: 0.85rem;
}

.user-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.1;
}

.user-role-badge {
  font-size: 0.65rem;
  font-weight: 700;
  color: #10b981; /* Green Touch */
  text-transform: uppercase;
  letter-spacing: 0.8px;
  background: #f0fdf4;
  padding: 1px 6px;
  border-radius: 4px;
  display: inline-block;
  align-self: flex-end;
  margin-top: 2px;
}

/* Modern Logout Button */
.btn-logout {
  background: #fff;
  border: 1px solid #ff7675;
  color: #d63031;
  padding: 5px 14px;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
  transition: all 0.3s ease;
  outline: none;
}

.btn-logout:hover {
  background: #d63031;
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(214, 48, 49, 0.2);
}

.btn-logout:active {
  transform: translateY(0);
}

.btn-logout:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.logout-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

/* Global Body Offset */
:global(body) {
  padding-top: 60px;
}

/* Mobile Adjustments */
@media (max-width: 768px) {
  .navbar {
    height: 56px;
  }
  
  :global(body) {
    padding-top: 56px;
  }
  
  .brand-text {
    font-size: 1rem;
  }
}
</style>