<template>
  <navbar/>
  <admin_-sidebar/>
  <div class="section">
    <!-- Modern Header Card -->
    <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item active" aria-current="page">Resources</li>
          </ol>
        </nav>
        <h2 class="mb-0 fw-bold text-dark-teal">Resource Management</h2>
        <p class="text-muted mb-0">Manage and monitor your assigned campus resources.</p>
      </div>
      <div class="text-end d-none d-md-block">
        <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
          <i class="bi bi-collection me-1"></i> {{ resourceStore.resources.length }} Assigned
        </span>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div></div>
      <div class="d-flex gap-2 mt-3">
        <button 
          class="btn btn-success btn-sm" 
          @click="showAddModal = true"
        >
          <i class="bi bi-plus-circle me-1"></i>Add New
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading resources...</p>
    </div>

    <!-- Error State -->
    <div v-if="errorMessage && !isLoading" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchResources">
        <i class="bi bi-arrow-clockwise me-1"></i>Retry
      </button>
    </div>

    <!-- Main Content -->
    <div v-if="!isLoading && !errorMessage">
      <div class="mb-4">
        <div class="row g-3">
          <div class="col-md-8">
            <input
              type="text"
              class="form-control"
              placeholder="Search resources by name..."
              v-model="searchQuery"
            >
          </div>
          <div class="col-md-4">
            <select class="form-select" v-model="selectedCategory">
              <option value="">All Categories</option>
              <!-- Use categories from store -->
              <option v-for="category in categoriesList" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredResources.length === 0" class="text-center py-5">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-3">No resources found</p>
        <button class="btn btn-success" @click="showAddModal = true">
          <i class="bi bi-plus-circle me-1"></i>Add Your First Resource
        </button>
      </div>

      <!-- Resources Grid -->
      <div v-else class="row g-4">
        <div v-for="resource in filteredResources" :key="resource.id" class="col-md-4">
          <div class="resource-card">
            <div class="resource-actions">
              <button 
                  class="btn btn-sm btn-action-edit" 
                  @click.stop="navigateToEditResource(resource.id)" 
                  title="Edit Resource"
              >
                <i class="bi bi-pencil-square"></i>
              </button>
              <button 
                  class="btn btn-sm btn-action-delete" 
                  @click.stop="openDeleteResourceConfirmation(resource)" 
                  title="Delete Resource"
              >
                <i class="bi bi-trash"></i>
              </button>
            </div>

            <div @click="navigateToSingleresource(resource.id)">
              <div class="resource-image">
                <img :src="getImageUrl(resource)" :alt="resource.name">
              </div>
              <div class="resource-body">
                <h5>{{ resource.name }}</h5>
                <p v-if="resource.location_name" class="text-muted mb-1 small">
                  <i class="bi bi-geo-alt me-1"></i>{{ resource.location_name }}
                </p>
                <p class="text-muted mb-2">
                  <i class="bi bi-tag me-1"></i>{{ getCategoryName(resource.category_id) }}
                </p>
                <p class="text-muted mb-2 small">
                  <i class="bi bi-currency-rupee me-1"></i>Rs. {{ resource.base_price }}
                </p>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                  
                  <div class="d-flex align-items-center gap-2">
                      <span class="badge" :class="getStatusClass(resource.status)">
                        {{ resource.status }}
                      </span>
                      <div class="form-check form-switch" @click.stop>
                        <input
                          class="form-check-input"
                          type="checkbox"
                          :checked="resource.status === 'Active'"
                          @change="toggleResourceStatus(resource.id)"
                        >
                      </div>
                  </div>

                  <button 
                      v-if="resource.status === 'Active'"
                      class="btn btn-sm btn-reserve-card" 
                      @click.stop="handleReserveClick(resource.id)"
                  >
                      <i class="bi bi-calendar-check me-1"></i> Reserve
                  </button>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Modal -->
  <div class="modal fade" :class="{ 'show d-block': showAddModal }" tabindex="-1" @click.self="showAddModal = false" style="background-color: rgba(0,0,0,0.5);" v-if="showAddModal">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Resource</h5>
          <button type="button" class="btn-close" @click="showAddModal = false"></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-3">How would you like to create the resource?</p>
          <div class="d-grid gap-2">
            <button class="btn btn-outline-dark-teal" @click="navigateToAdd_Custom">
              <i class="bi bi-file-earmark-plus me-2"></i>Custom Resource
            </button>
            <button class="btn btn-outline-dark-teal" @click="openTemplateSelectionModal">
              <i class="bi bi-layout-text-window-reverse me-2"></i>From Template
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Delete Confirmation Modal -->
  <div class="modal fade" :class="{ 'show d-block': showDeleteConfirmation }" tabindex="-1" @click.self="handleCancelDeletion" style="background-color: rgba(0,0,0,0.5);" v-if="showDeleteConfirmation">
    <div class="modal-dialog delete-modal-top"> 
      <div class="modal-content">

        <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">Are you sure you want to delete the resource <strong>{{ resourceToDelete?.name }}</strong>?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">No</button>
                <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation">Yes</button>
            </div>
        </template>

        <template v-else-if="deleteStep === 'final'">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion</h5>
                <button type="button" class="btn-close btn-close-white" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">This action will permanently delete the resource <strong>{{ resourceToDelete?.name }}</strong>. Are you sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                <button type="button" class="btn btn-danger" @click="handleDeleteResource" :disabled="isDeleting">
                    <span v-if="isDeleting" class="spinner-border spinner-border-sm me-1"></span>
                    {{ isDeleting ? 'Deleting...' : 'Confirm' }}
                </button>
            </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { resourceStore } from '../../store/resourceStore';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import Admin_Sidebar from '../../components/Sidebar/Admin_Sidebar.vue';

const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';
const STORAGE_URL_ROOT = 'http://localhost:8000/api/resources/storage';

// Get auth token
const getAuthToken = () => {
    return localStorage.getItem('authToken') || 
           localStorage.getItem('auth_token') || 
           localStorage.getItem('token');
};

// Resource Interface
interface Resource {
    id: number;
    name: string;
    location_name?: string;
    category_id: number;
    base_price: number;
    assigned_admin_id?: number;
    description?: string;
    status: 'Active' | 'Inactive' | 'Maintenance';
    images?: Array<{
        file_path: string;
        file_name: string;
    }>;
}

interface Category {
    id: number;
    name: string;
}

// State
const searchQuery = ref('');
const selectedCategory = ref('');
const resources = ref<Resource[]>([]);
const categories = ref<Category[]>([]);
const isLoading = ref(!resourceStore.isLoaded);
const isDeleting = ref(false);
const errorMessage = ref('');

// Modal States
const showAddModal = ref(false);
const showDeleteConfirmation = ref(false);
const resourceToDelete = ref<Resource | null>(null);
const deleteStep = ref<'confirm' | 'final'>('confirm');

// Use categories from store
const categoriesList = computed(() => resourceStore.categories);

// ===== FIXED: Filter resources by selected category with proper type conversion =====
const filteredResources = computed(() => {
  // Get resources from store
  let filtered = resourceStore.resources;
  
  // Filter by search query
  if (searchQuery.value) {
    filtered = filtered.filter(resource => 
      resource.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }
  
  // Filter by selected category - Convert both to number for safe comparison
  if (selectedCategory.value) {
    const selectedCatId = Number(selectedCategory.value);
    filtered = filtered.filter(resource => {
      const resourceCatId = Number(resource.category_id);
      return resourceCatId === selectedCatId;
    });
  }
  
  return filtered;
});

// Helper Functions
const getImageUrl = (resource: Resource): string => {
    // Now use resource.images
    if (resource.images && resource.images.length > 0) {
        const filePath = resource.images[0].file_path;
        
        // This is the correct, host-accessible URL format already returned by the API Model (ResourceImage)
        return filePath.startsWith('http') ? filePath : `${STORAGE_URL_ROOT}/${filePath}`;
    }
    
    return 'https://via.placeholder.com/300x180?text=No+Image';
};

const getCategoryName = (categoryId: number): string => {
  const category = resourceStore.categories.find(c => c.id === categoryId);
  return category ? category.name : 'Unknown';
};

const getStatusClass = (status: string): string => {
    switch (status) {
        case 'Active':
            return 'bg-success';
        case 'Inactive':
            return 'bg-secondary';
        case 'Maintenance':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
};

// API Calls
const fetchResources = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    
    try {
        const token = getAuthToken();
        const response = await axios.get(`${API_BASE_URL}/resources`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });
        
        resources.value = response.data.resources || response.data;
        console.log('Resources loaded:', resources.value.length);
    } catch (error: any) {
        console.error('Error fetching resources:', error);
        if (error.response?.status === 401) {
            errorMessage.value = 'Authentication required. Please login again.';
            setTimeout(() => router.push('/login'), 2000);
        } else {
            errorMessage.value = 'Failed to load resources. Please try again.';
        }
    } finally {
        isLoading.value = false;
    }
};

// Fetch categories function
const fetchCategories = async () => {
    try {
        const token = getAuthToken();
        if (!token) {
            console.error('No auth token found');
            return;
        }
        
        const response = await axios.get(`${API_BASE_URL}/categories`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });
        
        // Store in local state and also update the store if needed
        const fetchedCategories = response.data.categories || response.data;
        categories.value = fetchedCategories;
        
        // Update store if the store doesn't have categories
        if (resourceStore.categories.length === 0) {
            // If your store has a method to set categories, use it
            // resourceStore.setCategories(fetchedCategories);
        }
        
        console.log('Categories loaded:', fetchedCategories.length);
    } catch (error) {
        console.error('Error fetching categories:', error);
    }
};

const toggleResourceStatus = async (id: number) => {
  const resource = resourceStore.resources.find(r => r.id === id);
  if (!resource) return;
  const newStatus = resource.status === 'Active' ? 'Inactive' : 'Active';

  try {
    const token = getAuthToken();
    await axios.put(`${API_BASE_URL}/resources/${id}`, { status: newStatus }, {
      headers: { Authorization: `Bearer ${token}` }
    });
    // Update the store directly - UI updates instantly
    resourceStore.updateStatus(id, newStatus);
  } catch (error) {
    errorMessage.value = 'Failed to update resource status.';
  }
};

const handleDeleteResource = async () => {
  if (!resourceToDelete.value) return;
  isDeleting.value = true;
  try {
    const token = getAuthToken();
    await axios.delete(`${API_BASE_URL}/resources/${resourceToDelete.value.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    // Remove from store instantly
    resourceStore.removeResource(resourceToDelete.value.id);
    handleCancelDeletion();
  } catch (error) {
    errorMessage.value = 'Failed to delete resource.';
  } finally {
    isDeleting.value = false;
  }
};

// Navigation Handlers

const navigateToSingleresource = (id: number) => {
    router.push(`/admin/resource/${id}`);
};

const navigateToAdd_Custom = () => {
    showAddModal.value = false;
    router.push('/admin/add-resource'); 
};

const openTemplateSelectionModal = () => {
  showAddModal.value = false;
  router.push('/admin/use-template')
};

const navigateToEditResource = (id: number) => {
    router.push({ path: '/admin/add-resource', query: { id: id, mode: 'edit' } });
};

const handleReserveClick = (id: number) => {
    router.push({ path: '/admin/single-resource-booking', query: { resourceId: id } });
};

// Delete Modal Handlers
const openDeleteResourceConfirmation = (resource: Resource) => {
    resourceToDelete.value = resource;
    deleteStep.value = 'confirm';
    showDeleteConfirmation.value = true;
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final';
};

const handleCancelDeletion = () => {
    showDeleteConfirmation.value = false;
    resourceToDelete.value = null;
    deleteStep.value = 'confirm';
    isDeleting.value = false;
};

// Initialization
onMounted(async () => {
    // 1. First fetch categories
    await fetchCategories();
    
    // 2. Then fetch resources if store is empty
    if (!resourceStore.isLoaded) {
        await resourceStore.fetchAll();
    }
    
    isLoading.value = false;
});
</script>

<style scoped>
/* General Section & Sidebar Layout */
.section {
  animation: fadeIn 0.3s ease;
  margin-left: 260px;
  padding: 20px;
}
@media (max-width: 768px) {
  .section {
    margin-left: 80px;
  }
}

.section-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 24px;
}

/* Button Styling */
.btn-outline-dark-teal {
  --bs-btn-color: #1e4449;
  --bs-btn-border-color: #1e4449;
  --bs-btn-hover-bg: #fcc300;
  --bs-btn-hover-color: #ffffff;
  --bs-btn-hover-border-color: #fcc300;
}
.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}
.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* Resource Card Styling */
.resource-card {
  position: relative;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.resource-card > div:not(.resource-actions) {
  cursor: pointer;
}

.resource-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.resource-image {
  height: 180px;
  overflow: hidden;
  background-color: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
}
.resource-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.resource-body {
  padding: 16px;
}
.resource-body h5 {
  margin-bottom: 8px;
  color: #1e4449;
}

.form-check-input:checked {
  background-color: #fcc300;
  border-color: #fcc300;
}

.btn-reserve-card {
    background-color: #1e4449;
    color: white;
    border-color: #1e4449;
    font-size: 0.8rem;
    padding: 0.25rem 0.6rem;
    line-height: 1;
}
.btn-reserve-card:hover {
    background-color: #fcc300;
    color: #1e4449;
    border-color: #fcc300;
}

/* Action Buttons Overlay */
.resource-actions {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10;
  display: flex;
  gap: 5px;
  opacity: 0;
  transition: opacity 0.2s;
}

.resource-card:hover .resource-actions {
  opacity: 1;
}

.btn-action-edit, .btn-action-delete {
  font-size: 0.8rem;
  padding: 0.3rem 0.5rem;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  background-color: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.btn-action-edit {
  color: #0d6efd;
}
.btn-action-edit:hover {
  background-color: #0d6efd;
  color: white;
}

.btn-action-delete {
  color: #dc3545;
}
.btn-action-delete:hover {
  background-color: #dc3545;
  color: white;
}

/* Modal Styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1050;
  width: 100%;
  height: 100%;
  overflow-x: hidden;
  overflow-y: auto;
  outline: 0;
  opacity: 0;
  transition: opacity 0.15s linear;
}

.modal.show {
  opacity: 1;
}

.modal-dialog {
  position: relative;
  width: auto;
  margin: 0.5rem;
  pointer-events: none;
  transition: transform 0.3s ease-out;
  transform: translate(0, -50px);
}

.modal.show .modal-dialog {
  transform: none;
}

.modal-dialog-centered {
  display: flex;
  align-items: center;
  min-height: calc(100% - 1rem);
}

.modal-content {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  pointer-events: auto;
  background-color: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 0.3rem;
  outline: 0;
}

@media (min-width: 576px) {
    .modal-dialog {
        max-width: 300px;
        margin: 1.75rem auto;
    }
    .modal-dialog-centered {
        min-height: calc(100% - 3.5rem);
    }
}

.modal-dialog.delete-modal-top {
    align-items: flex-start;
    margin-top: 50px;
    height: auto;
}

@media (min-width: 576px) {
    .modal-dialog.delete-modal-top {
        max-width: 500px;
        margin: 1.75rem auto;
    }
}

.bg-warning { background-color: #ffc107 !important; }
.btn-warning {
    color: #212529 !important;
    background-color: #ffc107 !important;
    border-color: #ffc300 !important;
}
.btn-warning:hover {
    background-color: #e0a800 !important;
    border-color: #e0a800 !important;
}

.bg-danger { background-color: #dc3545 !important; }
.btn-danger {
  background-color: #dc3545 !important;
  border-color: #dc3545 !important;
}
.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-close-white {
    filter: invert(1);
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}
</style>