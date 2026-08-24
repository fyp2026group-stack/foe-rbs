<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Resource Details: <span class="text-dark-teal">{{ resource?.name || 'Loading...' }}</span></h2>
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-dark-teal" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading resource details...</p>
    </div>

    <div v-else-if="errorMessage" class="alert alert-danger text-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
    </div>

    <div v-else-if="resource" class="resource-detail-container">
      <div class="row g-4">
        
        <div class="col-lg-6">
          <div class="card p-3 h-100 resource-main-details">
            <div class="resource-image-lg mb-3">
              <img :src="getImageUrl(resource)" :alt="resource.name" class="img-fluid rounded">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
              <span :class="resource.status === 'Active' ? 'badge bg-success' : resource.status === 'Maintenance' ? 'badge bg-warning' : 'badge bg-secondary'" class="fs-6">
                  {{ resource.status.toUpperCase() }}
              </span>
              <span class="fw-bold fs-5 text-dark-teal">
                  Base Price: 
                  {{ resource.base_price !== null && resource.base_price !== undefined ? 
                     `Rs. ${resource.base_price.toFixed(2)}` : 
                     'N/A (Free)' 
                  }}
              </span>
            </div>

            <h5 class="text-dark-teal mb-2">Description</h5>
            <p>{{ resource.description || 'No detailed description available.' }}</p>

              <button 
                  v-if="resource.status === 'Active'"
                  class="btn btn-sm btn-reserve-card" 
                  @click.stop="handleReserveClick(resource.id)"
                >
                  <i class="bi bi-calendar-check me-1"></i> Reserve
                </button>
            
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card p-4 h-100">
            
            <div class="details-list mb-4 pb-3 border-bottom">
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Resource Name</h6>
                    <p class="fw-bold">{{ resource.name }}</p>
                </div>
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Location Name</h6>
                    <p class="fw-bold">{{ resource.location_name || 'N/A' }}</p>
                </div>
                <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Category</h6>
                    <p class="fw-bold">{{ resource.category?.name || 'Unknown' }}</p>
                </div>
                 <div class="detail-item mb-3">
                    <h6 class="text-muted mb-0">Department</h6>
                    <p class="fw-bold">{{ resource.department || 'Unknown' }}</p>
                </div>
                <div class="detail-item">
                    <h6 class="text-muted mb-0">Assigned Person</h6>
                    <div v-if="assignedAdminNames.length > 0">
                        <p v-for="name in assignedAdminNames" :key="name" class="fw-bold mb-1">
                            <i class="bi bi-person-fill text-dark-teal me-1"></i>{{ name }}
                        </p>
                    </div>
                    <div v-else-if="isLoadingAdmins">
                        <p class="fw-bold text-muted mb-0">Loading admin names...</p>
                    </div>
                    <div v-else-if="resource.assigned_admin_id || (resource.assigned_admin_ids && resource.assigned_admin_ids.length > 0)">
                        <p class="fw-bold text-muted mb-0">Loading admin details...</p>
                    </div>
                    <div v-else>
                        <p class="fw-bold text-muted mb-0">Unassigned</p>
                    </div>
                </div>
            </div>

            <!-- UPDATED: Weekly Availability Section -->
            <div class="schedule-details mb-4 pb-3 border-bottom">
                <h6 class="text-muted fw-bold mb-3">Weekly Availability</h6>
                
                <div v-if="!resource.availability || resource.availability.length === 0" class="text-muted small">
                    No schedule defined.
                </div>
                
                <!-- Day-by-day availability display -->
                <div v-else class="availability-list">
                  <div v-for="day in sortedAvailability" :key="day.day_name" class="day-availability mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="fw-medium text-dark">{{ day.day_name }}</span>
                      <span :class="day.is_available ? 'badge bg-success' : 'badge bg-secondary'">
                        {{ day.is_available ? 'Available' : 'Not Available' }}
                      </span>
                    </div>
                    
                    <!-- Time slots for the day -->
                    <div v-if="day.is_available && day.slots && day.slots.length > 0">
                      <div class="time-slots-container ms-2">
                        <div v-for="(slot, index) in day.slots" :key="index" class="time-slot mb-2">
                          <div class="d-flex align-items-center">
                            <i class="bi bi-clock text-dark-teal me-2"></i>
                            <span class="slot-time">
                              {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                            </span>
                            <span v-if="day.slots.length > 1" class="badge bg-light text-dark border ms-2">
                              Slot {{ index + 1 }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div v-else-if="day.is_available" class="text-muted small ms-2">
                      <i class="bi bi-info-circle me-1"></i> No specific time slots defined (available all day)
                    </div>
                    
                    <div v-else class="text-muted small ms-2">
                      <i class="bi bi-x-circle me-1"></i> Not available on this day
                    </div>
                  </div>
                </div>
            </div>

            <div class="equipment-details">
                <h6 class="text-muted fw-bold mb-2">Included Equipment/Accessories</h6>
                <ul class="list-unstyled equipment-display-list">
                    <li v-if="!resource.equipment || resource.equipment.length === 0" class="text-muted small">
                        No custom equipment listed.
                    </li>
                    <li v-else v-for="item in resource.equipment" :key="item.id" class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="fw-medium">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>{{ item.equipment_name }}
                        </span>
                        <span class="text-muted">Qty: {{ item.quantity }}</span>
                    </li>
                </ul>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter} from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api');
const STORAGE_URL_ROOT = (import.meta.env.VITE_STORAGE_URL || ((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/resources/storage'));

// Interfaces updated to match new data structure
interface ResourceImage {
    id: number;
    file_path: string;
    image_url?: string; 
}

interface ResourceEquipment {
    id: number;
    equipment_name: string;
    quantity: number;
}

interface TimeSlot {
    start_time: string;
    end_time: string;
}

interface ResourceAvailability {
    id: number;
    day_name: string;
    day_of_week: number;
    is_available: boolean;
    slots: TimeSlot[]; // Changed from start_time/end_time to slots array
}

interface ResourceCategory {
    id: number;
    name: string;
}

interface Resource {
    id: number;
    name: string;
    location_name: string;
    category_id: number;
    category: ResourceCategory;
    base_price: number | null;
    assigned_admin_id: number | null;
    assigned_admin_ids: number[] | null;
    description: string | null;
    status: 'Active' | 'Inactive' | 'Maintenance';
    images: ResourceImage[];
    equipment: ResourceEquipment[]; 
    availability: ResourceAvailability[]; 
}

// State
const resource = ref<Resource | null>(null);
const isLoading = ref(false);
const errorMessage = ref('');
const assignedAdminNames = ref<string[]>([]);
const isLoadingAdmins = ref(false);

// Helper to get auth token
const getAuthToken = (): string | null => {
    return localStorage.getItem('authToken') || localStorage.getItem('token');
};

// Helper Functions
const getImageUrl = (resource: Resource): string => {
    if (resource.images && resource.images.length > 0) {
        const filePath = resource.images[0].file_path;
        return filePath.startsWith('http') ? filePath : `${STORAGE_URL_ROOT}/${filePath}`;
    }
    return 'https://via.placeholder.com/600x400?text=No+Image';
};

const formatTime = (time: string | null): string => {
    if (!time) return '00:00';
    // Handle both formats: "14:30:00" and "14:30"
    return time.includes(':') ? time.substring(0, 5) : '00:00';
};

// Sort availability by day of week (Monday to Sunday)
const sortedAvailability = computed(() => {
  if (!resource.value || !resource.value.availability) return [];
  
  const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  
  return [...resource.value.availability].sort((a, b) => {
    return dayOrder.indexOf(a.day_name) - dayOrder.indexOf(b.day_name);
  });
});

// Fetch admin details
const fetchAdminDetails = async (adminIds: number[]) => {
    if (!adminIds || adminIds.length === 0) return;
    
    isLoadingAdmins.value = true;
    assignedAdminNames.value = [];
    
    try {
        const token = getAuthToken();
        if (!token) {
            console.warn('No auth token found for admin fetch');
            isLoadingAdmins.value = false;
            return;
        }

        // Try users endpoint first
        let allUsers: any[] = [];
        try {
            const response = await axios.get(`${API_BASE_URL}/users`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                }
            });
            allUsers = response.data.users || response.data || [];
        } catch (usersError) {
            console.log('Users endpoint not available, trying individual admins fetch...');
        }

        const names: string[] = [];

        for (const adminId of adminIds) {
            const adminUser = allUsers.find((user: any) => user.id === adminId);
            if (adminUser) {
                const name = adminUser.name || 
                             (adminUser.first_name && adminUser.last_name ? `${adminUser.first_name} ${adminUser.last_name}` : '') ||
                             adminUser.username || 
                             adminUser.email || 
                             `Admin ID: ${adminId}`;
                names.push(name);
            } else {
                try {
                    const response = await axios.get(`${API_BASE_URL}/admins/${adminId}`, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json',
                        }
                    });
                    const adminData = response.data.admin || response.data;
                    const name = adminData.name || 
                                 (adminData.first_name && adminData.last_name ? `${adminData.first_name} ${adminData.last_name}` : '') ||
                                 adminData.username || 
                                 `Admin ID: ${adminId}`;
                    names.push(name);
                } catch (adminsError) {
                    console.error(`Failed to fetch admin details for ID ${adminId}`);
                    names.push(`Admin ID: ${adminId}`);
                }
            }
        }
        
        assignedAdminNames.value = names;
    } catch (error: any) {
        console.error('Error fetching admin details:', error);
        assignedAdminNames.value = adminIds.map(id => `Admin ID: ${id}`);
    } finally {
        isLoadingAdmins.value = false;
    }
};

// Process availability data - handle both old and new formats
const processAvailabilityData = (availabilityData: any[]) => {
  if (!availabilityData || !Array.isArray(availabilityData)) return [];
  
  return availabilityData.map(day => {
    // If slots array exists, use it
    if (day.slots && Array.isArray(day.slots)) {
      return {
        ...day,
        slots: day.slots.map((slot: any) => ({
          start_time: slot.start_time || '',
          end_time: slot.end_time || ''
        }))
      };
    }
    
    // Otherwise, create a slots array from old format
    const slots = [];
    if (day.start_time && day.end_time) {
      slots.push({
        start_time: day.start_time,
        end_time: day.end_time
      });
    }
    
    return {
      ...day,
      slots
    };
  });
};

// API Calls
const fetchResourceDetails = async (id: number) => {
    isLoading.value = true;
    errorMessage.value = '';
    assignedAdminNames.value = [];
    
    try {
        const token = getAuthToken();
        if (!token) {
            errorMessage.value = 'Authentication required. Redirecting to login.';
            router.push('/login');
            return;
        }

        const response = await axios.get(`${API_BASE_URL}/resources/${id}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        });
        
        let fetchedResource = response.data.resource || response.data;
        
        // Process availability data to ensure consistent format
        if (fetchedResource.availability) {
          fetchedResource.availability = processAvailabilityData(fetchedResource.availability);
        }
        
        // Process other fields
        if (fetchedResource.base_price) {
            fetchedResource.base_price = parseFloat(fetchedResource.base_price);
        } else {
             fetchedResource.base_price = null;
        }
        
        if (fetchedResource.status) {
             fetchedResource.status = fetchedResource.status.charAt(0).toUpperCase() + fetchedResource.status.slice(1);
        }

        resource.value = fetchedResource as Resource;

        // Debug log to check availability data structure
        console.log('Resource availability data:', fetchedResource.availability);

        // Fetch admin names if admin IDs exist
        const adminIds = fetchedResource.assigned_admin_ids && fetchedResource.assigned_admin_ids.length > 0
            ? fetchedResource.assigned_admin_ids
            : (fetchedResource.assigned_admin_id ? [fetchedResource.assigned_admin_id] : []);

        if (adminIds.length > 0) {
            await fetchAdminDetails(adminIds);
        }

    } catch (error: any) {
        console.error('Error fetching resource details:', error);
        if (error.response?.status === 404) {
            errorMessage.value = `Resource ID ${id} was not found.`;
        } else {
            errorMessage.value = 'Failed to load resource details. Please try again.';
        }
        resource.value = null;
    } finally {
        isLoading.value = false;
    }
};

const handleReserveClick = (id: number) => {
    router.push({ path: '/master-admin/single-resource-booking', query: { resourceId: id } });
};

onMounted(() => {
    const resourceId = parseInt(route.params.id as string);
    if (!isNaN(resourceId)) {
        fetchResourceDetails(resourceId);
    } else {
        errorMessage.value = 'Invalid resource ID provided.';
        resource.value = null;
    }
});
</script>

<style scoped>
/* Your existing styles */
.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}
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
.card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.resource-image-lg {
    max-height: 350px; 
    overflow: hidden;
    border-radius: 6px;
}
.resource-image-lg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.details-list h6, .schedule-details h6, .equipment-details h6 {
    font-size: 0.95rem;
    font-weight: 600;
}
.details-list p {
    font-size: 1rem;
    color: #343a40; 
    margin-bottom: 0;
}

/* NEW: Availability specific styles */
.availability-list {
  max-height: 300px;
  overflow-y: auto;
}

.day-availability {
  padding: 12px;
  background-color: #f8f9fa;
  border-radius: 6px;
  border-left: 4px solid #1e4449;
}

.day-availability:last-child {
  margin-bottom: 0;
}

.time-slots-container {
  background-color: white;
  padding: 10px;
  border-radius: 5px;
  border: 1px solid #e9ecef;
}

.time-slot {
  padding: 6px 10px;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 3px solid #4BB66D;
}

.slot-time {
  font-family: monospace;
  font-weight: 500;
  color: #495057;
}

.badge.bg-success {
  background-color: #4BB66D !important;
}

.badge.bg-secondary {
  background-color: #6c757d !important;
}

.badge.bg-warning {
  background-color: #ffc107 !important;
  color: #212529;
}

/* Existing styles */
.bg-success {
    background-color: #4BB66D !important;
}
.bg-secondary {
    background-color: #6c757d !important;
}
.text-success {
    color: #4BB66D !important;
}
.text-danger {
    color: #dc3545 !important;
}
.text-secondary {
    color: #6c757d !important;
}

.btn-reserve-card {
    background-color: #1e4449;
    color: white;
    border-color: #1e4449;
    font-size: 0.8rem;
    padding: 0.25rem 0.6rem;
    line-height: 1.5; 
    margin-top: 17%;
}
.btn-reserve-card:hover {
    background-color: #fcc300;
    color: #1e4449;
    border-color: #fcc300;
}

/* Scrollbar styling for availability list */
.availability-list::-webkit-scrollbar {
  width: 6px;
}

.availability-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.availability-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.availability-list::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>