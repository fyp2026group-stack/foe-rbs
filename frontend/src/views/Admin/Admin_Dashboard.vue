<template>
  <Navbar />
  <AdminSidebar />
  <div class="section">
    
    <!-- Modern Header Card -->
    <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          </ol>
        </nav>
        <h2 class="mb-0 fw-bold text-dark-teal">Admin Dashboard</h2>
        <p class="text-muted mb-0">Overview of assigned resources and booking activities.</p>
      </div>
      <div class="text-end d-none d-md-block">
        <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
          <i class="bi bi-cpu me-1"></i> System Online
        </span>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-people-fill"
          :value="stats.totalUsers"
          label="Total Users"
          color="#4BB66D"
        />
      </div>
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-box-fill"
          :value="stats.totalResources"
          label="Total Resources"
          color="#26d516"
        />
      </div>
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-clock-fill"
          :value="stats.pendingBookings"
          label="Pending Bookings"
          color="#fcc300"
        />
      </div>
      <div class="col-sm-6 col-md-3">
        <StatCard
          icon="bi bi-check-circle-fill"
          :value="stats.approvedBookings"
          label="Approved Bookings"
          color="#1e4449"
        />
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="mb-3">Bookings Status</h5>
          <div v-if="isLoading" class="text-center py-5">
             <div class="spinner-border text-success" role="status"></div>
          </div>
          <div v-else class="pie-chart-container">
            <PieChart
              :approved="stats.approvedBookings"
              :pending="stats.pendingBookings"
              :rejected="stats.rejectedBookings"
            />
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="chart-card">
          <h5 class="mb-3">Total Bookings Summary</h5>
          <div class="total-bookings">
            <h2 v-if="!isLoading">{{ stats.totalBookings }}</h2>
            <h2 v-else>...</h2>
            <div class="booking-boxes">
              <div class="booking-box approved"> 
                <span class="badge bg-success">{{ calculatePercent(stats.approvedBookings) }}%</span> 
                <p>Approved</p>
              </div>
              <div class="booking-box pending">
                <span class="badge bg-warning text-dark">{{ calculatePercent(stats.pendingBookings) }}%</span> 
                <p>Pending</p>
              </div>
              <div class="booking-box rejected">
                <span class="badge bg-danger">{{ calculatePercent(stats.rejectedBookings) }}%</span> 
                <p>Rejected</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import StatCard from '../../components/StatCard.vue';
import PieChart from '../../components/PieChart.vue';
import Navbar from '../../components/Navbar.vue';
import { userStore } from '../../store/userStore'
import { resourceStore } from '../../store/resourceStore';
import AdminSidebar from '../../components/Sidebar/Admin_Sidebar.vue';

// --- API CONFIG ---
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api');
const getAuthToken = () => localStorage.getItem('authToken');

// --- STATE ---
const isLoading = ref(true);
const stats = ref({
  totalUsers: computed(() => userStore.users.length),
  totalResources: computed(() => resourceStore.resources.length),
  pendingBookings: 0,
  approvedBookings: 0,
  rejectedBookings: 0,
  totalBookings: 0
});

// --- LOGIC ---

// Helper to calculate percentages for the UI
const calculatePercent = (value: number) => {
  if (stats.value.totalBookings === 0) return 0;
  return Math.round((value / stats.value.totalBookings) * 100);
};

const fetchDashboardData = async () => {
  isLoading.value = true;
  const token = getAuthToken();

  if (!token) {
    console.error("Auth token missing");
    isLoading.value = false;
    return;
  }

  try {
    // 1. Fetch Users Count
    if (!userStore.isLoaded) {
      await userStore.fetchUsers();
    }

    // 2. Fetch Bookings and filter by exact Backend Status strings
    const bookingRes = await fetch(`${API_BASE_URL}/bookings`, {
       headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (bookingRes.ok) {
        const bookings = await bookingRes.json();
        stats.value.totalBookings = bookings.length;

        // UPDATED FILTER LOGIC TO MATCH POSTMAN DATA
        stats.value.approvedBookings = bookings.filter((b: any) => b.status === 'Confirmed' || b.status === 'Completed').length;
        stats.value.pendingBookings = bookings.filter((b: any) => b.status === 'Pending').length;
        stats.value.rejectedBookings = bookings.filter((b: any) => b.status === 'Cancelled').length;
    }

    // 3. Fetch Resources Count
    if (!resourceStore.isLoaded) {
      await resourceStore.fetchAll();
    }

  } catch (error) {
    console.error("Error fetching dashboard stats:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
.section {
  margin-left: 250px; 
  padding: 20px; 
  animation: fadeIn 0.3s ease;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section { margin-left: 70px; }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}


.chart-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  height: 100%; 
  margin-top: 20px;
}

.total-bookings h2 {
  font-size: 40px; 
  color: #1e4449;
  text-align: center;
  margin-bottom: 20px;
}

.booking-boxes {
  display: flex;
  flex-wrap: wrap; 
  justify-content: space-between;
  gap: 16px;
}

.booking-box {
  flex: 1 1 30%; 
  min-width: 90px; 
  background: white;
  border-radius: 8px;
  padding: 15px; 
  box-shadow: 0 2px 8px rgba(30, 68, 73, 0.15);
  text-align: center;
}

.booking-box p {
  margin: 10px 0 0;
  font-weight: 500;
  color: #1e4449;
}
</style>