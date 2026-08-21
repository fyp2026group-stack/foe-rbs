<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="text-center mb-4">
        <h2 class="auth-title">Reset Password</h2>
        <p class="text-muted">{{ getStepDescription() }}</p>
      </div>

      <div v-if="showSuccessMessage" class="success-popup">
        Password reset successfully! Redirecting...
      </div>
      
      <div v-if="errorMessage" class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      </div>

      <form @submit.prevent="handleSubmit">
        
        <div v-if="step === 1" class="mb-3">
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

        <div v-if="step === 2" class="mb-3">
          <label for="otp" class="form-label">Enter OTP</label>
          <input
            type="text"
            class="form-control"
            id="otp"
            v-model="otp"
            required
            placeholder="Enter 6-digit OTP"
            maxlength="6"
            :disabled="isLoading"
          >
          <small class="text-muted">OTP sent to {{ email }}. Code expires in 15 minutes.</small>
        </div>

        <div v-if="step === 3">
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <input
              type="password"
              class="form-control"
              id="newPassword"
              v-model="newPassword"
              required
              minlength="6"
              placeholder="Enter new password (min 6 characters)"
              :disabled="isLoading"
            >
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm Password</label>
            <input
              type="password"
              class="form-control"
              id="confirmPassword"
              v-model="confirmPassword"
              required
              minlength="6"
              placeholder="Confirm new password"
              :disabled="isLoading"
            >
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
          {{ getButtonText() }}
        </button>

        <div class="text-center">
          <router-link to="/login" class="text-decoration-none">
            <i class="bi bi-arrow-left me-2"></i>Back to Login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios'; // 🛑 Import axios

const router = useRouter();
const step = ref(1);
const email = ref('');
const otp = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showSuccessMessage = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');

const API_BASE_URL = 'http://localhost:8000/api';

const getStepDescription = () => {
  if (step.value === 1) return 'Enter your email to receive reset instructions';
  if (step.value === 2) return 'Enter the OTP sent to your email';
  return 'Enter your new password';
};

const getButtonText = () => {
  if (step.value === 1) return 'Send OTP';
  if (step.value === 2) return 'Verify OTP';
  return 'Save Password';
};

const clearError = () => {
    errorMessage.value = '';
};

const handleApiError = (error: any) => {
    // Tries to get the specific message from Laravel validation errors
    const responseMessage = error.response?.data?.message;
    const validationErrors = error.response?.data?.errors;
    
    if (validationErrors) {
        // Concatenate all validation messages
        errorMessage.value = Object.values(validationErrors).flat().join(' | ');
    } else {
        errorMessage.value = responseMessage || 'An unknown server error occurred.';
    }
    console.error('API Error:', error);
};

const handleSubmit = async () => {
    clearError();
    isLoading.value = true;

    try {
        if (step.value === 1) {
            // STEP 1: SEND OTP
            await axios.post(`${API_BASE_URL}/forgot-password/email`, { 
                email: email.value 
            });
            step.value = 2;
            
        } else if (step.value === 2) {
            // STEP 2: VERIFY OTP
            if (otp.value.length !== 6) { 
                errorMessage.value = 'Please enter a valid 6-digit OTP'; 
                isLoading.value = false;
                return;
            }
            
            await axios.post(`${API_BASE_URL}/forgot-password/verify-otp`, {
                email: email.value,
                otp: otp.value
            });
            step.value = 3;

        } else if (step.value === 3) {
            // STEP 3: RESET PASSWORD
            if (newPassword.value !== confirmPassword.value) { 
                errorMessage.value = 'Passwords do not match.'; 
                isLoading.value = false;
                return;
            }
            if (newPassword.value.length < 6) {
                errorMessage.value = 'Password must be at least 6 characters.';
                isLoading.value = false;
                return;
            }

            await axios.post(`${API_BASE_URL}/forgot-password/reset`, {
                email: email.value,
                otp: otp.value, 
                password: newPassword.value,
                password_confirmation: confirmPassword.value, // Required by backend
            });
            
            // Success: Show popup and redirect
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
                router.push('/login');
            }, 2000);
        }
    } catch (error: any) {
        handleApiError(error);
    } finally {
        isLoading.value = false;
    }
};
</script>

<style scoped>
/* ... (Keep your existing styles here) ... */
.auth-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e4449 0%, #4BB66D 100%);
  padding: 20px;
}

.auth-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  padding: 40px;
  width: 100%;
  max-width: 450px;
  position: relative;
}

.auth-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 8px;
}

.text-muted {
  color: #6c757d;
  margin-bottom: 0;
}

.form-label {
  font-weight: 500;
  color: #1e4449;
  margin-bottom: 8px;
}

.form-control {
  padding: 12px;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s ease;
}

.form-control:focus {
  border-color:  #4BB66D;
  box-shadow: 0 0 0 0.2rem rgba(38, 213, 22, 0.25);
  outline: none;
}

.btn-primary {
  background-color:  #4BB66D;
  border-color:  #4BB66D;
  font-weight: 500;
  padding: 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
}

.btn-primary:hover {
  background-color:  #3f975b;
  border-color:  #3f975b;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(38, 213, 22, 0.3);
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.w-100 {
  width: 100%;
}

.text-center {
  text-align: center;
}

.text-decoration-none {
  text-decoration: none;
  color:  #4BB66D;
  font-weight: 500;
  transition: color 0.3s ease;
}

.text-decoration-none:hover {
  color:   #3f975b;
}

.success-popup {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  background-color:  #4BB66D;
  color: white;
  padding: 16px 32px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(38, 213, 22, 0.4);
  font-weight: 500;
  z-index: 1000;
  animation: slideDown 0.3s ease;
}
.alert-danger {
    margin-top: 15px;
    padding: 10px;
    font-size: 0.9rem;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

small {
  display: block;
  margin-top: 4px;
  font-size: 14px;
}
</style>