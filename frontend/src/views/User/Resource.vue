<template>
  <Navbar />
  <UserSidebar />
  <div class="section">
   <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white" style="border-left: 5px solid #1e4449;">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item active" aria-current="page">Resources</li>
        </ol>
      </nav>
      <h2 class="mb-0 fw-bold text-dark-teal">Welcome User Resource Page</h2>
      <p class="text-muted mb-0">Browse and reserve university resources.</p>
    </div>
    <div class="text-end d-none d-md-block">
       <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
         <i class="bi bi-shield-lock me-1"></i> Available Resources
       </span>
    </div>
  </div>
</div>

    <!-- Search Bar -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input 
            type="text" 
            class="form-control" 
            placeholder="Search resources by name or category..." 
            v-model="searchQuery"
          >
        </div>
      </div>
      <div class="col-md-6">
        <select class="form-select" v-model="selectedCategory">
          <option value="">All Categories</option>
          <option v-for="category in categoriesList" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="resourceStore.isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading resources...</span>
      </div>
    </div>

    <!-- Resources Grid -->
    <div v-else class="row g-4">
      <div class="col-md-4 col-lg-3" v-for="resource in filteredResources" :key="resource.id">
        <div class="card resource-card h-100 shadow-sm border-0">
          <!-- Make the entire card clickable to navigate to single resource page -->
          <div class="card-clickable" @click="navigateToSingleResource(resource.id)">
            <div class="card-img-container bg-light">
              <img 
                v-if="resource.images && resource.images.length > 0" 
                :src="'http://localhost:8000/api/resources/storage/' + resource.images[0].file_path" 
                class="card-img-top" 
                alt="Resource Image"
                @error="handleImageError"
              >
              <div v-else class="placeholder-img d-flex align-items-center justify-content-center h-100 text-muted">
                <i class="bi bi-image" style="font-size: 2rem;"></i>
              </div>
            </div>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title text-truncate">{{ resource.name }}</h5>
              <p v-if="resource.location_name" class="card-text text-muted small mb-2 text-truncate">
                <i class="bi bi-geo-alt me-1"></i>{{ resource.location_name }}
              </p>
              <p class="card-text text-muted small mb-2">
                <i class="bi bi-tag me-1"></i>{{ getCategoryName(resource.category_id) }}
              </p>
              <p class="card-text text-muted small mb-2">
                <i class="bi bi-currency-rupee me-1"></i>Rs. {{ resource.base_price }}
              </p>

              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-auto">
                <span class="badge" :class="getStatusClass(resource.status)">
                  {{ resource.status === 'Active' ? 'Available' : 'Unavailable' }}
                </span>
              </div>
            </div>
          </div>
          <!-- Reserve button - Fixed at bottom of card -->
          <div class="card-footer bg-transparent border-0 pb-3 pt-0">
            <button 
              v-if="resource.status === 'Active'"
              class="btn btn-sm btn-reserve-card w-100" 
              @click.stop="handleReserveClick(resource.id)"
            >
              <i class="bi bi-calendar-check me-1"></i> Reserve
            </button>
            <button 
              v-else
              class="btn btn-sm btn-reserve-card-disabled w-100" 
              disabled
            >
              <i class="bi bi-calendar-x me-1"></i> Unavailable
            </button>
          </div>
        </div>
      </div>
      
      <div v-if="filteredResources.length === 0" class="col-12 text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
        <h5>No resources found</h5>
        <p>Try different search keywords or check back later.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router'
import Navbar from '../../components/Navbar.vue';
import UserSidebar from '../../components/Sidebar/UserSidebar.vue';
import { resourceStore } from '../../store/resourceStore';
import axios from 'axios';
import * as bootstrap from 'bootstrap';

const router = useRouter();
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedResource = ref<any>(null);
const reserveModalRef = ref<HTMLElement | null>(null);
let modalInstance: bootstrap.Modal | null = null;
const isSubmitting = ref(false);

const today = new Date();
const minDate = new Date(today.getTime() - (today.getTimezoneOffset() * 60000)).toISOString().split('T')[0];

const bookingForm = ref({
  date: minDate,
  startTime: '08:00',
  endTime: '10:00',
  notes: ''
});

// Get categories from store
const categoriesList = computed(() => resourceStore.categories);

onMounted(() => {
  resourceStore.fetchAll();
  if (reserveModalRef.value) {
    modalInstance = new bootstrap.Modal(reserveModalRef.value);
  }
});

// Filter resources by search query and category
const filteredResources = computed(() => {
  let filtered = resourceStore.resources;
  
  // Filter by search query
  if (searchQuery.value) {
    const lowerQ = searchQuery.value.toLowerCase();
    filtered = filtered.filter(r => 
      r.name.toLowerCase().includes(lowerQ) || 
      (r.location_name && r.location_name.toLowerCase().includes(lowerQ)) ||
      (r.category?.name && r.category.name.toLowerCase().includes(lowerQ))
    );
  }
  
  // Filter by selected category
  if (selectedCategory.value) {
    const selectedCatId = Number(selectedCategory.value);
    filtered = filtered.filter(r => Number(r.category_id) === selectedCatId);
  }
  
  return filtered;
});

// Get category name from category_id
const getCategoryName = (categoryId: number): string => {
  const category = resourceStore.categories.find(c => c.id === categoryId);
  return category ? category.name : 'Unknown';
};

// Get status badge class
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

// Navigate to single resource page
const navigateToSingleResource = (id: number) => {
  router.push(`/user/resource/${id}`);
};

const handleImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.style.display = 'none';
  if (target.nextElementSibling) {
      (target.nextElementSibling as HTMLElement).style.display = 'flex';
  }
};

const handleReserveClick = (id: number) => {
  router.push({ path: '/user/single-resource-booking', query: { resourceId: id } });
};

const submitReservation = async () => {
  isSubmitting.value = true;
  try {
    const token = localStorage.getItem('authToken');
    const userEmail = localStorage.getItem('userEmail');
    const userId = localStorage.getItem('userId') || 0;

    const payload = {
      user_id: parseInt(userId as string),
      user_email: userEmail,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.notes,
      resources: [
        { resource_id: selectedResource.value.id }
      ]
    };

    const response = await axios.post('http://localhost:8000/api/bookings', payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    if (response.data) {
      alert("Booking Request Initiated! Please check your email for the OTP to confirm this request.");
      modalInstance?.hide();
      // Reset form
      bookingForm.value = {
        date: minDate,
        startTime: '08:00',
        endTime: '10:00',
        notes: ''
      };
    }
  } catch (error: any) {
    console.error("Booking failed:", error);
    alert("Failed to submit booking: " + (error.response?.data?.message || error.message));
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.section {
  margin-left: 250px;
  padding: 20px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section {
    margin-left: 70px;
  }
}



.section-title {
  margin: 0;
  font-weight: 600;
}

/* Resource Card Styling - Same as Master Admin */
.resource-card {
  position: relative;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.resource-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.card-clickable {
  cursor: pointer;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.card-clickable:hover .card-title {
  color: #fcc300;
}

.card-img-container {
  height: 180px;
  overflow: hidden;
  flex-shrink: 0;
  background-color: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
}
 
 .card-img-top {
   width: 100%;
   height: 100%;
   object-fit: contain;
   transition: transform 0.3s ease;
 }

.resource-card:hover .card-img-top {
  transform: scale(1.05);
}

.placeholder-img {
  background-color: #f8f9fa;
  color: #adb5bd;
}

.card-body {
  padding: 16px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.card-body h5 {
  margin-bottom: 8px;
  color: #1e4449;
}

.card-footer {
  background: transparent;
  border-top: none;
  padding: 0 16px 16px 16px;
  margin-top: auto;
  flex-shrink: 0;
}

/* Reserve Button - Exactly like Master Admin, full width */
.btn-reserve-card {
  background-color: #1e4449;
  color: white;
  border-color: #1e4449;
  font-size: 0.8rem;
  padding: 0.35rem 0.6rem;
  line-height: 1.5;
  border-radius: 4px;
  transition: all 0.2s ease;
  width: 100%;
  display: inline-block;
  text-align: center;
}

.btn-reserve-card:hover {
  background-color: #fcc300;
  color: #1e4449;
  border-color: #fcc300;
}

.btn-reserve-card-disabled {
  background-color: #6c757d;
  color: #fff;
  border-color: #6c757d;
  font-size: 0.8rem;
  padding: 0.35rem 0.6rem;
  line-height: 1.5;
  border-radius: 4px;
  cursor: not-allowed;
  opacity: 0.65;
  width: 100%;
  display: inline-block;
  text-align: center;
}

/* Badge Styling - Same as Master Admin */
.badge {
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
  border-radius: 4px;
}

.bg-success {
  background-color: #4BB66D !important;
}

.bg-secondary {
  background-color: #6c757d !important;
}

.bg-warning {
  background-color: #ffc107 !important;
  color: #212529 !important;
}

/* Form Controls */
.form-select:focus, .form-control:focus {
  border-color: #fcc300;
  box-shadow: 0 0 0 0.2rem rgba(252, 195, 0, 0.25);
}

/* Modal Styles */
.modal-content {
  border-radius: 8px;
  overflow: hidden;
}

.modal-header.bg-light {
  background-color: #f8f9fa !important;
}

.btn-primary {
  background-color: #1e4449;
  border-color: #1e4449;
}

.btn-primary:hover {
  background-color: #fcc300;
  border-color: #fcc300;
  color: #1e4449;
}

/* ========== MODERN DASHBOARD HEADER STYLES ========== */
.text-dark-teal { color: #1a3a3d; }
.text-teal { color: #1e4449; }
.bg-light-teal { background-color: #e5f4de; }
.border-teal-subtle { border-color: #d1e7dd !important; }

.dashboard-header-modern {
    background: linear-gradient(to right, #ffffff, #f7fdf4);
    border-radius: 12px;
}

@media (max-width: 768px) {
  .dashboard-header-modern {
    padding: 1rem !important;
  }
}
</style>