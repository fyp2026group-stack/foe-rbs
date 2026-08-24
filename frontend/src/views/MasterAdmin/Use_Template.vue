<template>
  <Navbar />
  <MasterAdminSidebar />
  <div class="use-template-page">
    <!-- Step 1: Template Selection View -->
    <div v-if="currentView === 'selection'" class="selection-view">
      <!-- Header -->
      <div class="header-section">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title">
              <i class="bi bi-grid-3x3-gap-fill me-2 text-dark-teal"></i>
              Resource Templates
            </h1>
            <p class="text-muted mb-0">Select a template to create a new resource</p>
          </div>
        </div>
      </div>

      <!-- Category Filter -->
      <div class="category-filter mb-4">
        <div class="btn-group" role="group">
          <button 
            v-for="category in categories" 
            :key="category.id"
            class="btn"
            :class="selectedCategoryId === category.id ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
            @click="filterByCategory(category.id)"
          >
            <i :class="getCategoryIcon(category.name)" class="me-2"></i>
            {{ category.name }}
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="search-section mb-4">
        <div class="search-box">
          <div class="input-group">
            <span class="input-group-text bg-white">
              <i class="bi bi-search"></i>
            </span>
            <input 
              type="text" 
              class="form-control" 
              placeholder="Search templates..."
              v-model="searchQuery"
            >
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-dark-teal" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3">Loading templates...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="error-state text-center">
        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
        <p class="text-danger mt-3">{{ errorMessage }}</p>
        <button class="btn btn-primary mt-3" @click="fetchTemplates">
          <i class="bi bi-arrow-repeat me-2"></i>Try Again
        </button>
      </div>

      <!-- Templates Grid -->
      <div v-else-if="filteredTemplates.length > 0" class="templates-grid">
        <div class="row g-4">
          <div 
            v-for="template in filteredTemplates" 
            :key="template.id" 
            class="col-xl-3 col-lg-4 col-md-6"
          >
            <div class="template-card" @click="selectTemplate(template)">
              <div class="status-badge" :class="template.status.toLowerCase()">
                {{ template.status }}
              </div>

              <div class="template-icon-wrapper">
                <div class="template-icon" :class="getCategoryColorClass(template.category_id)">
                  <i :class="getTemplateIcon(template.template_name)"></i>
                </div>
              </div>

              <div class="template-content">
                <h3 class="template-title">{{ template.template_name }}</h3>
                <p class="template-description">{{ template.description || 'No description' }}</p>
                
                <div class="template-meta">
                  <span class="badge bg-light text-dark">
                    <i class="bi bi-list-check me-1"></i>
                    {{ template.fields?.length || 0 }} Fields
                  </span>
                </div>

                <button class="use-template-btn">
                  <i class="bi bi-plus-circle me-2"></i>
                  Use This Template
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No Templates -->
      <div v-else class="empty-state text-center py-5">
        <i class="bi bi-file-earmark-x text-muted" style="font-size: 4rem;"></i>
        <h4 class="mt-3">No Templates Found</h4>
        <p class="text-muted">No templates available in this category.</p>
      </div>
    </div>

    <!-- Step 2: Resource Creation Form -->
    <div v-else-if="currentView === 'form'" class="form-view">
      <div class="form-container">
        <!-- Form Header -->
        <div class="form-header">
          <button class="btn btn-link back-btn" @click="goBackToSelection">
            <i class="bi bi-arrow-left"></i>
            Back to Templates
          </button>
          <h2 class="form-title">
            <i :class="getTemplateIcon(selectedTemplate?.template_name || '')" class="me-2"></i>
            Create Resource from: {{ selectedTemplate?.template_name }}
          </h2>
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i>
          {{ successMessage }}
          <button type="button" class="btn-close" @click="successMessage = ''"></button>
        </div>

        <!-- Error Message -->
        <div v-if="formError" class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          {{ formError }}
          <button type="button" class="btn-close" @click="formError = ''"></button>
        </div>

        <!-- Resource Form -->
        <form @submit.prevent="submitResource" class="resource-form">
          <!-- Basic Resource Information -->
          <div class="form-section">
            <h4 class="section-title">
              <i class="bi bi-info-circle me-2"></i>
              Basic Information
            </h4>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">
                  Resource Name <span class="text-danger">*</span>
                </label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="resourceForm.name"
                  :class="{ 'is-invalid': validationErrors.name }"
                  required
                >
                <div class="invalid-feedback" v-if="validationErrors.name">
                  {{ validationErrors.name }}
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Category</label>
                <input 
                  type="text" 
                  class="form-control" 
                  :value="getCategoryName(selectedTemplate?.category_id || 0)"
                  readonly
                  disabled
                >
              </div>

              <div class="col-md-6">
                <label class="form-label">
                  Location Name <span class="text-danger">*</span>
                </label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="resourceForm.location_name"
                  :class="{ 'is-invalid': validationErrors.location_name }"
                  required
                >
                <div class="invalid-feedback" v-if="validationErrors.location_name">
                  {{ validationErrors.location_name }}
                </div>
              </div>

              <!-- Department -->
              <div class="col-md-6">
                <label class="form-label">Department</label>
                <select class="form-select" v-model="resourceForm.department_id">
                  <option value="">No Department</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                    {{ dept.name }}
                  </option>
                </select>
              </div>

              <!-- Base Price -->
              <div class="col-md-6">
                <label class="form-label">Base Price Per Hour (Rs.) <span class="text-danger">*</span></label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model.number="resourceForm.base_price"
                  min="0"
                  step="0.01"
                  required
                >
              </div>

              <!-- Assigned Admins (Multiple) -->
              <div class="col-md-6">
                <label class="form-label fw-bold">Assign Admins <span class="text-danger">*</span></label>
                <div class="border rounded p-3 bg-white" style="max-height: 150px; overflow-y: auto;">
                  <div v-for="admin in admins" :key="admin.id" class="form-check mb-2">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      :id="'admin_' + admin.id" 
                      :value="admin.id" 
                      v-model="resourceForm.assigned_admin_ids"
                    >
                    <label class="form-check-label small" :for="'admin_' + admin.id">
                      {{ admin.name }} ({{ admin.email }})
                    </label>
                  </div>
                </div>
                <small class="text-muted">Select one or more admins who will confirm bookings for this resource.</small>
              </div>

              <!-- Status -->
              <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select" v-model="resourceForm.status" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Maintenance">Maintenance</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea 
                  class="form-control" 
                  rows="2"
                  v-model="resourceForm.description"
                  placeholder="Optional: Provide a detailed description of the resource"
                ></textarea>
                <div class="form-text text-muted">This field is completely optional. Leave empty if not needed.</div>
              </div>
            </div>
          </div>

          <!-- Template Fields Section -->
          <div v-if="templateFields.length > 0" class="form-section">
            <h4 class="section-title">
              <i class="bi bi-list-task me-2"></i>
              Template Fields
              <small class="text-muted ms-2">({{ selectedTemplate?.template_name }})</small>
            </h4>

            <div class="row g-3">
              <div 
                v-for="(field, index) in templateFields" 
                :key="field.id || index" 
                class="col-md-6"
              >
                <label class="form-label">
                  {{ field.field_name }}
                  <span v-if="field.is_required === 1" class="text-danger">*</span>
                </label>

                <!-- Text Input -->
                <input
                  v-if="field.field_type === 'text'"
                  type="text"
                  class="form-control"
                  v-model="fieldValues[field.field_name]"
                  :required="field.is_required === 1"
                  :placeholder="'Enter ' + field.field_name.toLowerCase()"
                >

                <!-- Number Input -->
                <input
                  v-else-if="field.field_type === 'number'"
                  type="number"
                  class="form-control"
                  v-model="fieldValues[field.field_name]"
                  :required="field.is_required === 1"
                  :placeholder="'Enter ' + field.field_name.toLowerCase()"
                >

                <!-- Textarea -->
                <textarea
                  v-else-if="field.field_type === 'textarea'"
                  class="form-control"
                  rows="2"
                  v-model="fieldValues[field.field_name]"
                  :required="field.is_required === 1"
                  :placeholder="'Enter ' + field.field_name.toLowerCase()"
                ></textarea>

                <!-- Checkbox -->
                <div v-else-if="field.field_type === 'checkbox'" class="form-check mt-2">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    :id="'field-' + index"
                    v-model="fieldValues[field.field_name]"
                  >
                  <label class="form-check-label" :for="'field-' + index">Yes</label>
                </div>

                <!-- Dropdown -->
                <select
                  v-else-if="field.field_type === 'dropdown'"
                  class="form-select"
                  v-model="fieldValues[field.field_name]"
                  :required="field.is_required === 1"
                >
                  <option value="" disabled>Select option</option>
                  <option 
                    v-for="option in getDropdownOptions(field)" 
                    :key="option" 
                    :value="option"
                  >
                    {{ option }}
                  </option>
                </select>

                <!-- Image Upload -->
                <input
                  v-else-if="field.field_type === 'image'"
                  type="file"
                  class="form-control"
                  accept="image/*"
                  @change="handleImageUpload($event, field.field_name)"
                  :required="field.is_required === 1"
                >

                <!-- Date -->
                <input
                  v-else-if="field.field_type === 'date'"
                  type="date"
                  class="form-control"
                  v-model="fieldValues[field.field_name]"
                  :required="field.is_required === 1"
                >

                <!-- Time -->
                <input
                  v-else-if="field.field_type === 'time'"
                  type="time"
                  class="form-control"
                  v-model="fieldValues[field.field_name]"
                  :required="field.is_required === 1"
                >

                <!-- Default -->
                <input
                  v-else
                  type="text"
                  class="form-control"
                  v-model="fieldValues[field.field_name]"
                  :required="field.is_required === 1"
                >

                <small class="text-danger" v-if="validationErrors[field.field_name]">
                  {{ validationErrors[field.field_name] }}
                </small>
              </div>
            </div>
          </div>

          <!-- Availability & Time Slots Section -->
          <div class="form-section">
            <h4 class="section-title">
              <i class="bi bi-calendar-week me-2"></i>
              Availability & Time Slots
              <small class="text-muted ms-2">(Set available days and time slots)</small>
            </h4>

            <div class="availability-matrix border p-3 rounded bg-light">
              <div class="row fw-bold text-muted mb-2 border-bottom pb-2 mx-0 small">
                <div class="col-2">Day</div>
                <div class="col-2 text-center">Available</div>
                <div class="col-5">Time Slots</div>
                <div class="col-3">Actions</div>
              </div>

              <div 
                v-for="(day, dayIndex) in availability" 
                :key="day.day_name"
                class="row align-items-center mb-3 mx-0 border-bottom pb-3"
              >
                <div class="col-2 fw-medium">{{ day.day_name }}</div>
                
                <div class="col-2 text-center">
                  <div class="form-check form-switch d-inline-block">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="day.is_available"
                      @change="handleAvailabilityChange(dayIndex)"
                    >
                  </div>
                </div>

                <div class="col-5">
                  <!-- Time Slots -->
                  <div v-if="day.is_available" class="time-slots-container">
                    <div v-for="(slot, slotIndex) in day.slots" :key="slotIndex" class="row g-2 mb-2 align-items-center">
                      <div class="col-5">
                        <input 
                          type="time" 
                          class="form-control form-control-sm" 
                          v-model="slot.start_time"
                          required
                          @change="validateTimeSlot(dayIndex, slotIndex)"
                        >
                      </div>
                      <div class="col-5">
                        <input 
                          type="time" 
                          class="form-control form-control-sm" 
                          v-model="slot.end_time"
                          required
                          @change="validateTimeSlot(dayIndex, slotIndex)"
                        >
                      </div>
                      <div class="col-2">
                        <button 
                          v-if="day.slots.length > 1"
                          type="button" 
                          class="btn btn-sm btn-outline-danger"
                          @click="removeSlot(dayIndex, slotIndex)"
                          title="Remove this time slot"
                        >
                          <i class="bi bi-x"></i>
                        </button>
                        <span v-else class="text-muted small">Required</span>
                      </div>
                    </div>
                    
                    <!-- Add Slot Button -->
                    <button 
                      type="button" 
                      class="btn btn-sm btn-outline-secondary mt-1"
                      @click="addSlot(dayIndex)"
                    >
                      <i class="bi bi-plus-circle me-1"></i> Add Time Slot
                    </button>
                    
                    <!-- Validation message -->
                    <div v-if="day.slotError" class="text-danger small mt-1">
                      {{ day.slotError }}
                    </div>
                  </div>
                  
                  <div v-else class="text-muted small mt-1">
                    <em>Enable day to add time slots</em>
                  </div>
                </div>

                <div class="col-3">
                  <span v-if="day.is_available && day.slots.length > 0" class="badge bg-info">
                    {{ day.slots.length }} slot(s)
                  </span>
                  <span v-else-if="day.is_available && day.slots.length === 0" class="badge bg-warning">
                    No slots
                  </span>
                </div>
              </div>
            </div>

            <!-- Availability validation summary -->
            <div v-if="hasAvailabilityErrors" class="text-danger small mt-2">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              Please fix time slot errors before saving.
            </div>
          </div>

          <!-- Equipment Section - Added from Add Resource page -->
          <div class="form-section">
            <label class="form-label fw-bold">Custom Equipment/Accessories</label>
            <div class="equipment-list border p-3 rounded">
              <div 
                v-for="(item, index) in equipment" 
                :key="index" 
                class="d-flex align-items-center mb-3 p-2 border-bottom"
              >
                <div class="flex-grow-1 me-3">
                  <input 
                    type="text" 
                    class="form-control form-control-sm mb-2" 
                    v-model="item.equipment_name" 
                    placeholder="Equipment Name (e.g., Projector)"
                  >
                </div>
                
                <div class="me-3" style="width: 100px;">
                  <input 
                    type="number" 
                    class="form-control form-control-sm" 
                    v-model.number="item.quantity" 
                    placeholder="Qty"
                    min="1"
                  >
                </div>
                
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-danger flex-shrink-0" 
                  @click="removeEquipment(index)"
                >
                  <i class="bi bi-x"></i>
                </button>
              </div>
              
              <button 
                type="button" 
                class="btn btn-sm btn-outline-dark-teal mt-2" 
                @click="addEquipment"
              >
                <i class="bi bi-plus-circle me-1"></i> Add Equipment
              </button>
            </div>
          </div>

          <!-- Images Section -->
          <div class="form-section">
            <h4 class="section-title">
              <i class="bi bi-images me-2"></i>
              Resource Images
            </h4>
            
            <input 
              type="file" 
              class="form-control" 
              @change="handleFileUpload" 
              accept="image/*"
              multiple
            >
            <small class="text-muted d-block mt-1">You can upload up to 10 images total</small>
            
            <!-- Image Previews -->
            <div v-if="allImagePreviews.length > 0" class="mt-3">
              <h6>Images:</h6>
              <div class="d-flex flex-wrap gap-2">
                <div v-for="(preview, idx) in allImagePreviews" :key="idx" class="position-relative">
                  <img 
                    :src="preview" 
                    alt="Preview" 
                    class="img-thumbnail" 
                    style="height: 100px; width: 100px; object-fit: cover;"
                  >
                  <button 
                    type="button" 
                    class="btn btn-sm btn-danger position-absolute top-0 end-0" 
                    style="padding: 2px 6px;"
                    @click="removeImage(idx)"
                  >
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="goBackToSelection">
              Cancel
            </button>
            <button type="submit" class="btn btn-success" :disabled="isSubmitting || hasAvailabilityErrors">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-save me-2"></i>
              {{ isSubmitting ? 'Creating...' : 'Create Resource' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';
import { resourceStore } from '../../store/resourceStore';
import { userStore } from '../../store/userStore';

// Router
const router = useRouter();

// Store integration - EXACT same as Add Resource page
const admins = computed(() => userStore.users.filter(u => u.primaryRole && u.primaryRole.toLowerCase() === 'admin'));
const categories = computed(() => resourceStore.categories);
const departments = computed(() => resourceStore.departments);

// View State
const currentView = ref('selection');
const selectedTemplate = ref(null);

// Data State
const templates = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const formError = ref('');
const searchQuery = ref('');
const selectedCategoryId = ref(null);
const validationErrors = ref({});

// Form State
const resourceForm = ref({
  name: '',
  location_name: '',
  department_id: '',
  base_price: null,
  assigned_admin_id: '',
  assigned_admin_ids: [],
  description: '',
  status: 'Active'
});

// Availability data with time slots
const availability = ref([
  { day_name: 'Monday', is_available: false, slots: [], slotError: '' },
  { day_name: 'Tuesday', is_available: false, slots: [], slotError: '' },
  { day_name: 'Wednesday', is_available: false, slots: [], slotError: '' },
  { day_name: 'Thursday', is_available: false, slots: [], slotError: '' },
  { day_name: 'Friday', is_available: false, slots: [], slotError: '' },
  { day_name: 'Saturday', is_available: false, slots: [], slotError: '' },
  { day_name: 'Sunday', is_available: false, slots: [], slotError: '' },
]);

// Equipment data - EXACT same as Add Resource page
const equipment = ref([]);

// Template field values
const fieldValues = ref({});

// Image handling - EXACT same as Add Resource page
const selectedFiles = ref([]);
const imagePreviews = ref([]);
const existingImages = ref([]);
const existingImagePreviews = ref([]);
const imagesToDelete = ref([]);

// API Base URL
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api');
const STORAGE_URL_ROOT = (import.meta.env.VITE_STORAGE_URL || ((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/resources/storage'));

// Computed Properties
const filteredTemplates = computed(() => {
  let filtered = templates.value.filter(t => t.status === 'Active');
  
  if (selectedCategoryId.value) {
    filtered = filtered.filter(t => t.category_id === selectedCategoryId.value);
  }
  
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim();
    filtered = filtered.filter(t => 
      t.template_name.toLowerCase().includes(query) ||
      (t.description && t.description.toLowerCase().includes(query))
    );
  }
  
  return filtered;
});

const templateFields = computed(() => {
  if (!selectedTemplate.value) return [];
  return selectedTemplate.value.fields.sort((a, b) => a.order_index - b.order_index);
});

const hasAvailabilityErrors = computed(() => {
  return availability.value.some(day => 
    day.is_available && day.slotError
  );
});

// Combine all previews
const allImagePreviews = computed(() => {
  return [...existingImagePreviews.value, ...imagePreviews.value];
});

// Helper Functions
const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('token') || 
         localStorage.getItem('access_token');
};

// Get full image URL
const getImageUrl = (filePath) => {
  if (!filePath) return 'https://via.placeholder.com/600x400?text=No+Image';
  return filePath.startsWith('http') ? filePath : `${STORAGE_URL_ROOT}/${filePath}`;
};

// Equipment methods - EXACT same as Add Resource page
const addEquipment = () => {
  equipment.value.push({
    equipment_name: '',
    quantity: 1,
  });
};

const removeEquipment = (index) => {
  equipment.value.splice(index, 1);
};

// Image methods - EXACT same as Add Resource page
const handleFileUpload = (event) => {
  const files = Array.from(event.target.files);
  
  // Check total images limit
  const totalImages = existingImages.value.length + selectedFiles.value.length + files.length;
  if (totalImages > 10) {
    formError.value = 'Maximum 10 images allowed total.';
    return;
  }
  
  files.forEach(file => {
    selectedFiles.value.push(file);
    
    const reader = new FileReader();
    reader.onload = (e) => {
      if (e.target && typeof e.target.result === 'string') {
        imagePreviews.value.push(e.target.result);
      }
    };
    reader.readAsDataURL(file);
  });
  
  // Clear input
  event.target.value = '';
};

const removeImage = (index) => {
  // Check if this is an existing image
  if (index < existingImagePreviews.value.length) {
    // Get the actual image ID from existing images array
    const imageId = existingImages.value[index].id;
    
    // Add to delete list if not already there
    if (!imagesToDelete.value.includes(imageId)) {
      imagesToDelete.value.push(imageId);
    }
    
    // Remove from existing arrays
    existingImages.value.splice(index, 1);
    existingImagePreviews.value.splice(index, 1);
  } else {
    // This is a new image
    const newImageIndex = index - existingImagePreviews.value.length;
    if (newImageIndex >= 0 && newImageIndex < selectedFiles.value.length) {
      selectedFiles.value.splice(newImageIndex, 1);
      imagePreviews.value.splice(newImageIndex, 1);
    }
  }
};

// Availability methods
const addSlot = (dayIndex) => {
  availability.value[dayIndex].slots.push({
    start_time: '',
    end_time: ''
  });
  availability.value[dayIndex].slotError = '';
};

const removeSlot = (dayIndex, slotIndex) => {
  availability.value[dayIndex].slots.splice(slotIndex, 1);
  validateTimeSlotsForDay(dayIndex);
};

const handleAvailabilityChange = (dayIndex) => {
  const day = availability.value[dayIndex];
  if (day.is_available && day.slots.length === 0) {
    addSlot(dayIndex);
  } else if (!day.is_available) {
    day.slots = [];
    day.slotError = '';
  }
};

const validateTimeSlot = (dayIndex, slotIndex) => {
  const slot = availability.value[dayIndex].slots[slotIndex];
  
  if (!slot.start_time || !slot.end_time) {
    availability.value[dayIndex].slotError = 'Both start and end time are required';
    return false;
  }
  
  if (slot.start_time >= slot.end_time) {
    availability.value[dayIndex].slotError = 'End time must be after start time';
    return false;
  }
  
  // Check for overlapping slots
  const slots = availability.value[dayIndex].slots;
  for (let i = 0; i < slots.length; i++) {
    if (i !== slotIndex && slots[i].start_time && slots[i].end_time) {
      if ((slot.start_time >= slots[i].start_time && slot.start_time < slots[i].end_time) ||
          (slot.end_time > slots[i].start_time && slot.end_time <= slots[i].end_time) ||
          (slot.start_time <= slots[i].start_time && slot.end_time >= slots[i].end_time)) {
        availability.value[dayIndex].slotError = 'Time slots cannot overlap';
        return false;
      }
    }
  }
  
  availability.value[dayIndex].slotError = '';
  return true;
};

const validateTimeSlotsForDay = (dayIndex) => {
  const slots = availability.value[dayIndex].slots;
  
  if (slots.length === 0) {
    availability.value[dayIndex].slotError = 'At least one time slot is required';
    return false;
  }
  
  let hasError = false;
  slots.forEach((slot, index) => {
    if (!validateTimeSlot(dayIndex, index)) {
      hasError = true;
    }
  });
  
  if (!hasError) {
    availability.value[dayIndex].slotError = '';
  }
  
  return !hasError;
};

const validateAvailability = () => {
  let isValid = true;
  
  availability.value.forEach((day, dayIndex) => {
    if (day.is_available) {
      if (day.slots.length === 0) {
        availability.value[dayIndex].slotError = 'At least one time slot is required';
        isValid = false;
      } else {
        if (!validateTimeSlotsForDay(dayIndex)) {
          isValid = false;
        }
      }
    }
  });
  
  return isValid;
};

// Get dropdown options from metadata
const getDropdownOptions = (field) => {
  if (field.metadata) {
    try {
      const meta = typeof field.metadata === 'string' ? JSON.parse(field.metadata) : field.metadata;
      return meta.options || [];
    } catch (e) {
      console.error('Error parsing metadata:', e);
      return [];
    }
  }
  return [];
};

// Image upload for template fields
const handleImageUpload = (event, fieldName) => {
  if (event.target.files && event.target.files[0]) {
    fieldValues.value[fieldName] = event.target.files[0];
  }
};

// Prepare form data - EXACT same structure as Add Resource page
const prepareFormData = () => {
  const formData = new FormData();
  
  // Basic resource data
  formData.append('name', resourceForm.value.name);
  formData.append('location_name', resourceForm.value.location_name);
  formData.append('category_id', selectedTemplate.value.category_id.toString());
  
  // Handle department
  if (resourceForm.value.department_id && departments.value.length > 0) {
    const selectedDept = departments.value.find(d => d.id == resourceForm.value.department_id);
    formData.append('department', selectedDept ? selectedDept.name : '');
  } else {
    formData.append('department', '');
  }
  
  // Handle base price
  if (resourceForm.value.base_price === null || resourceForm.value.base_price === undefined || resourceForm.value.base_price === '') {
    formData.append('base_price', '0.00');
  } else {
    const priceValue = parseFloat(resourceForm.value.base_price);
    formData.append('base_price', priceValue.toString());
  }
  
  formData.append('status', resourceForm.value.status);
  
  if (resourceForm.value.assigned_admin_ids && resourceForm.value.assigned_admin_ids.length > 0) {
    resourceForm.value.assigned_admin_ids.forEach((id, index) => {
      formData.append(`assigned_admin_ids[${index}]`, id.toString());
    });
  } else if (resourceForm.value.assigned_admin_id) {
    formData.append('assigned_admin_ids[0]', resourceForm.value.assigned_admin_id.toString());
  }
  
  // Handle description
  if (resourceForm.value.description && resourceForm.value.description.trim() !== '') {
    formData.append('description', String(resourceForm.value.description));
  }
  
  // Add template ID
  formData.append('template_id', selectedTemplate.value.id.toString());
  
  // Add images to delete
  if (imagesToDelete.value.length > 0) {
    imagesToDelete.value.forEach((id, index) => {
      formData.append(`removeImages[${index}]`, id);
    });
  }
  
  // Add new images
  selectedFiles.value.forEach((file, index) => {
    formData.append(`images[${index}]`, file);
  });
  
  // Add equipment
  if (equipment.value.length > 0) {
    equipment.value.forEach((item, index) => {
      if (item.equipment_name && item.equipment_name.trim()) {
        formData.append(`equipment[${index}][equipment_name]`, item.equipment_name);
        formData.append(`equipment[${index}][quantity]`, item.quantity?.toString() || '1');
      }
    });
  }
  
  // Add template fields
  for (const [key, value] of Object.entries(fieldValues.value)) {
    if (value instanceof File) {
      formData.append(`field_${key}`, value, value.name);
    } else if (value !== null && value !== undefined) {
      formData.append(`field_${key}`, String(value));
    }
  }
  
  // Add availability with time slots
  availability.value.forEach((day, dayIndex) => {
    if (day.is_available && day.slots.length > 0) {
      const validSlots = day.slots.filter(slot => 
        slot.start_time && slot.end_time && 
        slot.start_time.trim() && slot.end_time.trim()
      );
      
      if (validSlots.length > 0) {
        formData.append(`availability[${dayIndex}][day_of_week]`, day.day_name);
        formData.append(`availability[${dayIndex}][is_available]`, '1');
        
        validSlots.forEach((slot, slotIndex) => {
          formData.append(`availability[${dayIndex}][slots][${slotIndex}][start_time]`, slot.start_time);
          formData.append(`availability[${dayIndex}][slots][${slotIndex}][end_time]`, slot.end_time);
        });
      }
    }
  });
  
  return formData;
};

// Fetch Data - Using store data like Add Resource page
const fetchDepartments = async () => {
  try {
    const token = getAuthToken();
    if (!token) return;
    
    const response = await axios.get(`${API_BASE_URL}/departments`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    if (response.data && Array.isArray(response.data)) {
      resourceStore.setDepartments(response.data);
    }
    
  } catch (error) {
    console.error('Error fetching departments:', error);
  }
};

const fetchTemplates = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const token = getAuthToken();
    if (!token) {
      errorMessage.value = 'Authentication required. Please login again.';
      return;
    }

    // Load dependencies from stores if not loaded - EXACT same as Add Resource page
    if (!resourceStore.isLoaded) {
      await resourceStore.fetchAll();
    } else {
      if (!resourceStore.departments || resourceStore.departments.length === 0) {
        await fetchDepartments();
      }
    }
    
    if (!userStore.isLoaded) {
      await userStore.fetchUsers();
    }

    const response = await axios.get(`${API_BASE_URL}/resource-templates`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    templates.value = response.data || [];

  } catch (error) {
    console.error('Fetch templates error:', error);
    errorMessage.value = error.response?.data?.message || 'Failed to load templates.';
  } finally {
    isLoading.value = false;
  }
};

// Template Selection
const selectTemplate = (template) => {
  selectedTemplate.value = template;
  
  // Reset form
  resourceForm.value = {
    name: '',
    location_name: '',
    department_id: '',
    base_price: null,
    assigned_admin_id: '',
    assigned_admin_ids: [],
    description: '',
    status: 'Active'
  };
  
  // Reset availability
  availability.value = [
    { day_name: 'Monday', is_available: false, slots: [], slotError: '' },
    { day_name: 'Tuesday', is_available: false, slots: [], slotError: '' },
    { day_name: 'Wednesday', is_available: false, slots: [], slotError: '' },
    { day_name: 'Thursday', is_available: false, slots: [], slotError: '' },
    { day_name: 'Friday', is_available: false, slots: [], slotError: '' },
    { day_name: 'Saturday', is_available: false, slots: [], slotError: '' },
    { day_name: 'Sunday', is_available: false, slots: [], slotError: '' },
  ];
  
  // Reset equipment
  equipment.value = [];
  addEquipment(); // Add one empty equipment row
  
  // Reset images
  selectedFiles.value = [];
  imagePreviews.value = [];
  existingImages.value = [];
  existingImagePreviews.value = [];
  imagesToDelete.value = [];
  
  // Initialize field values
  fieldValues.value = {};
  template.fields.forEach(field => {
    if (field.field_type === 'checkbox') {
      fieldValues.value[field.field_name] = false;
    } else if (field.field_type === 'dropdown') {
      fieldValues.value[field.field_name] = '';
    } else {
      fieldValues.value[field.field_name] = '';
    }
  });
  
  // Reset validation
  validationErrors.value = {};
  formError.value = '';
  successMessage.value = '';
  
  currentView.value = 'form';
};

const goBackToSelection = () => {
  currentView.value = 'selection';
  selectedTemplate.value = null;
};

// Submit Resource
const submitResource = async () => {
  formError.value = '';
  successMessage.value = '';
  validationErrors.value = {};

  // Validate required fields
  if (!resourceForm.value.name) {
    formError.value = 'Resource name is required.';
    return;
  }

  if (!resourceForm.value.location_name) {
    formError.value = 'Location name is required.';
    return;
  }

  // Validate template required fields
  for (const field of templateFields.value) {
    if (field.is_required === 1) {
      const value = fieldValues.value[field.field_name];
      if (value === undefined || value === null || value === '' || value === false) {
        formError.value = `"${field.field_name}" is required.`;
        return;
      }
    }
  }

  // Validate availability
  if (!validateAvailability()) {
    formError.value = 'Please fix time slot errors before saving.';
    return;
  }

  if (!resourceForm.value.assigned_admin_ids || resourceForm.value.assigned_admin_ids.length === 0) {
    formError.value = 'Please select at least one assigned admin.';
    return;
  }

  isSubmitting.value = true;

  try {
    const token = getAuthToken();
    if (!token) {
      formError.value = 'Authentication required. Please login again.';
      return;
    }

    const formData = prepareFormData();
    
    // Log FormData contents for debugging
    console.log('=== FormData Contents ===');
    for (let pair of formData.entries()) {
      console.log(pair[0] + ': ' + pair[1]);
    }
    console.log('=== End FormData ===');

    const response = await axios.post(`${API_BASE_URL}/resources`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    successMessage.value = 'Resource created successfully!';
    
    if (response.data.resource || response.data) {
      resourceStore.addResource(response.data.resource || response.data);
    }
    
    // Auto navigate to resources page after 2 seconds
    setTimeout(() => {
      router.push('/master-admin/resource');
    }, 2000);

  } catch (error) {
    console.error('Submit resource error:', error);
    
    if (error.response?.status === 401) {
      formError.value = 'Authentication required. Please login again.';
    } else if (error.response?.data?.errors) {
      const errors = error.response.data.errors;
      console.error('Validation errors:', errors);
      validationErrors.value = errors;
      formError.value = Object.values(errors).flat().join(', ');
    } else if (error.response?.data?.message) {
      formError.value = error.response.data.message;
    } else {
      formError.value = 'Failed to create resource. Please try again.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

// Filter and UI Helpers
const filterByCategory = (categoryId) => {
  selectedCategoryId.value = categoryId;
};

const getCategoryName = (categoryId) => {
  const category = categories.value.find(c => c.id === categoryId);
  return category ? category.name : 'Uncategorized';
};

const getCategoryIcon = (categoryName) => {
  const name = categoryName.toLowerCase();
  if (name.includes('academic')) return 'bi bi-book';
  if (name.includes('it')) return 'bi bi-laptop';
  if (name.includes('medical')) return 'bi bi-hospital';
  if (name.includes('sport')) return 'bi bi-trophy';
  if (name.includes('cultural')) return 'bi bi-palette';
  return 'bi bi-folder';
};

const getCategoryColorClass = (categoryId) => {
  const colors = {
    1: 'academic-color',
    2: 'it-color',
    3: 'medical-color',
    4: 'sports-color',
    5: 'cultural-color'
  };
  return colors[categoryId] || 'default-color';
};

const getTemplateIcon = (templateName) => {
  const name = templateName.toLowerCase();
  if (name.includes('lecture') || name.includes('seminar')) return 'bi bi-easel';
  if (name.includes('study')) return 'bi bi-person-workspace';
  if (name.includes('lab')) return name.includes('computer') ? 'bi bi-pc-display' : 'bi bi-flask';
  if (name.includes('data')) return 'bi bi-server';
  if (name.includes('workshop')) return 'bi bi-tools';
  if (name.includes('editing')) return 'bi bi-film';
  if (name.includes('procedure')) return 'bi bi-clipboard-pulse';
  if (name.includes('radiology')) return 'bi bi-x-diamond';
  if (name.includes('operating')) return 'bi bi-heart-pulse';
  if (name.includes('court') || name.includes('field')) return 'bi bi-dribbble';
  if (name.includes('gym')) return 'bi bi-heart-fill';
  if (name.includes('pool')) return 'bi bi-water';
  return 'bi bi-file-text';
};

// Initialize
onMounted(() => {
  fetchTemplates();
});
</script>

<style scoped>
/* Main Layout */
.use-template-page {
  margin-left: 260px;
  padding: 30px;
  background: #f8f9fa;
  min-height: 100vh;
  animation: fadeIn 0.3s ease;
}

@media (max-width: 768px) {
  .use-template-page {
    margin-left: 80px;
    padding: 20px;
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Header */
.header-section {
  margin-bottom: 30px;
}

.page-title {
  font-size: 2rem;
  font-weight: 600;
  color: #1e4449;
  margin-bottom: 0.5rem;
}

.text-dark-teal {
  color: #1e4449;
}

/* Category Filter */
.category-filter {
  background: white;
  padding: 15px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.btn-group {
  flex-wrap: wrap;
  gap: 10px;
}

.btn-group .btn {
  border-radius: 8px !important;
  padding: 8px 20px;
  font-weight: 500;
}

.btn-dark-teal {
  background-color: #1e4449;
  color: white;
  border: 1px solid #1e4449;
}

.btn-dark-teal:hover {
  background-color: #153237;
  color: white;
}

.btn-outline-dark-teal {
  color: #1e4449;
  border: 1px solid #1e4449;
  background: transparent;
}

.btn-outline-dark-teal:hover {
  background-color: #1e4449;
  color: white;
}

/* Search Section */
.search-section {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Template Card */
.template-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  position: relative;
  cursor: pointer;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.template-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.status-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  z-index: 1;
}

.status-badge.active {
  background: #d4edda;
  color: #155724;
}

.status-badge.inactive {
  background: #f8d7da;
  color: #721c24;
}

.template-icon-wrapper {
  padding: 30px 20px 20px;
  display: flex;
  justify-content: center;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.template-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  color: white;
}

.academic-color { background: #4B6E8C; }
.it-color { background: #2C3E50; }
.medical-color { background: #C44545; }
.sports-color { background: #27AE60; }
.cultural-color { background: #F39C12; }
.default-color { background: #95A5A6; }

.template-content {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.template-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 10px;
}

.template-description {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-bottom: 15px;
  flex: 1;
}

.use-template-btn {
  width: 100%;
  padding: 10px;
  background: #1e4449;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  margin-top: 15px;
  transition: all 0.3s;
}

.use-template-btn:hover {
  background: #153237;
}

/* Form View */
.form-view {
  max-width: 1000px;
  margin: 0 auto;
}

.form-container {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.form-header {
  padding: 20px 30px;
  background: linear-gradient(135deg, #1e4449 0%, #153237 100%);
  color: white;
}

.back-btn {
  color: white !important;
  text-decoration: none;
  padding: 0;
  margin-bottom: 15px;
}

.back-btn:hover {
  color: #fcc300 !important;
}

.form-title {
  font-size: 1.5rem;
  margin: 0;
}

/* Form Sections */
.form-section {
  padding: 30px;
  border-bottom: 1px solid #e9ecef;
}

.form-section:last-child {
  border-bottom: none;
}

.section-title {
  color: #1e4449;
  font-size: 1.25rem;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #fcc300;
}

/* Equipment List */
.equipment-list {
  max-height: 350px;
  overflow-y: auto;
  background-color: #f8f9fa;
}

/* Availability Matrix */
.availability-matrix {
  background-color: #fafafa !important;
}

.availability-matrix .form-check-input {
  margin-top: 0.2rem;
  cursor: pointer;
}

.availability-matrix input[type="time"]:disabled {
  background-color: #e9ecef;
  opacity: 0.8;
}

.time-slots-container {
  min-height: 60px;
}

.time-slots-container .row {
  min-height: 38px;
}

/* Form Actions */
.form-actions {
  padding: 30px;
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  background: #f8f9fa;
}

.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
  padding: 10px 30px;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.btn-success:disabled {
  background-color: #6c757d;
  border-color: #6c757d;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 10px 30px;
}

/* Loading States */
.spinner-border.text-dark-teal {
  color: #1e4449 !important;
  width: 3rem;
  height: 3rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}

/* Badges */
.badge {
  font-size: 0.75rem;
  padding: 0.35em 0.65em;
}

.badge.bg-info {
  background-color: #0dcaf0 !important;
}

.badge.bg-warning {
  background-color: #ffc107 !important;
  color: #212529;
}

/* Form Check */
.form-check-input:checked {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.form-check-input:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.25rem rgba(75, 182, 109, 0.25);
}

/* Image Preview */
.img-thumbnail {
  object-fit: cover;
  max-width: 100%;
}

.btn-outline-danger {
  --bs-btn-color: #dc3545;
  --bs-btn-border-color: #dc3545;
  --bs-btn-hover-bg: #dc3545;
  --bs-btn-hover-color: white;
}

/* Alerts */
.alert {
  border-radius: 0.375rem;
  margin: 20px 30px 0;
}

.alert-success {
  background-color: #d1e7dd;
  border-color: #badbcc;
  color: #0f5132;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c2c7;
  color: #842029;
}

/* Validation */
.text-danger {
  color: #dc3545 !important;
}

.is-invalid {
  border-color: #dc3545 !important;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
}

/* Responsive */
@media (max-width: 768px) {
  .form-section {
    padding: 20px;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .form-actions button {
    width: 100%;
  }
  
  .availability-matrix .row {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .availability-matrix .col-2,
  .availability-matrix .col-5,
  .availability-matrix .col-3 {
    width: 100%;
    margin-bottom: 10px;
  }
}
</style>