<template>
  <Navbar />
  <AdminSidebar />
  <div class="section">
    <!-- Modern Header Card -->
    <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item active" aria-current="page">Settings</li>
          </ol>
        </nav>
        <h2 class="mb-0 fw-bold text-dark-teal">Account Settings</h2>
        <p class="text-muted mb-0">Update your personal details and security preferences.</p>
      </div>
      <div class="text-end d-none d-md-block">
        <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
          <i class="bi bi-person-badge me-1"></i> {{ userRole }} Account
        </span>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            
            <div v-if="isLoading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status"></div>
            </div>

            <form v-else @submit.prevent="updateProfile">
              <div class="mb-4 text-center">
                <div class="avatar-circle mx-auto mb-3">
                  {{ profileForm.name ? profileForm.name.charAt(0).toUpperCase() : 'A' }}
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

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                  <input type="email" class="form-control border-start-0 ps-0" v-model="profileForm.email" disabled readonly style="background-color: #f8f9fa;">
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
                    placeholder="Leave blank to keep current password"
                  >
                </div>
                <div class="form-text mt-1 text-muted" style="font-size: 0.75rem;">Password must be at least 6 characters long if changing.</div>
              </div>

              <hr>

              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary px-4" :disabled="isSaving">
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
import AdminSidebar from '../../components/Sidebar/Admin_Sidebar.vue';
import axios from 'axios';

const userRole = ref(localStorage.getItem('userRole') || 'Admin');
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
    profileForm.value.password = ''; // Don't prefill password
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
      localStorage.setItem('userEmail', res.data.email);
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
.section {
  margin-left: 260px;
  padding: 20px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section {
    margin-left: 70px;
  }
}


.avatar-circle {
  width: 80px;
  height: 80px;
  background-color: #4BB66D;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: bold;
}

.form-control:focus, .form-select:focus {
  border-color: #ced4da;
  box-shadow: none;
}

.form-control, .form-select {
    border-radius: 8px;
}

.input-group-text {
  color: #4BB66D;
}
</style>
