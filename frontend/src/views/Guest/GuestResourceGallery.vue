<template>
  <GuestLayout>
    <div class="section">
      <div class="guest-gallery">
        <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white" style="border-left: 5px solid #1e4449;">
          <div>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item active" aria-current="page">Resources</li>
              </ol>
            </nav>
            <h2 class="mb-0 fw-bold text-dark-teal">Public Resource Gallery</h2>
            <p class="text-muted mb-0">Browse and reserve our high-end facilities.</p>
          </div>
          <div class="text-end d-none d-md-block">
            <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
              <i class="bi bi-collection me-1"></i> {{ sortedResources.length }} Available
            </span>
          </div>
        </div>

        <!-- Resources Grid -->
        <div class="row g-4 mb-5">
          <div v-for="resource in sortedResources" :key="resource.id" class="col-md-4">
            <div class="resource-card shadow-sm">
              <!-- Admin actions hidden for guests, but we keep the structure if needed -->
              <div class="resource-actions" v-if="isAdmin">
                <button class="btn btn-sm btn-action-edit"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-sm btn-action-delete"><i class="bi bi-trash"></i></button>
              </div>

              <!-- Clickable Area for Details -->
              <div @click="viewResourceDetails(resource.id)" class="cursor-pointer">
                <div class="resource-image">
                  <img :src="getMainImage(resource)" :alt="resource.name">
                </div>
                <div class="resource-body">
                  <h5 class="fw-bold text-dark-teal mb-3">{{ resource.name }}</h5>
                  
                  <div class="resource-info-list mb-3">
                    <p v-if="resource.location_name" class="text-muted mb-1 small d-flex align-items-center">
                      <i class="bi bi-geo-alt me-2 text-teal"></i>{{ resource.location_name }}
                    </p>
                    <p class="text-muted mb-1 small d-flex align-items-center">
                      <i class="bi bi-tag me-2 text-teal"></i>{{ getCategoryName(resource.category_id) }}
                    </p>
                    <p class="text-muted mb-3 small d-flex align-items-center fw-bold">
                      <i class="bi bi-currency-rupee me-2 text-teal"></i>Rs. {{ Number(resource.base_price || 0).toFixed(2) }}
                    </p>
                  </div>

                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" :class="getStatusClass(resource.status)">
                          {{ resource.status }}
                        </span>
                        <!-- Visual Toggle Switch for UI consistency with the requested image -->
                        <div class="form-check form-switch opacity-75" @click.stop>
                          <input
                            class="form-check-input"
                            type="checkbox"
                            :checked="resource.status === 'Active'"
                            disabled
                          >
                        </div>
                    </div>

                    <button 
                        v-if="resource.status === 'Active'"
                        class="btn btn-sm btn-reserve-card" 
                        @click.stop="bookResource(resource.id)"
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
  </GuestLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { resourceStore } from '../../store/resourceStore';
import GuestLayout from '../../layouts/GuestLayout.vue';

const router = useRouter();

// Guests are not admins, but we include the variable for structure if you ever want to toggle it
const isAdmin = ref(false);

onMounted(async () => {
  await resourceStore.fetchAll();
});

const sortedResources = computed(() => {
  return [...resourceStore.resources].sort((a, b) => b.id - a.id);
});

const getMainImage = (resource: any) => {
  if (resource.images && resource.images.length > 0) {
    const sorted = [...resource.images].sort((a, b) => (a.order_index || 0) - (b.order_index || 0));
    return ((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + `/resources/storage/${sorted[0].file_path}`);
  }
  return 'https://via.placeholder.com/600x400?text=No+Image';
};

const getCategoryName = (categoryId: number) => {
  const cat = resourceStore.categories.find(c => c.id === categoryId);
  return cat ? cat.name : 'Uncategorized';
};

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Active': return 'bg-success';
    case 'Maintenance': return 'bg-warning text-dark';
    default: return 'bg-secondary';
  }
};

const viewResourceDetails = (id: number) => {
  router.push(`/guest-resources/${id}`);
};

const bookResource = (id: number) => {
  router.push(`/guest-resources/${id}/book`);
};
</script>

<style scoped>
.text-dark-teal { color: #1e4449; }
.text-teal { color: #1e4449; }
.bg-teal { background-color: #1e4449; }
.bg-light-teal { background-color: #e5f4de; }
.border-teal-subtle { border-color: #d1e7dd !important; }

.section {
  margin-left: 260px;
  padding: 30px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section { margin-left: 85px; padding: 15px; }
}

/* Resource Card Styling synced with Admin */
.resource-card {
  position: relative;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  height: 100%;
}

.resource-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
}

.resource-image {
  height: 200px;
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
   transition: transform 0.5s ease;
 }

.resource-card:hover .resource-image img {
  transform: scale(1.05);
}

.resource-body {
  padding: 20px;
}

.resource-body h5 {
  margin-bottom: 12px;
  font-size: 1.15rem;
  letter-spacing: -0.01em;
}

.resource-info-list i {
    width: 20px;
    text-align: center;
}

.form-check-input:checked {
  background-color: #fcc300;
  border-color: #fcc300;
}

.btn-reserve-card {
    background-color: #1e4449;
    color: white;
    border: none;
    font-size: 0.85rem;
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-reserve-card:hover {
    background-color: #163337;
    transform: scale(1.05);
    color: white;
}

.cursor-pointer { cursor: pointer; }

.bg-success { background-color: #4BB66D !important; }
.bg-warning { background-color: #ffc107 !important; }

/* Visual consistency for the toggle from the image */
.form-check-input:disabled {
    opacity: 1;
}
.form-check-input:checked {
    background-color: #ffc107;
    border-color: #ffc107;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.guest-gallery {
  animation: fadeIn 0.4s ease-out;
}

/* ========== MODERN DASHBOARD HEADER STYLES ========== */
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
