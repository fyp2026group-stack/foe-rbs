<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="text-center mb-4">
        <img :src="systemStore.logo || defaultLogoUrl" alt="University Logo" class="auth-logo mb-3">
        <h2 class="auth-title">{{ systemStore.name || 'FOE' }}</h2>
        <h4 class="auth-title">Resource Booking System</h4>
        <p class="text-muted">Sign in to your account</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input
            type="email"
            class="form-control"
            id="email"
            v-model="email"
            required
            placeholder="Enter your email"
            :disabled="isLoading"
          >
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input
              :type="isPasswordVisible ? 'text' : 'password'"
              class="form-control no-browser-icon" 
              id="password"
              v-model="password"
              required
              placeholder="Enter your password"
              :disabled="isLoading"
            >
            <button 
              class="btn btn-outline-secondary toggle-password" 
              type="button" 
              @click="togglePasswordVisibility"
              :disabled="isLoading"
            >
              <i :class="isPasswordVisible ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
            </button>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" v-model="rememberMe">
            <label class="form-check-label" for="remember">
              Remember me
            </label>
          </div>
          <router-link to="/forgot-password" class="text-decoration-none">Forgot Password?</router-link>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-2" :disabled="!email || !password || isLoading">
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Signing In...' : 'Sign In' }}
        </button>

        <button type="button" @click="handleGuestLogin" class="btn btn-outline-success w-100 mb-3" :disabled="isLoading">
            Sign In as Guest
        </button>

        <div v-if="loginError" class="alert alert-danger text-center" role="alert">
            {{ loginError }}
        </div>

        <div class="text-center">
          <span class="text-muted">Don't have an account? </span>
          <router-link to="/register" class="text-decoration-none">Register</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { reportStore } from '../store/reportStore';
import { resourceStore } from '../store/resourceStore';
import { userStore } from '../store/userStore';
import { systemStore } from '../store/systemSettings';
import defaultLogoUrl from '../assets/logo.png';

const router = useRouter();
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const loginError = ref(''); 
const isLoading = ref(false);

// Toggle Visibility logic
const isPasswordVisible = ref(false);
const togglePasswordVisibility = () => {
  isPasswordVisible.value = !isPasswordVisible.value;
};

onMounted(() => {
  document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
  document.body.style.overflow = 'auto';
});

const API_URL = 'http://localhost:8000/api/login';
const GUEST_LOGIN_URL = 'http://localhost:8000/api/guest-login';

// Helper function to extract role ID from user data
const extractRoleId = (user: any): number => {
  // Check if user has roles array (from your API response)
  if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
    return user.roles[0].id;
  }
  // Check if user has direct role_id
  if (user.role_id) {
    return user.role_id;
  }
  // Check if user has role object
  if (user.role && user.role.id) {
    return user.role.id;
  }
  // Default to Guest (4) if not found
  return 4;
};

// Helper function to get role name from user data
const extractRoleName = (user: any): string => {
  // Check if user has roles array
  if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
    return user.roles[0].name;
  }
  // Check if user has direct role
  if (user.role) {
    return typeof user.role === 'string' ? user.role : user.role.name;
  }
  // Default to 'User' if not found
  return 'User';
};

// Process login success and save all necessary data
const processLoginSuccess = (data: any) => {
  const { user, token, permissions } = data;
  
  // Extract role information
  const roleId = extractRoleId(user);
  const roleName = extractRoleName(user);
  
  console.log('========== LOGIN SUCCESS ==========');
  console.log('User:', user);
  console.log('Role ID:', roleId);
  console.log('Role Name:', roleName);
  console.log('===================================');
  
  // 1. Set authentication state
  localStorage.setItem('isAuthenticated', 'true');
  localStorage.setItem('authToken', token);
  localStorage.setItem('token', token);
  
  // 2. Save user information
  localStorage.setItem('user', JSON.stringify(user));
  localStorage.setItem('userName', user.name);
  localStorage.setItem('userEmail', user.email);
  localStorage.setItem('email', user.email);
  localStorage.setItem('userId', user.id.toString());
  
  // Save permissions
  if (permissions) {
      localStorage.setItem('userPermissions', JSON.stringify(permissions));
  }
  
  // 3. CRITICAL: Save role information in multiple formats for compatibility
  localStorage.setItem('userRole', roleName);
  localStorage.setItem('role', roleName);
  localStorage.setItem('role_id', roleId.toString());
  localStorage.setItem('user_role_id', roleId.toString());
  localStorage.setItem('roleId', roleId.toString());
  
  // 4. Save user roles array as string for debugging
  if (user.roles) {
    localStorage.setItem('user_roles', JSON.stringify(user.roles));
  }
  
  // 5. TRIGGER BACKGROUND DATA SYNC based on role
  if (roleName === 'Master Admin' || roleId === 1) {
    systemStore.loadSettings(); 
    userStore.fetchUsers();
    resourceStore.fetchAll();
    reportStore.fetchAllReports();
    router.push('/master-admin/dashboard');
  } 
  else if (roleName === 'Admin' || roleId === 2) {
    systemStore.loadSettings();
    router.push('/admin/dashboard');
  } 
  else if (roleName === 'Guest' || roleId === 4) {
    router.push('/guest-resources');
  }
  else {
    // Internal User (role_id = 3)
    router.push('/user/dashboard');
  }
};

const handleLogin = async () => {
    loginError.value = '';
    isLoading.value = true;
    try {
        const response = await axios.post(API_URL, {
            email: email.value,
            password: password.value,
            remember: rememberMe.value
        });
        const data = response.data;

        if (data.token && data.user) {
            processLoginSuccess(data);
        } else {
            loginError.value = data.message || 'Login failed. Invalid credentials.';
        }
    } catch (error: any) {
        console.error('Login error:', error);
        if (error.response) {
            loginError.value = error.response.data?.message || 'Invalid email or password.';
        } else if (error.request) {
            loginError.value = 'Network error. Please check your connection.';
        } else {
            loginError.value = 'An error occurred. Please try again.';
        }
    } finally {
        isLoading.value = false;
    }
};

const handleGuestLogin = async () => {
    loginError.value = '';
    isLoading.value = true;
    try {
        const response = await axios.post(GUEST_LOGIN_URL);
        const data = response.data;

        if (data.token && data.user) {
            processLoginSuccess(data);
        } else {
            loginError.value = data.message || 'Guest login failed. Please try again.';
        }
    } catch (error: any) {
        console.error('Guest login error:', error);
        if (error.response) {
            loginError.value = error.response.data?.message || 'Guest login failed.';
        } else if (error.request) {
            loginError.value = 'Network error. Please check your connection.';
        } else {
            loginError.value = 'An error occurred. Please try again.';
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<style scoped>
/* 1. HIDE THE BROWSER DEFAULT REVEAL ICON (Edge/Chrome) */
.no-browser-icon::-ms-reveal,
.no-browser-icon::-ms-clear {
  display: none;
}

/* Base layout styles - SCROLL REMOVED */
.auth-container {
  min-height: 100vh;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e4449 0%, #4BB66D 100%);
  padding: 20px;
  overflow: hidden;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.auth-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  padding: 40px;
  width: 100%;
  max-width: 450px;
  max-height: 90vh;
  overflow-y: auto;
}

/* Custom scrollbar for card (optional - looks better) */
.auth-card::-webkit-scrollbar {
  width: 6px;
}

.auth-card::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.auth-card::-webkit-scrollbar-thumb {
  background: #4BB66D;
  border-radius: 10px;
}

.auth-card::-webkit-scrollbar-thumb:hover {
  background: #1e4449;
}

.auth-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 8px;
}

.btn-primary {
  background-color: #4BB66D;
  border-color: #4BB66D;
  font-weight: 500;
  padding: 12px;
}

.btn-primary:hover:not(:disabled) {
  background-color: #3f975b;
  border-color: #3f975b;
}

.btn-outline-success {
  color: #4BB66D;
  border-color: #4BB66D;
}

.btn-outline-success:hover:not(:disabled) {
  background-color: #4BB66D;
  color: white;
}

.toggle-password {
  border-color: #dee2e6;
  color: #6c757d;
  background-color: #fff;
}

.toggle-password:hover:not(:disabled) {
  background-color: #f8f9fa;
  color: #4BB66D;
}

.form-control:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.2rem rgba(75, 182, 109, 0.25);
}

.input-group:focus-within .form-control,
.input-group:focus-within .btn {
  border-color: #4BB66D;
}

a {
  color: #0d6efd;
  text-decoration: none;
  transition: color 0.2s;
}

a:hover {
  color: #0a58ca;
  text-decoration: underline;
}

.auth-logo {
  max-height: 120px; 
  width: auto;
}

.alert-danger {
  color: #842029;
  background-color: #f8d7da;
  border-color: #f5c2c7;
  padding: 1rem;
  margin-bottom: 1rem;
  border-radius: 0.25rem;
}

/* Responsive for mobile */
@media (max-width: 576px) {
  .auth-card {
    padding: 30px 20px;
    max-height: 95vh;
  }
}
</style>