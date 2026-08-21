<template>
  <GuestLayout>
    <div class="section">
      <div class="public-bookings">
      <div class="dashboard-header mb-4">
        <h2 class="section-title">Public Booking Lookup</h2>
        <p>Check the status of your reservation requests.</p>
      </div>

      <!-- Search Section -->
      <div class="row justify-content-center mb-5">
        <div class="col-md-6 col-lg-5">
          <div class="card shadow-sm border-0">
            <div class="card-body p-4 text-center">
              <h5 class="mb-3">Find Your Bookings</h5>
              <form @submit.prevent="searchBookings">
                <div class="input-group mb-3">
                  <span class="input-group-text bg-white text-dark-teal"><i class="bi bi-envelope"></i></span>
                  <input type="email" class="form-control" placeholder="Enter your email address" v-model="searchEmail" required>
                </div>
                <button class="btn btn-teal w-100" type="submit" :disabled="bookingStore.isLoading">
                  <span class="spinner-border spinner-border-sm me-2" v-if="bookingStore.isLoading"></span>
                  <i class="bi bi-search me-2" v-else></i> Lookup Bookings
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasSearched">
        <h4 class="mb-4 text-center text-dark-teal">Results for "{{ lastSearchedEmail }}"</h4>
        
        <div v-if="bookingStore.bookings.length === 0 && !bookingStore.isLoading" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-3"></i>
          <h5>No bookings found</h5>
          <p>We couldn't find any reservations associated with this email address.</p>
        </div>

        <div v-else class="row g-4 justify-content-center">
          <div class="col-md-8 col-lg-6" v-for="b in bookingStore.bookings" :key="b.id">
            <div class="card shadow-sm border-0 booking-card">
              <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark-teal">{{ b.booking_reference }}</span>
                <span class="badge" :class="statusBadgeClass(b.status)">{{ formatStatus(b.status) }}</span>
              </div>
              <div class="card-body">
                <div class="row mb-2">
                  <div class="col-sm-4 text-muted small fw-bold">Date</div>
                  <div class="col-sm-8">{{ formatDate(b.booking_date) }}</div>
                </div>
                <div class="row mb-2">
                  <div class="col-sm-4 text-muted small fw-bold">Time</div>
                  <div class="col-sm-8">{{ formatTime(b.start_time) }} - {{ formatTime(b.end_time) }}</div>
                </div>
                <div class="row mb-3">
                  <div class="col-sm-4 text-muted small fw-bold">Resources</div>
                  <div class="col-sm-8">
                    <ul class="list-unstyled mb-0">
                      <li v-for="detail in b.details" :key="detail.id">
                        <i class="bi bi-check-circle-fill text-success small me-1"></i> {{ detail.item_name }}
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="alert alert-light border small mb-0" v-if="b.notes">
                  <strong>Notes:</strong> {{ b.notes }}
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
import { ref } from 'vue';
import GuestLayout from '../../layouts/GuestLayout.vue';
import { bookingStore } from '../../store/bookingStore';

const searchEmail = ref('');
const lastSearchedEmail = ref('');
const hasSearched = ref(false);

const searchBookings = async () => {
  if (!searchEmail.value) return;
  lastSearchedEmail.value = searchEmail.value;
  hasSearched.value = true;
  await bookingStore.fetchGuestBookings(searchEmail.value);
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
};

const formatTime = (timeStr: string) => {
  if (!timeStr) return '';
  const [h, m] = timeStr.split(':');
  const date = new Date();
  date.setHours(parseInt(h), parseInt(m), 0);
  return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

const formatStatus = (status: string) => {
  if (status === 'Requested_by_Guest') return 'Admin Reviewing';
  if (status === 'Pending_for_Verification') return 'Awaiting OTP';
  return status;
};

const statusBadgeClass = (status: string) => {
  switch (status) {
    case 'Confirmed':
    case 'Approved':
      return 'bg-success';
    case 'Pending':
    case 'Requested_by_Guest':
      return 'bg-warning text-dark';
    case 'Pending_for_Verification':
      return 'bg-info text-dark';
    case 'Cancelled':
    case 'Rejected':
      return 'bg-danger';
    default:
      return 'bg-light text-dark';
  }
};
</script>

<style scoped>
.section {
  margin-left: 260px;
  padding: 20px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section {
    margin-left: 85px;
  }
}

.dashboard-header {
  background-color: #e5f4de;
  color: #1e4449;
  text-align: center;
  padding: 30px 15px;
  border-radius: 10px;
}

.text-dark-teal {
  color: #1e4449;
}

.btn-teal {
  background-color: #1e4449;
  color: white;
  border: none;
}

.btn-teal:hover {
  background-color: #163337;
  color: white;
}

.booking-card {
  transition: transform 0.2s ease;
}

.booking-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>
