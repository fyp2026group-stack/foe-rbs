<template>
    <Navbar/>
    <UserSidebar/>
    <div class="section">
  
      <!-- Modern Dashboard Header - Same as Guest Booking Page -->
      <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white border-start border-5 border-teal">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
              </ol>
            </nav>
            <h2 class="mb-0 fw-bold text-dark-teal">Welcome User Dashboard</h2>
            <p class="text-muted mb-0">Overview of your bookings and resource statistics.</p>
          </div>
          <div class="text-end d-none d-md-block">
             <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
               <i class="bi bi-shield-lock me-1"></i> My Dashboard
             </span>
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4" v-if="!isLoading">
        <div class="col-sm-6 col-md-3">
          <StatCard
            icon="bi bi-box-seam"
            :value="stats.totalResources"
            label="Total Resources"
            color="#1e4449"
          />
        </div>
        <div class="col-sm-6 col-md-3">
          <StatCard
            icon="bi bi-journal-text"
            :value="stats.totalBookings"
            label="My Total Bookings"
            color="#26d516"
          />
        </div>
        <div class="col-sm-6 col-md-3">
          <StatCard
            icon="bi bi-clock-history"
            :value="stats.pendingBookings"
            label="My Pending Bookings"
            color="#fcc300"
          />
        </div>
        <div class="col-sm-6 col-md-3">
          <StatCard
            icon="bi bi-check2-circle"
            :value="stats.approvedBookings"
            label="My Approved Bookings"
            color="#4BB66D"
          />
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
      </div>

      <div class="row g-4 mb-4" v-if="!isLoading">
        <div class="col-md-6">
          <div class="chart-card">
            <h5 class="mb-3">Bookings Status</h5>
            <div class="pie-chart-container">
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
            <h5 class="mb-3">My Total Bookings</h5>
            <div class="total-bookings">
              <h2>{{ stats.totalBookings }}</h2>
                <div class="booking-boxes">
                  <div class="booking-box approved"> 
                    <span class="badge bg-success">{{ stats.totalBookings ? Math.round((stats.approvedBookings / stats.totalBookings) * 100) : 0 }}%</span> <p>Approved</p>
                  </div>
                  <div class="booking-box pending">
                    <span class="badge bg-warning text-dark">{{ stats.totalBookings ? Math.round((stats.pendingBookings / stats.totalBookings) * 100) : 0 }}%</span> <p>Pending</p>
                  </div>
                  <div class="booking-box rejected">
                    <span class="badge bg-danger">{{ stats.totalBookings ? Math.round((stats.rejectedBookings / stats.totalBookings) * 100) : 0 }}%</span> <p>Rejected</p>
                  </div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import StatCard from '../../components/StatCard.vue';
import PieChart from '../../components/PieChart.vue';
import Navbar from '../../components/Navbar.vue';
import UserSidebar from '../../components/Sidebar/UserSidebar.vue';
import { resourceStore } from '../../store/resourceStore';
import { bookingStore } from '../../store/bookingStore';

const isLoading = ref(true);

// Get logged-in user email from localStorage
const getLoggedInUserEmail = () => {
  return localStorage.getItem('userEmail') || 
         localStorage.getItem('email') || 
         localStorage.getItem('user_email') || 
         '';
};

// Filter bookings for current logged-in user only
const myBookings = computed(() => {
  const loggedInEmail = getLoggedInUserEmail().toLowerCase();
  if (!loggedInEmail) return [];
  
  // Filter only bookings that belong to the logged-in user
  return bookingStore.bookings.filter((booking: any) => {
    const bookingEmail = (booking.user_email || '').toLowerCase();
    return bookingEmail === loggedInEmail;
  });
});

const stats = computed(() => {
  // Count only current user's bookings
  const pendingCount = myBookings.value.filter((b: any) => {
    const status = (b.status || '').toLowerCase();
    return status === 'pending_for_verification' || 
           status === 'pending' || 
           status === 'Pending' ||
           status === 'Pending_for_Verification';
  }).length;
  
  const approvedCount = myBookings.value.filter((b: any) => {
    const status = (b.status || '').toLowerCase();
    return status === 'approved' || 
           status === 'confirmed' || 
           status === 'Completed' ||
           status === 'completed' ||
           status === 'Confirmed';
  }).length;
  
  const rejectedCount = myBookings.value.filter((b: any) => {
    const status = (b.status || '').toLowerCase();
    return status === 'cancelled' || 
           status === 'rejected' ||
           status === 'Cancelled' ||
           status === 'Rejected';
  }).length;

  return {
    totalResources: resourceStore.resources.length,
    pendingBookings: pendingCount,
    approvedBookings: approvedCount,
    rejectedBookings: rejectedCount,
    totalBookings: myBookings.value.length
  };
});

onMounted(async () => {
  try {
    await Promise.all([
      resourceStore.fetchAll(),
      bookingStore.fetchAll()
    ]);
    
    // Debug logs
    console.log('Logged-in User Email:', getLoggedInUserEmail());
    console.log('Total Bookings in Store:', bookingStore.bookings.length);
    console.log('My Bookings (filtered):', myBookings.value.length);
    console.log('Stats:', stats.value);
    
  } finally {
    isLoading.value = false;
  }
});
</script>

<style scoped>
/* ========== MODERN DASHBOARD HEADER STYLES (from Guest Booking Page) ========== */
.text-dark-teal { color: #1a3a3d; }
.text-teal { color: #1e4449; }
.bg-light-teal { background-color: #e5f4de; }
.border-teal { border-color: #1e4449 !important; }
.border-teal-subtle { border-color: #d1e7dd !important; }

.dashboard-header-modern {
    background: linear-gradient(to right, #ffffff, #f7fdf4);
    border-radius: 12px;
}

/* ========== END OF MODERN HEADER STYLES ========== */

/* ================================================= */
/* FIX: ADJUSTED .section FOR FIXED SIDEBAR          */
/* ================================================= */

.section {
  /* Pushes the entire dashboard content to the right by 250px (Sidebar Width) */
  margin-left: 250px; 
  padding: 20px; /* Add overall padding */
  animation: fadeIn 0.3s ease;
  margin-top: 20px;
}

@media (max-width: 768px) {
  /* When the sidebar collapses, reduce the margin to 70px (Collapsed Sidebar Width) */
  .section {
    margin-left: 70px;
  }
}

/* ================================================= */
/* RESPONSIVE CSS STYLES START                       */
/* ================================================= */

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* --- General Card & Title Styles --- */
.section-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 24px; 
}

.chart-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(114, 38, 38, 0.08);
  height: 100%; 
  margin-top: 20px;
}

/* --- Total Bookings Section --- */
.total-bookings {
  text-align: center;
  padding-top: 20px; 
}

@media (min-width: 768px) {
  .total-bookings {
    padding-top: 42px; 
  }
}

.total-bookings h2 {
  font-size: 40px; 
  color: #1e4449;
  margin-bottom: 20px;
}

@media (min-width: 768px) {
  .total-bookings h2 {
    font-size: 48px; 
  }
}

/* --- Booking Percentage Boxes --- */
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
  transition: transform 0.2s ease;
  text-align: center;
}

@media (min-width: 576px) {
  .booking-box {
    padding: 20px; 
  }
}

.booking-box:hover {
  transform: translateY(-4px);
}

.booking-box p {
  margin: 10px 0 0;
  font-weight: 500;
  color: #1e4449;
  font-size: 16px; 
}

@media (min-width: 768px) {
  .booking-box p {
    font-size: 20px; 
  }
}

.booking-box .badge {
  font-size: 14px; 
  padding: 10px 15px; 
  display: inline-block; 
}

@media (min-width: 768px) {
  .booking-box .badge {
    font-size: 16px; 
    padding: 18px 24px; 
  }
}

.booking-box.approved { border-top: 4px solid #4BB66D; }
.booking-box.pending { border-top: 4px solid #fcc300; }
.booking-box.rejected { border-top: 4px solid #dc3545; }

/* --- Pie Chart Container --- */
/* .pie-chart-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 0;
} */

/* Responsive adjustments for header */
@media (max-width: 768px) {
  .dashboard-header-modern {
    padding: 1rem !important;
  }
}
</style>