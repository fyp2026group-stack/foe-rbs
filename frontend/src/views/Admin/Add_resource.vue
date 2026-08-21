<template>
   <navbar/>
   <admin_-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">{{ isEditMode ? 'Edit Resource' : 'Add New Resource' }}</h2>
    </div>

    <div class="card p-4 shadow-sm">
      <form @submit.prevent="isEditMode ? handleUpdate() : handleSave()">
        <div class="row g-4">
          <!-- Resource Name -->
          <div class="col-md-6">
            <label for="resourceName" class="form-label fw-bold">Resource Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="resourceName" 
              v-model="resource.name" 
              required
              placeholder="e.g., Conference Room A 301"
            >
          </div>

          <!-- Location Name -->
          <div class="col-md-6">
            <label for="locationName" class="form-label fw-bold">Location Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="locationName" 
              v-model="resource.location_name"
              placeholder="e.g., Building C, Floor 2"
              required
            >
          </div>

          <!-- Category -->
          <div class="col-md-6">
            <label for="resourceCategory" class="form-label fw-bold">Resource Category <span class="text-danger">*</span></label>
            <select class="form-select" id="resourceCategory" v-model="resource.category_id" required>
              <option value="" disabled>Select a Category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <!-- 🔥 FIXED: Department - Auto-filled from logged-in admin, READONLY -->
          <div class="col-md-6">
            <label for="resourceDepartment" class="form-label fw-bold">Department <span class="text-danger">*</span></label>
            <input 
              type="text" 
              class="form-control" 
              id="resourceDepartment" 
              :value="adminDepartmentName"
              readonly
              disabled
              style="background-color: #f5f5f5; cursor: not-allowed;"
            >
            <small class="text-muted d-block mt-1">
              <i class="bi bi-info-circle me-1"></i> 
               Cannot be changed.
            </small>
          </div>

          <!-- Base Price -->
          <div class="col-md-6">
            <label for="resourcePrice" class="form-label fw-bold">Resource Base Price Per Hour (Rs.) <span class="text-danger">*</span></label>
            <input 
              type="number" 
              class="form-control" 
              id="resourcePrice" 
              v-model.number="resource.base_price"
              placeholder="e.g., 500.00 Rs."
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
                  v-model="resource.assigned_admin_ids"
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
            <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="status" v-model="resource.status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>

          <!-- Availability -->
          <div class="col-12">
            <label class="form-label fw-bold">Availability & Time Slots <span class="text-danger">*</span></label>
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
          </div>

          <!-- Equipment -->
          <div class="col-12">
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

          <!-- Images -->
          <div class="col-12">
            <label for="resourcePhotoFile" class="form-label fw-bold">Upload Photos</label>
            <input 
              type="file" 
              class="form-control" 
              id="resourcePhotoFile" 
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
                  <small class="text-muted d-block text-center mt-1">
                    {{ idx < existingImagesCount ? 'Existing' : 'New' }}
                  </small>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Description -->
          <div class="col-12">
            <label for="resourceDescription" class="form-label fw-bold">Description</label>
            <textarea 
              class="form-control" 
              id="resourceDescription" 
              rows="4" 
              v-model="resource.description"
              placeholder="Optional: Provide a detailed description of the resource"
            ></textarea>
            <div class="form-text text-muted">This field is completely optional. Leave empty if not needed.</div>
          </div>
        </div>

        <!-- Error/Success Messages -->
        <div v-if="errorMessage" class="alert alert-danger mt-3">
          {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert alert-success mt-3">
          {{ successMessage }}
        </div>

        <!-- Buttons -->
        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <button type="button" class="btn btn-secondary" @click="router.push('/admin/resource')">
            <i class="bi bi-x-circle me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-success" :disabled="isSubmitting || hasAvailabilityErrors">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-save me-1"></i> 
            {{ isEditMode ? 'Update Resource' : 'Save Resource' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import { resourceStore } from '../../store/resourceStore';
import { userStore } from '../../store/userStore';

import Navbar from '../../components/Navbar.vue';
import Admin_Sidebar from '../../components/Sidebar/Admin_Sidebar.vue';

export default {
  name: 'AddResource',
  components: {
    Navbar,
    Admin_Sidebar
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    const API_BASE_URL = 'http://localhost:8000/api';
    const STORAGE_URL_ROOT = 'http://localhost:8000/api/resources/storage';

    // 🔥 Get logged-in admin's department from localStorage
    const getLoggedInAdminDepartment = () => {
      try {
        // Try to get from user object in localStorage first
        const userStr = localStorage.getItem('user');
        if (userStr) {
          const user = JSON.parse(userStr);
          if (user.department) {
            return user.department;
          }
        }
      } catch (e) {
        console.error('Error parsing user from localStorage:', e);
      }
      
      // Fallback: Try direct department from localStorage
      const department = localStorage.getItem('department') || 
                        localStorage.getItem('userDepartment') || 
                        localStorage.getItem('adminDepartment') || 
                        '';
      return department;
    };
    
    // 🔥 Admin's department name (auto-filled, readonly)
    const adminDepartmentName = ref(getLoggedInAdminDepartment());

    // Resource data
    const resource = ref({
      name: '',
      location_name: '',
      category_id: '',
      department_id: '', 
      department_name: adminDepartmentName.value,
      base_price: null,
      assigned_admin_id: '',
      assigned_admin_ids: [],
      description: '',
      status: 'Active',
    });

    // Availability data
    const availability = ref([
      { day_name: 'Monday', is_available: false, slots: [], slotError: '' },
      { day_name: 'Tuesday', is_available: false, slots: [], slotError: '' },
      { day_name: 'Wednesday', is_available: false, slots: [], slotError: '' },
      { day_name: 'Thursday', is_available: false, slots: [], slotError: '' },
      { day_name: 'Friday', is_available: false, slots: [], slotError: '' },
      { day_name: 'Saturday', is_available: false, slots: [], slotError: '' },
      { day_name: 'Sunday', is_available: false, slots: [], slotError: '' },
    ]);

    // Equipment data
    const equipment = ref([]);

    // Store integration
    const admins = computed(() => userStore.users.filter(u => u.primaryRole && u.primaryRole.toLowerCase() === 'admin'));
    const categories = computed(() => resourceStore.categories);
    const departments = computed(() => resourceStore.departments);
    
    // Other state
    const selectedFiles = ref([]);
    const imagePreviews = ref([]);
    
    // For existing images
    const existingImages = ref([]);
    const existingImagePreviews = ref([]);
    const imagesToDelete = ref([]);
    
    const isSubmitting = ref(false);
    const errorMessage = ref('');
    const successMessage = ref('');

    const isEditMode = computed(() => route.query.mode === 'edit' && !!route.query.id);
    
    // Combine all previews
    const allImagePreviews = computed(() => {
      return [...existingImagePreviews.value, ...imagePreviews.value];
    });
    
    // Count of existing images
    const existingImagesCount = computed(() => existingImagePreviews.value.length);
    
    // Check if there are availability validation errors
    const hasAvailabilityErrors = computed(() => {
      const hasErrors = availability.value.some(day => 
        day.is_available && day.slotError
      );
      const noneSelected = !availability.value.some(day => day.is_available);
      return hasErrors || noneSelected;
    });

    // Get auth token
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

    // Equipment methods
    const addEquipment = () => {
      equipment.value.push({
        equipment_name: '',
        quantity: 1,
      });
    };

    const removeEquipment = (index) => {
      equipment.value.splice(index, 1);
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

    // Validate time slot
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

    // Validate all availability before submission
    const validateAvailability = () => {
      let isValid = true;
      let atLeastOneDayAvailable = false;
      
      availability.value.forEach((day, dayIndex) => {
        if (day.is_available) {
          atLeastOneDayAvailable = true;
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

      if (!atLeastOneDayAvailable) {
        errorMessage.value = 'At least one day must be marked as available with time slots.';
        isValid = false;
      }
      
      return isValid;
    };

    // Image methods
    const handleFileUpload = (event) => {
      const files = Array.from(event.target.files);
      
      const totalImages = existingImages.value.length + selectedFiles.value.length + files.length;
      if (totalImages > 10) {
        errorMessage.value = 'Maximum 10 images allowed total.';
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
      
      event.target.value = '';
    };

    const removeImage = (index) => {
      if (index < existingImagePreviews.value.length) {
        const imageId = existingImages.value[index].id;
        if (!imagesToDelete.value.includes(imageId)) {
          imagesToDelete.value.push(imageId);
        }
        existingImages.value.splice(index, 1);
        existingImagePreviews.value.splice(index, 1);
      } else {
        const newImageIndex = index - existingImagePreviews.value.length;
        if (newImageIndex >= 0 && newImageIndex < selectedFiles.value.length) {
          selectedFiles.value.splice(newImageIndex, 1);
          imagePreviews.value.splice(newImageIndex, 1);
        }
      }
    };

    // 🔥 FIXED: Prepare form data with department name from logged-in admin
    const prepareFormData = () => {
      const formData = new FormData();
      
      formData.append('name', resource.value.name);
      formData.append('location_name', resource.value.location_name);
      formData.append('category_id', resource.value.category_id.toString());
      
      // 🔥 Use admin's department name (readonly)
      formData.append('department', adminDepartmentName.value);
      
      if (resource.value.base_price === null || resource.value.base_price === undefined || resource.value.base_price === '') {
        formData.append('base_price', '0.00');
      } else {
        const priceValue = parseFloat(resource.value.base_price);
        formData.append('base_price', priceValue.toString());
      }
      
      formData.append('status', resource.value.status);
      
      if (resource.value.assigned_admin_ids && resource.value.assigned_admin_ids.length > 0) {
        resource.value.assigned_admin_ids.forEach((id, index) => {
          formData.append(`assigned_admin_ids[${index}]`, id.toString());
        });
      } else if (resource.value.assigned_admin_id) {
        formData.append('assigned_admin_ids[0]', resource.value.assigned_admin_id.toString());
      }
      
      if (resource.value.description && resource.value.description.trim() !== '') {
        formData.append('description', String(resource.value.description));
      }
      
      if (imagesToDelete.value.length > 0) {
        imagesToDelete.value.forEach((id, index) => {
          formData.append(`removeImages[${index}]`, id);
        });
      }
      
      selectedFiles.value.forEach((file, index) => {
        formData.append(`images[${index}]`, file);
      });
      
      if (equipment.value.length > 0) {
        equipment.value.forEach((item, index) => {
          if (item.equipment_name && item.equipment_name.trim()) {
            formData.append(`equipment[${index}][equipment_name]`, item.equipment_name);
            formData.append(`equipment[${index}][quantity]`, item.quantity?.toString() || '1');
          }
        });
      }
      
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

    // Save resource
    const handleSave = async () => {
      errorMessage.value = '';
      successMessage.value = '';
      
      if (!validateAvailability()) {
        errorMessage.value = 'Please fix time slot errors before saving.';
        return;
      }

      if (!resource.value.assigned_admin_ids || resource.value.assigned_admin_ids.length === 0) {
        errorMessage.value = 'Please select at least one assigned admin.';
        return;
      }
      
      isSubmitting.value = true;
      
      try {
        const formData = prepareFormData();
        const token = getAuthToken();
        
        if (!token) {
          throw new Error('Authentication required. Please login again.');
        }
        
        console.log('Saving resource with department:', adminDepartmentName.value);
        
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
        
        setTimeout(() => {
          router.push('/admin/resource');
        }, 2000);
        
      } catch (error) {
        console.error('Error creating resource:', error);
        
        if (error.response?.status === 401) {
          errorMessage.value = 'Authentication required. Please login again.';
        } else if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          errorMessage.value = Object.values(errors).flat().join(', ');
        } else if (error.response?.data?.message) {
          errorMessage.value = error.response.data.message;
        } else {
          errorMessage.value = 'Failed to create resource. Please try again.';
        }
      } finally {
        isSubmitting.value = false;
      }
    };

    // Update resource
    const handleUpdate = async () => {
      errorMessage.value = '';
      successMessage.value = '';
      
      if (!validateAvailability()) {
        errorMessage.value = 'Please fix time slot errors before updating.';
        return;
      }

      if (!resource.value.assigned_admin_ids || resource.value.assigned_admin_ids.length === 0) {
        errorMessage.value = 'Please select at least one assigned admin.';
        return;
      }
      
      isSubmitting.value = true;
      
      try {
        const formData = prepareFormData();
        const idToUpdate = route.query.id;
        const token = getAuthToken();
        
        if (!token) {
          throw new Error('Authentication required. Please login again.');
        }
        
        formData.append('_method', 'PUT');
        
        const response = await axios.post(`${API_BASE_URL}/resources/${idToUpdate}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });
        
        successMessage.value = 'Resource updated successfully!';
        
        if (response.data.resource || response.data) {
          resourceStore.updateResource(response.data.resource || response.data);
        }
        
        setTimeout(() => {
          router.push('/admin/resource');
        }, 2000);
        
      } catch (error) {
        console.error('Error updating resource:', error);
        
        if (error.response?.status === 401) {
          errorMessage.value = 'Authentication required. Please login again.';
        } else if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          errorMessage.value = Object.values(errors).flat().join(', ');
        } else if (error.response?.data?.message) {
          errorMessage.value = error.response.data.message;
        } else {
          errorMessage.value = 'Failed to update resource. Please try again.';
        }
      } finally {
        isSubmitting.value = false;
      }
    };

    // Load resource for edit
    const loadResourceForEdit = async (resourceId) => {
      try {
        const token = getAuthToken();
        if (!token) {
          errorMessage.value = 'Authentication required. Please login again.';
          return;
        }
        
        const response = await axios.get(`${API_BASE_URL}/resources/${resourceId}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          }
        });
        
        const resourceData = response.data;
        
        resource.value = {
          name: resourceData.name || '',
          location_name: resourceData.location_name || '',
          category_id: resourceData.category_id || '',
          department_id: '',
          department_name: resourceData.department || adminDepartmentName.value,
          base_price: resourceData.base_price !== null && resourceData.base_price !== undefined 
            ? parseFloat(resourceData.base_price) 
            : null,
          assigned_admin_id: resourceData.assigned_admin_id ? resourceData.assigned_admin_id.toString() : '',
          assigned_admin_ids: resourceData.assigned_admin_ids 
            ? resourceData.assigned_admin_ids.map(id => parseInt(id))
            : (resourceData.assigned_admin_id ? [parseInt(resourceData.assigned_admin_id)] : []),
          description: resourceData.description || '',
          status: resourceData.status || 'Active',
        };
        
        if (resourceData.equipment && Array.isArray(resourceData.equipment)) {
          equipment.value = resourceData.equipment.map(item => ({
            equipment_name: item.equipment_name || '',
            quantity: item.quantity || 1,
          }));
        } else {
          equipment.value = [];
        }
        
        if (resourceData.images && Array.isArray(resourceData.images)) {
          existingImages.value = resourceData.images;
          existingImagePreviews.value = resourceData.images.map(img => getImageUrl(img.file_path));
        }
        
        if (resourceData.availability && Array.isArray(resourceData.availability)) {
          availability.value = [
            { day_name: 'Monday', is_available: false, slots: [], slotError: '' },
            { day_name: 'Tuesday', is_available: false, slots: [], slotError: '' },
            { day_name: 'Wednesday', is_available: false, slots: [], slotError: '' },
            { day_name: 'Thursday', is_available: false, slots: [], slotError: '' },
            { day_name: 'Friday', is_available: false, slots: [], slotError: '' },
            { day_name: 'Saturday', is_available: false, slots: [], slotError: '' },
            { day_name: 'Sunday', is_available: false, slots: [], slotError: '' },
          ];
          
          resourceData.availability.forEach(savedDay => {
            const dayIndex = availability.value.findIndex(
              day => day.day_name === savedDay.day_name
            );
            
            if (dayIndex !== -1) {
              availability.value[dayIndex].is_available = savedDay.is_available;
              
              if (savedDay.slots && Array.isArray(savedDay.slots)) {
                availability.value[dayIndex].slots = savedDay.slots.map(slot => ({
                  start_time: slot.start_time ? slot.start_time.substring(0, 5) : '',
                  end_time: slot.end_time ? slot.end_time.substring(0, 5) : ''
                }));
              }
            }
          });
        }
        
      } catch (error) {
        console.error('Error loading resource:', error);
        errorMessage.value = 'Failed to load resource data.';
      }
    };

    // Initialize
    onMounted(async () => {
      if (!resourceStore.isLoaded) {
        await resourceStore.fetchAll();
      }
      
      if (!userStore.isLoaded) {
        await userStore.fetchUsers();
      }
      
      console.log('Logged-in Admin Department:', adminDepartmentName.value);
      
      if (isEditMode.value) {
        await loadResourceForEdit(route.query.id);
      } else {
        addEquipment();
      }
    });

    return {
      resource,
      availability,
      equipment,
      selectedFiles,
      imagePreviews,
      allImagePreviews,
      existingImagesCount,
      isSubmitting,
      errorMessage,
      successMessage,
      admins,
      categories,
      departments,
      isEditMode,
      hasAvailabilityErrors,
      adminDepartmentName,
      addEquipment,
      removeEquipment,
      addSlot,
      removeSlot,
      handleAvailabilityChange,
      validateTimeSlot,
      handleFileUpload,
      removeImage,
      handleSave,
      handleUpdate,
      router
    };
  }
};
</script>

<style scoped>
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

.section-subtitle {
  color: #1e4449;
  font-size: 1.1rem;
}

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

.btn-success:disabled {
  background-color: #6c757d;
  border-color: #6c757d;
  cursor: not-allowed;
}

.img-thumbnail {
  object-fit: cover;
  max-width: 100%;
}

.card {
  align-items: flex-start;
}

.equipment-list {
  max-height: 350px;
  overflow-y: auto;
  background-color: #f8f9fa;
}

.btn-outline-danger {
  --bs-btn-color: #dc3545;
  --bs-btn-border-color: #dc3545;
  --bs-btn-hover-bg: #dc3545;
  --bs-btn-hover-color: white;
}

.availability-matrix {
  background-color: #fafafa !important;
}

.availability-matrix .form-check-input {
  margin-top: 0.2rem;
  cursor: pointer;
}

.time-slots-container {
  min-height: 60px;
}

.time-slots-container .row {
  min-height: 38px;
}

.availability-matrix input[type="time"]:disabled {
  background-color: #e9ecef;
  opacity: 0.8;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25em 0.5em;
}

.form-check-input:checked {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.form-check-input:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.25rem rgba(75, 182, 109, 0.25);
}

.alert {
  border-radius: 0.375rem;
  border: 1px solid transparent;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c2c7;
  color: #842029;
}

.alert-success {
  background-color: #d1e7dd;
  border-color: #badbcc;
  color: #0f5132;
}
</style>