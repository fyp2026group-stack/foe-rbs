<template>
  <Navbar />
  <UserSidebar />
  <div class="section">
    <!-- Modern Dashboard Header - Same as Guest Booking Page -->
    <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white border-start border-5 border-teal">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item active" aria-current="page">Account Settings</li>
            </ol>
          </nav>
          <h2 class="mb-0 fw-bold text-dark-teal">Account Settings</h2>
          <p class="text-muted mb-0">Update your personal details and security preferences.</p>
        </div>
        <div class="text-end d-none d-md-block">
           <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
             <i class="bi bi-shield-lock me-1"></i> Secure Account
           </span>
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 p-lg-5">
            
            <div v-if="isLoading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status"></div>
            </div>

            <form v-else @submit.prevent="updateProfile">
              <div class="mb-4 text-center">
                <div class="avatar-circle mx-auto mb-3">
                  {{ profileForm.name ? profileForm.name.charAt(0).toUpperCase() : 'U' }}
                </div>
                <h5 class="mb-0">{{ profileForm.name }}</h5>
                <span class="badge bg-secondary mt-1">{{ userRole }}</span>
              </div>

              <div v-if="succMsg" class="alert alert-success small py-2">{{ succMsg }}</div>
              <div v-if="errMsg" class="alert alert-danger small py-2">{{ errMsg }}</div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Full Name</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                  <input type="text" class="form-control border-start-0 ps-0" v-model="profileForm.name" required>
                </div>
              </div>

              <!-- Email Field - READONLY / DISABLED - Cannot be changed -->
              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                  <input 
                    type="email" 
                    class="form-control border-start-0 ps-0" 
                    v-model="profileForm.email" 
                    readonly
                    disabled
                    style="background-color: #f5f5f5; cursor: not-allowed;"
                  >
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Department</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-building"></i></span>
                  <select class="form-select border-start-0 ps-0" v-model="profileForm.department" required>
                    <option value="" disabled>Select Department</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
                  </select>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label text-muted small fw-bold">New Password</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                  <input 
                    type="password" 
                    class="form-control border-start-0 ps-0" 
                    v-model="profileForm.password" 
                    placeholder="New Password"
                  >
                </div>
                <div class="form-text mt-1 text-muted" style="font-size: 0.75rem;">Password must be at least 6 characters long if changing.</div>
              </div>

              <hr>

              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-teal-modern px-4 py-2" :disabled="isSaving">
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-cloud-check me-2"></i> Save Changes
                </button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Navbar from '../../components/Navbar.vue';
import UserSidebar from '../../components/Sidebar/UserSidebar.vue';
import axios from 'axios';

const userRole = ref(localStorage.getItem('userRole') || 'User');
const isLoading = ref(true);
const isSaving = ref(false);
const succMsg = ref('');
const errMsg = ref('');

const profileForm = ref({
  name: '',
  email: '',
  password: '',
  department: ''
});

const departments = ref<any[]>([]);

onMounted(async () => {
  await fetchProfile();
  await fetchDepartments();
});

const fetchProfile = async () => {
  try {
    const token = localStorage.getItem('authToken');
    const res = await axios.get('http://localhost:8000/api/user', {
      headers: { Authorization: `Bearer ${token}` }
    });
    const u = res.data;
    profileForm.value.name = u.name;
    profileForm.value.email = u.email;
    profileForm.value.department = u.department || '';
    profileForm.value.password = '';
  } catch (err: any) {
    console.error("Failed to load profile", err);
    errMsg.value = "Failed to load profile data from server.";
  } finally {
    isLoading.value = false;
  }
};

const fetchDepartments = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/departments');
    departments.value = res.data;
  } catch (err) {
    console.error("Failed to fetch departments", err);
  }
};

const updateProfile = async () => {
  succMsg.value = '';
  errMsg.value = '';
  isSaving.value = true;
  try {
    const token = localStorage.getItem('authToken');
    const payload: any = {
      name: profileForm.value.name,
      email: profileForm.value.email,
      department: profileForm.value.department
    };
    
    if (profileForm.value.password) {
      if (profileForm.value.password.length < 6) {
        errMsg.value = "Password must be at least 6 characters.";
        isSaving.value = false;
        return;
      }
      payload.password = profileForm.value.password;
    }

    const res = await axios.put('http://localhost:8000/api/user/profile', payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    if (res.data) {
      succMsg.value = "Profile successfully updated.";
      localStorage.setItem('userName', res.data.name);
      profileForm.value.password = '';
    }
  } catch (err: any) {
    console.error("Update failed", err);
    errMsg.value = err.response?.data?.message || "Failed to update profile.";
  } finally {
    isSaving.value = false;
  }
};
</script>

<style scoped>
/* ========== MODERN DASHBOARD HEADER STYLES (from Guest Booking Page) ========== */
.text-dark-teal { color: #1a3a3d; }
.text-teal { color: #1e4449; }
.bg-teal { background-color: #1e4449; }
.bg-light-teal { background-color: #e5f4de; }
.border-teal { border-color: #1e4449 !important; }
.border-teal-subtle { border-color: #d1e7dd !important; }

.dashboard-header-modern {
    background: linear-gradient(to right, #ffffff, #f7fdf4);
    border-radius: 12px;
}

.btn-teal-modern {
    background: linear-gradient(135deg, #1e4449 0%, #2c5f65 100%);
    color: white;
    border: none;
    transition: all 0.2s ease;
    font-weight: 600;
    border-radius: 8px;
}

.btn-teal-modern:hover {
    background: linear-gradient(135deg, #2c5f65 0%, #1e4449 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 68, 73, 0.2);
}

.btn-teal-modern:disabled {
    opacity: 0.6;
    transform: none;
}

/* ========== END OF MODERN HEADER STYLES ========== */

.section {
  margin-left: 250px;
  padding: 20px;
  margin-top: 20px;
  min-height: calc(100vh - 60px);
  display: flex;
  flex-direction: column;
}

@media (max-width: 768px) {
  .section {
    margin-left: 70px;
  }
}

/* Center the card container */
.row {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 200px);
}

/* Card styling - centered and with proper width */
.card {
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
  border-radius: 16px;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

.card-body {
  padding: 2rem !important;
}

/* Avatar styling */
.avatar-circle {
  width: 90px;
  height: 90px;
  background: linear-gradient(135deg, #4BB66D 0%, #1e4449 100%);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 1rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Form input styling */
.form-control, .form-select {
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.95rem;
  transition: all 0.2s ease;
}

.form-control:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.2rem rgba(75, 182, 109, 0.25);
}

.input-group-text {
  background-color: #f8f9fa;
  border-radius: 8px 0 0 8px;
  color: #4BB66D;
  border-right: none;
}

.input-group .form-control {
  border-radius: 0 8px 8px 0;
  border-left: none;
}

/* Label styling */
.form-label {
  font-weight: 600;
  color: #1e4449;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  letter-spacing: 0.3px;
}

/* Alert styling */
.alert {
  border-radius: 10px;
  border: none;
}

.alert-success {
  background-color: #d1e7dd;
  color: #0f5132;
}

.alert-danger {
  background-color: #f8d7da;
  color: #842029;
}

/* Style for disabled/readonly input */
.form-control:disabled,
.form-control[readonly] {
  background-color: #f8f9fa;
  cursor: not-allowed;
  opacity: 0.8;
  border-color: #dee2e6;
}

/* Horizontal rule styling */
hr {
  margin: 1.5rem 0;
  border-top: 1px solid #e9ecef;
}

/* Help text styling */
.form-text {
  margin-top: 0.5rem;
  color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .card-body {
    padding: 1.5rem !important;
  }
  
  .avatar-circle {
    width: 70px;
    height: 70px;
    font-size: 2rem;
  }
  
  .btn-teal-modern {
    padding: 0.5rem 1.5rem;
  }
  
  .row {
    min-height: calc(100vh - 180px);
  }
  
  .dashboard-header-modern {
    padding: 1rem !important;
  }
}

@media (max-width: 576px) {
  .card-body {
    padding: 1.25rem !important;
  }
  
  .section-title {
    font-size: 1.5rem;
  }
}
</style>