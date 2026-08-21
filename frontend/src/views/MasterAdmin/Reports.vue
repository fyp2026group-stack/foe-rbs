<template>
  <navbar />
  <master-admin-sidebar />
  
  <div class="section">
    <!-- Modern Header Card -->
    <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white no-print">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item active" aria-current="page">Reports</li>
          </ol>
        </nav>
        <h2 class="mb-0 fw-bold text-dark-teal">Analytics & Reports</h2>
        <p class="text-muted mb-0">Gain insights into resource usage, revenue, and system activity.</p>
      </div>
      <div class="text-end d-none d-md-block">
        <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
          <i class="bi bi-graph-up-arrow me-1"></i> {{ bookings.length }} Bookings
        </span>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
      <div></div>
      <button class="btn btn-dark-teal btn-sm" @click="handlePrint">
        <i class="bi bi-printer me-1"></i>Print Page
      </button>
    </div>

    <div class="global-filter-card mb-4 no-print">
      <div class="filter-header">
        <i class="bi bi-funnel me-2"></i>
        <h6 class="mb-0">Filter Reports by Date</h6>
      </div>
      <div class="filter-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-8">
            <div class="d-flex flex-wrap gap-2">
              <button v-for="type in ['today', 'week', 'month', 'year', 'all']" 
                :key="type" class="btn btn-sm"
                :class="dateRangeType === type ? 'btn-dark-teal' : 'btn-outline-dark-teal'"
                @click="setDateRange(type)">
                {{ type.toUpperCase() }}
              </button>
            </div>
          </div>
          <div class="col-md-4">
            <div class="input-group">
              <input type="date" class="form-control form-control-sm" v-model="startDate">
              <span class="input-group-text">to</span>
              <input type="date" class="form-control form-control-sm" v-model="endDate">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4 no-print">
      <div class="col-md-3" v-for="(stat, label) in statsDisplay" :key="label">
        <div class="stat-card">
          <div class="stat-icon" :style="stat.iconStyle"><i :class="stat.icon"></i></div>
          <div class="stat-content">
            <h3>{{ stat.value }}</h3>
            <p>{{ label }}</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!isInitialLoading" class="reports-container">
      
      <div class="table-card mb-4" id="resources-report">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Resources Report</h5>
          <div class="d-flex gap-2 align-items-center no-print">
            <input type="text" class="form-control form-control-sm" placeholder="Search resources..." v-model="resourceFilter.search" style="max-width: 200px;">
            <select class="form-select form-select-sm" v-model="selectedResourceReportFilter" style="max-width: 200px;">
              <option value="">All Resources</option>
              <option v-for="res in resources" :key="res.id" :value="res.name">
                {{ res.name }}
              </option>
            </select>
            <select class="form-select form-select-sm" v-model="selectedCategoryReportFilter" style="max-width: 200px;">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
            <button class="btn btn-sm btn-outline-success" @click="printSection('resources')">PDF</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th></tr>
            </thead>
            <tbody>
              <tr v-for="res in filteredResources" :key="res.id">
                <td>{{ res.id }}</td>
                <td><strong>{{ res.name }}</strong></td>
                <td>{{ getCategoryName(res.category_id) }}</td>
                <td>Rs. {{ formatPrice(res.base_price) }}</td>
                <td><span class="badge" :class="res.status === 'Active' ? 'bg-success' : 'bg-secondary'">{{ res.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="table-card mb-4" id="users-report">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Users Report</h5>
          <div class="d-flex gap-2 align-items-center no-print">
            <input type="text" class="form-control form-control-sm" placeholder="Search users..." v-model="userFilter.search" style="max-width: 200px;">
            <select class="form-select form-select-sm" v-model="selectedUserNameFilter" style="max-width: 200px;">
              <option value="">All Users</option>
              <option v-for="user in users" :key="user.id" :value="user.name">
                {{ user.name }}
              </option>
            </select>
            <select class="form-select form-select-sm" v-model="selectedRoleReportFilter" style="max-width: 200px;">
              <option value="">All Roles</option>
              <option v-for="role in uniqueRoles" :key="role" :value="role">
                {{ role }}
              </option>
            </select>
            <button class="btn btn-sm btn-outline-success" @click="printSection('users')">PDF</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th></tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id">
                <td>{{ user.id }}</td>
                <td><strong>{{ user.name }}</strong></td>
                <td>{{ user.email }}</td>
                <td><span class="badge bg-primary">{{ user.primaryRole }}</span></td>
                <td><span class="badge" :class="user.status === 'active' ? 'bg-success' : 'bg-secondary'">{{ user.status }}</span></td>
                <td>{{ formatDate(user.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="table-card mb-4" id="bookings-report">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Bookings Report</h5>
          <div class="d-flex gap-2 align-items-center no-print">
            <input type="text" class="form-control form-control-sm" placeholder="Search bookings..." v-model="bookingFilter.search" style="max-width: 150px;">
            <select class="form-select form-select-sm" v-model="selectedResourceFilter" style="max-width: 150px;">
              <option value="">All Resources</option>
              <option v-for="res in resources" :key="res.id" :value="res.name">
                {{ res.name }}
              </option>
            </select>
            <input type="date" class="form-control form-control-sm" v-model="selectedBookingDateFilter" style="max-width: 150px;">
            <button class="btn btn-sm btn-outline-success" @click="printSection('bookings')">PDF</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr><th>Ref</th><th>Email</th><th>Resource</th><th>Date</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
              <tr v-for="bkg in filteredBookings" :key="bkg.id">
                <td><span class="badge bg-light text-dark">{{ bkg.booking_reference }}</span></td>
                <td>{{ bkg.user_email }}</td>
                <td>{{ bkg.resource?.name || bkg.details?.[0]?.item_name || 'N/A' }}</td>
                <td>{{ formatDate(bkg.booking_date) }}</td>
                <td>Rs. {{ formatPrice(bkg.total_amount) }}</td>
                <td><span class="badge" :class="bkg.status === 'Confirmed' ? 'bg-success' : 'bg-warning text-dark'">{{ bkg.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="isInitialLoading" class="loading-overlay no-print">
      <div class="spinner-border text-dark-teal" role="status"></div>
      <p class="mt-2 text-muted fw-bold">Syncing data from Cloud...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { reportStore } from '../../store/reportStore';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- STATE & FILTERS ---
const isInitialLoading = ref(true);
const dateRangeType = ref('month');
const startDate = ref('');
const endDate = ref('');
const resourceFilter = ref({ search: '' });
const userFilter = ref({ search: '' });
const bookingFilter = ref({ search: '' });
const selectedResourceFilter = ref('');
const selectedResourceReportFilter = ref('');
const selectedCategoryReportFilter = ref('');
const selectedUserNameFilter = ref('');
const selectedRoleReportFilter = ref('');
const selectedBookingDateFilter = ref('');

// --- COMPUTED DATA FROM STORE ---
const users = computed(() => reportStore.users);
const resources = computed(() => reportStore.resources);
const bookings = computed(() => reportStore.bookings);
const categories = computed(() => reportStore.categories);

const uniqueRoles = computed(() => {
  return [...new Set(users.value.map(u => u.primaryRole).filter(Boolean))];
});

// --- FILTERING LOGIC ---
const filteredResources = computed(() => {
  return resources.value.filter(r => {
    // 1. Text search filter
    if (resourceFilter.value.search && !r.name.toLowerCase().includes(resourceFilter.value.search.toLowerCase())) {
      return false;
    }
    // 2. Dropdown filter
    if (selectedResourceReportFilter.value && r.name !== selectedResourceReportFilter.value) {
      return false;
    }
    // 3. Category filter
    if (selectedCategoryReportFilter.value && r.category_id != selectedCategoryReportFilter.value) {
      return false;
    }
    return true;
  });
});
const handlePrint = () => {
  window.print();
};

const filteredUsers = computed(() => {
  return users.value.filter(u => {
    // 1. Text search filter
    if (userFilter.value.search) {
      const query = userFilter.value.search.toLowerCase();
      const nameMatch = u.name?.toLowerCase().includes(query);
      const emailMatch = u.email?.toLowerCase().includes(query);
      if (!nameMatch && !emailMatch) return false;
    }
    // 2. Name dropdown filter
    if (selectedUserNameFilter.value && u.name !== selectedUserNameFilter.value) {
      return false;
    }
    // 3. Role dropdown filter
    if (selectedRoleReportFilter.value && u.primaryRole !== selectedRoleReportFilter.value) {
      return false;
    }
    return true;
  });
});

const filteredBookings = computed(() => {
  return bookings.value.filter(b => {
    // 1. Date filter
    const bookingDate = new Date(b.booking_date).toISOString().split('T')[0];
    if (selectedBookingDateFilter.value) {
      if (bookingDate !== selectedBookingDateFilter.value) return false;
    } else if (startDate.value && dateRangeType.value !== 'all') {
      if (bookingDate < startDate.value || bookingDate > endDate.value) return false;
    }
    
    // 2. Resource filter
    if (selectedResourceFilter.value) {
      const bookingResourceName = b.resource?.name || b.details?.[0]?.item_name || '';
      if (bookingResourceName.toLowerCase() !== selectedResourceFilter.value.toLowerCase()) return false;
    }
    
    // 3. Search query filter (Reference, Email, Resource Name)
    if (bookingFilter.value.search) {
      const query = bookingFilter.value.search.toLowerCase();
      const refMatch = b.booking_reference?.toLowerCase().includes(query);
      const emailMatch = b.user_email?.toLowerCase().includes(query);
      const resourceName = b.resource?.name || b.details?.[0]?.item_name || '';
      const resourceMatch = resourceName.toLowerCase().includes(query);
      
      if (!refMatch && !emailMatch && !resourceMatch) return false;
    }
    
    return true;
  });
});

// --- STATS ---
const statsDisplay = computed(() => ({
  'Resources': { value: filteredResources.value.length, icon: 'bi-box-seam', iconStyle: 'color: #1e4449' },
  'Users': { value: filteredUsers.value.length, icon: 'bi-people', iconStyle: 'color: #26d516' },
  'Bookings': { value: filteredBookings.value.length, icon: 'bi-calendar-check', iconStyle: 'color: #4BB66D' },
  'Revenue': { value: 'Rs. ' + formatPrice(totalRevenue.value), icon: 'bi-cash-coin', iconStyle: 'color: #ffc107' }
}));

const totalRevenue = computed(() => filteredBookings.value.reduce((acc, b) => acc + (parseFloat(b.total_amount) || 0), 0));

// --- HELPERS ---
const formatPrice = (p: any) => parseFloat(p || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
const formatDate = (d: string) => d ? new Date(d).toLocaleDateString() : 'N/A';
const getCategoryName = (id: any) => categories.value.find(c => c.id === id)?.name || 'N/A';
const getResourceRevenue = (id: any) => bookings.value.filter(b => b.details?.some((d: any) => d.item_name?.includes(id.toString()))).reduce((sum, b) => sum + (parseFloat(b.total_amount) || 0), 0);

const setDateRange = (type: string) => {
  dateRangeType.value = type;
  const today = new Date();
  if (type === 'today') startDate.value = today.toISOString().split('T')[0];
  if (type === 'month') startDate.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
  endDate.value = today.toISOString().split('T')[0];
};

// --- PRINT LOGIC ---
const printSection = (sectionId: string) => {
  const tableHtml = document.getElementById(`${sectionId}-report`)?.querySelector('table')?.outerHTML || '';
  const printWindow = window.open('', '_blank');
  if (!printWindow) return;
  printWindow.document.write(`<html><head><title>Report</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>@page { size: A4; margin: 20mm; } body { padding: 20px; font-family: sans-serif; } .header { text-align: center; border-bottom: 2px solid #1e4449; margin-bottom: 20px; }</style></head><body><div class="header"><h1>University RBS</h1><p>${sectionId.toUpperCase()} REPORT</p></div>${tableHtml}</body></html>`);
  printWindow.document.close();
  printWindow.onload = () => { setTimeout(() => { printWindow.print(); printWindow.close(); }, 500); };
};

onMounted(async () => {
  setDateRange('month');
  if (!reportStore.isLoaded) await reportStore.fetchAllReports();
  // Normalize bookings to ensure resource info is populated
  reportStore.bookings.forEach((booking: any) => {
    booking.resource = booking.resource || booking.item || booking.details?.[0] || null;
  });
  isInitialLoading.value = false;
});
</script>

<style scoped>
.section { margin-left: 260px; padding: 20px; background-color: #f8f9fa; min-height: 100vh; }
.global-filter-card, .stat-card, .table-card { background: white; border-radius: 12px; border: 1px solid #e9ecef; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.filter-header { background: #1e4449; color: white; padding: 12px 20px; border-radius: 12px 12px 0 0; }
.filter-body, .stat-card, .table-card { padding: 20px; }
.stat-card { display: flex; align-items: center; gap: 15px; }
.stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: #f8f9fa; }
.loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; }
.btn-dark-teal { background: #1e4449; color: white; }
@media (max-width: 768px) { .section { margin-left: 0; } }
@media print {
  /* 1. Target the sidebar container */
  /* This covers common names for the sidebar component */
  .master-admin-sidebar,
  aside,
  .sidebar-wrapper,
  .sidebar {
    display: none !important;
    width: 0 !important;
    position: absolute !important;
  }

  /* 2. Remove the margin that makes room for the sidebar */
  .section, 
  main, 
  .content-body {
    margin-left: 0 !important;
    padding: 0 !important;
    border: none !important;
  }

  /* 3. Hide the navbar and other UI clutter */
  .navbar,
  .no-print,
  button,
  .global-filter-card {
    display: none !important;
  }

  /* 4. Fix table width for A4 */
  .table-card {
    box-shadow: none !important;
    border: none !important;
    width: 100% !important;
  }
}
</style>