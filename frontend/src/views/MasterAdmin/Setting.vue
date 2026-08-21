<template>
  <div class="layout-wrapper">
    <Navbar />
    <MasterAdminSidebar />
    
    <main class="content-body">
      <div class="container-fluid">
        <!-- Modern Header Card -->
        <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white">
          <div>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item active" aria-current="page">Settings</li>
              </ol>
            </nav>
            <h2 class="mb-0 fw-bold text-dark-teal">System Configuration</h2>
            <p class="text-muted mb-0">Manage university branding, logos, and global operational parameters.</p>
          </div>
          <div class="text-end d-none d-md-block">
            <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
              <i class="bi bi-gear-wide-connected me-1"></i> Global Settings
            </span>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header-flex">
            <h5 class="m-0">General Settings</h5>
            <div v-if="logoPreview" class="logo-preview-box">
              <img :src="logoPreview" alt="System Logo" class="preview-img" />
              <button type="button" class="btn-remove-preview" @click="resetLogo">
                <i class="bi bi-x-circle-fill"></i>
              </button>
            </div>
          </div>

          <form @submit.prevent="saveSettings" class="settings-form mt-4">
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label">System Name</label>
                <input type="text" class="form-control shadow-none" v-model="settings.site_name" placeholder="e.g., University RBS">
              </div>
              <div class="col-md-6">
                <label class="form-label">Organization Name</label>
                <input type="text" class="form-control shadow-none" v-model="settings.organization_name" placeholder="e.g., KIU Sri Lanka">
              </div>
              
              <div class="col-md-12">
                <label class="form-label">Update Identity Logo</label>
                <div class="upload-wrapper">
                  <input type="file" class="form-control" @change="handleFileUpload" accept="image/*">
                  <div class="form-text text-muted">Recommended: Transparent PNG, 200x60px. New uploads replace the current logo.</div>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Contact Email</label>
                <input type="email" class="form-control shadow-none" v-model="settings.contact_email">
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control shadow-none" v-model="settings.phone_number">
              </div>
              <div class="col-md-12">
                <label class="form-label">Physical Address</label>
                <textarea class="form-control shadow-none" v-model="settings.address" rows="2"></textarea>
              </div>
            </div>

            <div class="form-footer mt-5">
              <button type="submit" class="btn btn-apply" :disabled="isLoading">
                <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-shield-check me-2"></i>
                {{ isLoading ? 'Finalizing...' : 'Save Configuration' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';
import { systemStore } from '../../store/systemSettings';

// Local state initialized from the Global Store
const settings = ref({ ...systemStore.settings });
const selectedFile = ref<File | null>(null);
const logoPreview = ref<string | null>(null);
const isLoading = ref(false);

/**
 * Handles instant local preview of uploaded logo
 */
const handleFileUpload = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) {
    selectedFile.value = file;
    logoPreview.value = URL.createObjectURL(file);
  }
};

/**
 * Reverts the logo to the currently saved version
 */
const resetLogo = () => {
  selectedFile.value = null;
  logoPreview.value = systemStore.logo;
};

/**
 * Persists settings to the database and updates the global store
 */
const saveSettings = async () => {
  isLoading.value = true;
  const formData = new FormData();
  
  // Mapping local site_name to the database key
  formData.append('site_name', settings.value.site_name); 
  formData.append('organization_name', settings.value.organization_name);
  formData.append('contact_email', settings.value.contact_email);
  formData.append('phone_number', settings.value.phone_number);
  formData.append('address', settings.value.address);

  if (selectedFile.value) {
    formData.append('logo', selectedFile.value);
  }

  try {
    const token = localStorage.getItem('authToken');
    const response = await axios.post('http://localhost:8000/api/settings', formData, {
      headers: { 
        'Content-Type': 'multipart/form-data',
        'Authorization': `Bearer ${token}`
      }
    });
    
    // Push the new data to the Global Store memory immediately
    systemStore.updateState(response.data); 

    // Success cleanup
    selectedFile.value = null;
    logoPreview.value = systemStore.logo; 
    alert('System identity updated successfully.');

  } catch (e) {
    console.error("Save Error:", e);
    alert('Failed to save settings. Check if the API Gateway is reachable.');
  } finally {
    isLoading.value = false;
  }
};

/**
 * Lifecycle Hook: Instant Load Logic
 */
onMounted(async () => {
  // If data isn't in memory yet, fetch it once.
  // If we pre-fetched at login, this happens in 0ms.
  if (!systemStore.isLoaded) {
    isLoading.value = true;
    await systemStore.loadSettings();
    isLoading.value = false;
  }
  
  // Sync form with memory
  settings.value = { ...systemStore.settings };
  logoPreview.value = systemStore.logo;
});
</script>

<style scoped>
.layout-wrapper { 
  background: #f1f5f9; 
  min-height: 100vh; 
  display: flex; 
  flex-direction: column; 
}

/* Structural separation to prevent overlap with the 260px sidebar */
.content-body { 
  margin-left: 260px; 
  padding: 30px 40px; 
  margin-top: 60px; /* Offset for fixed navbar */
  flex: 1;
  transition: all 0.3s ease;
}

.page-header { margin-bottom: 25px; }
.section-title { font-weight: 800; color: #1e293b; font-size: 1.5rem; }
.section-subtitle { color: #64748b; font-size: 0.95rem; }

/* Light card with crisp borders for identification */
.settings-card { 
  background: #fff; 
  border: 1px solid #e2e8f0; 
  border-radius: 16px; 
  padding: 30px 35px; 
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); 
}

.card-header-flex { 
  display: flex; 
  justify-content: space-between; 
  align-items: center;
  border-bottom: 1px solid #f1f5f9; 
  padding-bottom: 15px; 
}

/* Image Preview Styles */
.logo-preview-box { position: relative; }
.preview-img { 
  height: 60px; 
  max-width: 220px; 
  object-fit: contain; 
  border: 1.5px solid #e2e8f0; 
  padding: 6px; 
  border-radius: 8px;
  background: #f8fafc;
}

.btn-remove-preview {
  position: absolute;
  top: -10px;
  right: -10px;
  background: white;
  border: none;
  color: #ef4444;
  font-size: 1.25rem;
  line-height: 1;
  padding: 0;
  border-radius: 50%;
  cursor: pointer;
}

.form-label { font-weight: 600; color: #475569; font-size: 0.88rem; margin-bottom: 8px; }

/* Professional Emerald Green Button */
.btn-apply { 
  background: #10b981; 
  color: #fff; 
  font-weight: 700; 
  padding: 12px 32px; 
  border: none; 
  border-radius: 10px;
  box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
  transition: all 0.2s ease;
}

.btn-apply:hover:not(:disabled) { 
  background: #059669; 
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
}

.form-footer { 
  display: flex; 
  justify-content: flex-end; 
  border-top: 1px solid #f1f5f9; 
  padding-top: 25px;
  margin-top: 30px;
  padding-bottom: 50px; /* Extra padding at bottom to ensure button is fully visible */
}


.row.g-4 {
  --bs-gutter-y: 0.8rem;  
  --bs-gutter-x: 1.2rem;
}



@media (max-width: 768px) { 
  .content-body { margin-left: 80px; padding: 30px 20px; } 
}


@media (max-height: 750px) {
  .content-body {
    padding: 20px 35px;
  }
  
  .settings-card {
    padding: 20px 25px;
  }
  
  .page-header {
    margin-bottom: 15px;
  }
  
  .form-footer {
    padding-top: 15px;
    margin-top: 15px !important;
  }
  
  .row.g-4 {
    --bs-gutter-y: 0.6rem;
  }
}

</style>