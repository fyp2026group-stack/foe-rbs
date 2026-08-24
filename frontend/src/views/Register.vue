<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="text-center mb-4">
        <img :src="systemStore.logo || defaultLogoUrl" alt="University Logo" class="auth-logo mb-3">
        <h2 class="auth-title">{{ systemStore.name || 'FOE' }}</h2>
        <h4 class="auth-title">Resource Booking System</h4>
        <p class="text-muted">Register as a Departmental User</p>
      </div>

      <form @submit.prevent="handleRegister">
        <div v-if="error" class="alert alert-danger" role="alert">
            {{ error }}
        </div>
        
        <div class="mb-3">
          <label for="fullName" class="form-label">Full Name</label>
          <input
            type="text"
            class="form-control"
            id="fullName"
            v-model="fullName"
            required
            placeholder="Enter your full name"
            :disabled="isLoading"
          >
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Email Address </label>
          <input
            type="email"
            class="form-control"
            id="email"
            v-model="email"
            required
            placeholder="Enter your university email"
            :disabled="isLoading"
            title="Please use your email address"
          >
        </div>

        <div class="mb-3">
            <label for="department" class="form-label">Department</label>
            <select
                class="form-select"
                id="department"
                v-model="selectedDepartment"
                required
                :disabled="isLoading || isFetchingDepartments"
            >
                <option value="" disabled>Select your department</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.name">
                    {{ dept.name }}
                </option>
            </select>
            <div v-if="departmentsError" class="text-danger small mt-1">Failed to load departments.</div>
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
              placeholder="Create a password"
              :disabled="isLoading"
            >
            <button 
              class="btn btn-outline-secondary toggle-password" 
              type="button" 
              @click="isPasswordVisible = !isPasswordVisible"
              :disabled="isLoading"
            >
              <i :class="isPasswordVisible ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label for="confirmPassword" class="form-label">Confirm Password</label>
          <div class="input-group">
            <input
              :type="isConfirmPasswordVisible ? 'text' : 'password'"
              class="form-control no-browser-icon"
              id="confirmPassword"
              v-model="confirmPassword"
              required
              placeholder="Confirm your password"
              :disabled="isLoading"
            >
            <button 
              class="btn btn-outline-secondary toggle-password" 
              type="button" 
              @click="isConfirmPasswordVisible = !isConfirmPasswordVisible"
              :disabled="isLoading"
            >
              <i :class="isConfirmPasswordVisible ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
            </button>
          </div>
        </div>

        <div class="mb-3 form-check">
          <input class="form-check-input" type="checkbox" id="terms" v-model="acceptTerms" required :disabled="isLoading">
          <label class="form-check-label" for="terms">
            I agree to the terms and conditions
          </label>
        </div>

        <button 
            type="submit" 
            class="btn btn-primary w-100 mb-3"
            :disabled="isLoading || !acceptTerms" >
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ isLoading ? 'Registering...' : 'Create Account' }}
        </button>

        <div class="login-prompt py-3 px-2 mt-4 text-center rounded bg-light">
          <span class="text-muted small">Already have an account? </span>
          <router-link to="/login" class="fw-bold text-decoration-none ms-1">Sign In Now</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import defaultLogoUrl from '../assets/logo.png';
import { systemStore } from '../store/systemSettings';

const API_URL = ((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/register'); 
const router = useRouter();

const fullName = ref('');
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const acceptTerms = ref(false);
const selectedDepartment = ref('');

const departments = ref<any[]>([]);
const isFetchingDepartments = ref(false);
const departmentsError = ref(false);

const isLoading = ref(false);
const error = ref<string | null>(null);

// Visibility states for both password fields
const isPasswordVisible = ref(false);
const isConfirmPasswordVisible = ref(false);

onMounted(async () => {
    systemStore.loadSettings();
    isFetchingDepartments.value = true;
    try {
        const response = await fetch(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/departments'));
        if (response.ok) {
            departments.value = await response.json();
        } else {
            departmentsError.value = true;
        }
    } catch (e) {
        departmentsError.value = true;
    } finally {
        isFetchingDepartments.value = false;
    }
});

const handleRegister = async () => {
  error.value = null;
  if (password.value !== confirmPassword.value) {
    error.value = 'Passwords do not match.';
    return;
  }
  if (!acceptTerms.value) {
    error.value = 'You must accept the terms and conditions.';
    return;
  }

  const payload = {
    name: fullName.value,
    email: email.value,
    password: password.value,
    department: selectedDepartment.value
  };

  isLoading.value = true;

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (response.ok) {
      alert('Registration successful! Please login.');
      router.push('/login');
    } else {
      if (response.status === 422 && data.errors) {
        const validationErrors = data.errors;
        error.value = validationErrors.email?.[0] || validationErrors.name?.[0] || validationErrors.password?.[0] || 'Validation failed.';
      } else {
        error.value = data.message || `Registration failed with status: ${response.status}`;
      }
    }
  } catch (e) {
    error.value = 'Could not connect to the server.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
/* HIDE DEFAULT BROWSER EYE ICON */
.no-browser-icon::-ms-reveal,
.no-browser-icon::-ms-clear {
  display: none;
}

/* Base layout styles - FIXED SIZE, NO SCROLLING */
.auth-container {
  height: 100vh;
  width: 100vw;
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
  border-radius: 16px;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
  padding: 25px 35px;
  width: 100%;
  max-width: 450px;
  height: auto;
  max-height: 98vh;
  overflow-y: auto;
}

/* No scrollbar needed - content fits perfectly */
.auth-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 4px;
  font-size: 1.5rem;
}

.btn-primary {
  background-color:#4BB66D;
  border-color: #4BB66D;
  font-weight: 500;
  padding: 10px;
}

.btn-primary:hover:not(:disabled) {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* Toggle Button Styling */
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

.form-select:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.2rem rgba(75, 182, 109, 0.25);
}

/* Keeps border green when clicking the eye button */
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
  max-height: 70px;
  width: auto;
}

.form-label {
  font-size: 0.9rem;
  margin-bottom: 0.3rem;
  font-weight: 500;
}

.form-control, .form-select {
  padding: 0.5rem 0.75rem;
  font-size: 0.9rem;
}

.mb-3 {
  margin-bottom: 0.8rem !important;
}

.mb-4 {
  margin-bottom: 1rem !important;
}

.btn {
  font-size: 0.9rem;
  padding: 8px 12px;
}

.text-muted {
  font-size: 0.8rem;
}

.login-prompt {
  border: 1px solid #eef2f5;
}

/* Alert message styling */
.alert-danger {
  padding: 0.6rem;
  margin-bottom: 1rem;
  font-size: 0.85rem;
}

/* Responsive for smaller screens - adjust padding */
@media (max-width: 576px) {
  .auth-card {
    padding: 20px 25px;
    max-width: 95%;
  }
  
  .auth-title {
    font-size: 1.5rem;
  }
  
  .auth-logo {
    max-height: 80px;
  }
  
  .form-label {
    font-size: 0.85rem;
  }
}

/* For very small height screens */
@media (max-height: 700px) {
  .auth-card {
    padding: 20px 35px;
  }
  
  .mb-3 {
    margin-bottom: 0.6rem !important;
  }
  
  .auth-logo {
    max-height: 70px;
  }
}
</style>