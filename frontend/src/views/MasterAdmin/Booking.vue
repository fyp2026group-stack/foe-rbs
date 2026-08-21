<template>
  <navbar/>
  <master-admin-sidebar/>
  <div class="section">
    <!-- Modern Header Card -->
    <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item active" aria-current="page">Bookings</li>
          </ol>
        </nav>
        <h2 class="mb-0 fw-bold text-dark-teal">Booking Management</h2>
        <p class="text-muted mb-0">Monitor and manage all resource reservations across the campus.</p>
      </div>
      <div class="text-end d-none d-md-block">
        <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
          <i class="bi bi-calendar-event me-1"></i> {{ bookingStore.bookings.length }} Total
        </span>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div></div>
      <button
        class="btn btn-outline-dark-teal btn-sm"
        @click="navigatetoresource"
      >
        <i class="bi bi-list-ul me-1"></i>Book Resource
      </button>
    </div>
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading bookings...</p>
    </div>

    <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="loadBookings">
        <i class="bi bi-arrow-clockwise me-1"></i>Retry
      </button>
    </div>

    <div v-else>
      <div class="mb-4 filter-row">
        <div class="row g-3">
          
          <div class="col-sm-6 col-md-3">
            <select class="form-select" v-model="selectedResource">
              <option value="">All Resources</option>
              <option v-for="resource in uniqueResources" :key="resource" :value="resource">
                {{ resource }}
              </option>
            </select>
          </div>
          
          <div class="col-sm-6 col-md-3">
            <select class="form-select" v-model="selectedStatus">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="Confirmed">Confirmed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          
          <div class="col-sm-6 col-md-3">
              <div class="input-group">
                  <input 
                      type="date" 
                      class="form-control" 
                      v-model="startDate" 
                      placeholder="Start Date"
                  >
                  <span class="input-group-text calendar-icon-fix">
                      <i class="bi bi-calendar-range"></i>
                  </span>
              </div>
          </div>
          
          <div class="col-sm-6 col-md-3">
              <div class="input-group">
                  <input 
                      type="date" 
                      class="form-control" 
                      v-model="endDate" 
                      placeholder="End Date"
                  >
                  <span class="input-group-text calendar-icon-fix">
                      <i class="bi bi-calendar-range"></i>
                  </span>
              </div>
          </div>
          
        </div>
      </div>

      <div class="calendar-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-dark-teal" @click="navigateCalendar(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="btn-group btn-group-sm ms-3 view-switcher">
                    <button 
                        class="btn" 
                        :class="viewMode === 'month' ? 'btn-dark-teal active' : 'btn-outline-dark-teal'" 
                        @click="viewMode = 'month'"
                    >Month</button>
                    <button 
                        class="btn" 
                        :class="viewMode === 'day' ? 'btn-dark-teal active' : 'btn-outline-dark-teal'" 
                        @click="viewMode = 'day'"
                    >Day</button>
                </div>
            </div>

            <h5 class="mb-0 calendar-title-header">
                {{ viewMode === 'month' ? `${currentMonthName} ${currentYear}` : formatDateLong(focusedDate || currentDate.toISOString().split('T')[0]) }}
            </h5>

            <button class="btn btn-sm btn-outline-dark-teal" @click="navigateCalendar(1)">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <!-- Month View Grid -->
        <div v-if="viewMode === 'month'" class="calendar-grid animate-fade-in">
            <div v-for="day in weekdays" :key="day" class="calendar-header">{{ day }}</div>
            
            <div 
              v-for="day in daysInMonth" 
              :key="day.key" 
              class="calendar-day"
              :class="{ 
                  'day-outside-month': day.isOutsideMonth,
                  'day-has-booking': day.hasBooking,
                  'day-is-start': day.dateString && day.dateString === startDate,
                  'day-is-end': day.dateString && day.dateString === endDate,
                  'day-in-range': day.dateString && isDateInRange(day.dateString),
                  'day-focused': day.dateString && focusedDate === day.dateString
              }"
              @click="day.dateString && handleDayClick(day.dateString)"
              :title="day.hasBooking ? `${day.bookingCount} booking(s) on ${day.dayNumber}` : ''"
            >
              <span class="day-label" v-if="day.dateString === startDate">Start</span>
              <span class="day-label" v-else-if="day.dateString === endDate">End</span>
              <span class="day-number">{{ day.dayNumber }}</span>
              <span v-if="day.bookingCount" class="booking-badge">{{ day.bookingCount }}</span>
            </div>
        </div>

        <!-- Day/Time View Timeline -->
        <div v-else class="day-view-container animate-fade-in">
            <div class="timeline-header-row mb-3 d-flex justify-content-between align-items-center">
                <div class="text-dark-teal fw-bold">
                    <i class="bi bi-clock-history me-2"></i>Daily Schedule
                </div>
                <div class="small text-muted">
                    Total: {{ focusedDateBookings.length }} Booking(s)
                </div>
            </div>

            <div class="timeline-scroll-area">
                <div class="timeline-grid">
                    <!-- Hour Labels and Grid Lines -->
                    <div v-for="hour in 24" :key="hour-1" class="hour-row">
                        <div class="hour-label">{{ formatHour(hour-1) }}</div>
                        <div class="hour-line"></div>
                    </div>

                    <!-- Bookings on Timeline -->
                    <div class="booking-layer">
                        <div 
                            v-for="b in focusedDateBookings" 
                            :key="b.id"
                            class="timeline-booking-block"
                            :style="getBookingTimelineStyle(b)"
                            :class="getStatusClass(b.status)"
                            @click="viewBookingDetails(b.id)"
                        >
                            <div class="booking-block-content">
                                <div class="booking-block-ref">{{ b.booking_reference }}</div>
                                <div class="booking-block-resource">{{ b.resource?.name || b.details?.[0]?.item_name || 'N/A' }}</div>
                                <div class="booking-block-time">{{ b.start_time }} - {{ b.end_time }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>

      <!-- Selected Day details for Admin -->
      <div v-if="focusedDate" class="card mb-4 border-0 shadow-sm animate-fade-in day-details-panel">
        <div class="card-header bg-light-teal py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark-teal"><i class="bi bi-info-circle-fill me-2"></i>Bookings for {{ formatDateLong(focusedDate) }}</h5>
            <button class="btn-close" @click="focusedDate = ''"></button>
        </div>
        <div class="card-body">
            <div v-if="focusedDateBookings.length > 0" class="row g-3">
                <div v-for="b in focusedDateBookings" :key="b.id" class="col-md-6 col-lg-4">
                    <div class="booking-mini-card p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border">{{ b.booking_reference }}</span>
                            <span class="badge" :class="getStatusClass(b.status)">{{ b.status }}</span>
                        </div>
                        <div class="user-info small mb-2">
                            <i class="bi bi-person me-1"></i>{{ b.user_email }}
                        </div>
                        <div class="resource-info small fw-bold mb-1">
                            <i class="bi bi-box-seam me-1"></i>{{ b.resource?.name || b.details?.[0]?.item_name || 'N/A' }}
                        </div>
                        <div class="time-info small text-muted">
                            <i class="bi bi-clock me-1"></i>{{ b.start_time }} - {{ b.end_time }}
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-xs btn-outline-primary py-0 px-2" @click="viewBookingDetails(b.id)" style="font-size: 0.7rem;">View</button>
                            <button v-if="b.status === 'Pending'" class="btn btn-xs btn-outline-success py-0 px-2" @click="confirmBooking(b.id)" style="font-size: 0.7rem;">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-4 text-muted">
                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                No bookings found for this specific date.
            </div>
        </div>
      </div>

      <!-- System-Wide Resource Bookings Table -->
      <div class="table-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0 text-dark-teal"><i class="bi bi-globe me-2"></i>System-Wide Resource Bookings ({{ allResourceBookings.length }})</h5>
          <div>
            <button class="btn btn-success btn-sm me-2" @click="loadBookings" :disabled="isRefreshing">
              <span v-if="isRefreshing" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-arrow-clockwise me-1"></i>
              {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>User Email</th>
                <th>Resource</th>
                <th>Booking Date</th>
                <th>Time Slot</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in allResourceBookings" :key="booking.id">
                <td><span class="badge bg-light text-dark">{{ booking.booking_reference }}</span></td>
                <td>{{ booking.user_email }}</td>
                <td>{{ booking.resource?.name || booking.details?.[0]?.item_name || 'N/A' }}</td>
                <td>{{ formatDate(booking.booking_date) }}</td>
                <td>{{ booking.start_time }} - {{ booking.end_time }}</td>
                <td>Rs. {{ booking.total_amount }}</td>
                <td><span class="badge" :class="getStatusClass(booking.status)">{{ booking.status }}</span></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" @click="viewBookingDetails(booking.id)" title="View Details"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-danger" @click="openDeleteConfirmation(booking)" title="Delete Permanently"><i class="bi bi-trash"></i></button>
                    <template v-if="booking.status === 'Pending' || booking.status === 'Requested_by_Guest'">
                      <button class="btn btn-outline-success" @click="openConfirmConfirmation(booking)" title="Confirm"><i class="bi bi-check-circle"></i></button>
                      <button class="btn btn-outline-warning" @click="openRejectConfirmation(booking)" title="Reject"><i class="bi bi-x-circle"></i></button>
                    </template>
                  </div>
                </td>
              </tr>
              <tr v-if="allResourceBookings.length === 0">
                <td colspan="8" class="text-center py-4 text-muted">No system-wide bookings found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- My Personal Bookings Table -->
      <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0 text-dark-teal"><i class="bi bi-person-badge me-2"></i>My Personal Bookings ({{ personalBookings.length }})</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>Resource</th>
                <th>Booking Date</th>
                <th>Time Slot</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in personalBookings" :key="booking.id">
                <td><span class="badge bg-light text-dark">{{ booking.booking_reference }}</span></td>
                <td>{{ booking.resource?.name || booking.details?.[0]?.item_name || 'N/A' }}</td>
                <td>{{ formatDate(booking.booking_date) }}</td>
                <td>{{ booking.start_time }} - {{ booking.end_time }}</td>
                <td>Rs. {{ booking.total_amount }}</td>
                <td><span class="badge" :class="getStatusClass(booking.status)">{{ booking.status }}</span></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" @click="viewBookingDetails(booking.id)" title="View Details"><i class="bi bi-eye"></i></button>
                    <button v-if="booking.status === 'Pending' || booking.status === 'Pending_for_Verification'" class="btn btn-outline-danger" @click="openDeleteConfirmation(booking)" title="Cancel Booking"><i class="bi bi-trash"></i></button>
                  </div>
                </td>
              </tr>
              <tr v-if="personalBookings.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">No personal bookings found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal (Double Confirmation) -->
  <div class="modal fade" :class="{ 'show d-block': showDeleteConfirmation }" tabindex="-1" @click.self="handleCancelDeletion" style="background-color: rgba(0,0,0,0.5);" v-if="showDeleteConfirmation">
    <div class="modal-dialog delete-modal-top"> 
      <div class="modal-content">

        <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-question-circle-fill me-2"></i>Permanently Delete</h5>
                <button type="button" class="btn-close btn-close-white" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">
                  Are you sure you want to delete the booking 
                  <strong>{{ bookingToDelete?.booking_reference }}</strong> 
                  for <strong>{{ bookingToDelete?.resource?.name || bookingToDelete?.details?.[0]?.item_name || 'N/A' }}</strong>?
                </p>
                <div class="alert alert-danger mt-3" role="alert">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  This action cannot be undone!
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                <button type="button" class="btn btn-danger" @click="handleFirstConfirmation" :disabled="isDeleting">
                  <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2"></span>
                  Continue to Delete
                </button>
            </div>
        </template>

        <template v-else-if="deleteStep === 'final'">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Final Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" @click="handleCancelDeletion"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">You are about to <strong>permanently delete</strong> booking <strong>{{ bookingToDelete?.booking_reference }}</strong>.</p>
                <p class="mt-2 mb-0 text-danger">This action cannot be undone. Are you absolutely sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" @click="handleCancelDeletion">Cancel</button>
                <button type="button" class="btn btn-danger" @click="handleDeleteBooking" :disabled="isDeleting">
                  <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2"></span>
                  Yes, Delete Permanently
                </button>
            </div>
        </template>
      </div>
    </div>
  </div>

  <!-- Confirm Booking Modal (Single Confirmation) -->
  <div class="modal fade" :class="{ 'show d-block': showConfirmModal }" tabindex="-1" @click.self="closeConfirmModal" style="background-color: rgba(0,0,0,0.5);" v-if="showConfirmModal">
    <div class="modal-dialog action-modal-top" style="max-width: 400px;">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-check-circle-fill me-2"></i>Confirm Booking</h5>
          <button type="button" class="btn-close btn-close-white" @click="closeConfirmModal"></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-2">
            Are you sure you want to <strong class="text-success">confirm</strong> this booking?
          </p>
          <div class="alert alert-light border mt-3 text-start">
            <p class="mb-1"><strong>Booking Reference:</strong> {{ bookingToConfirm?.booking_reference }}</p>
            <p class="mb-1"><strong>Resource:</strong> {{ bookingToConfirm?.resource?.name || bookingToConfirm?.details?.[0]?.item_name || 'N/A' }}</p>
            <p class="mb-0"><strong>User:</strong> {{ bookingToConfirm?.user_email }}</p>
          </div>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-secondary" @click="closeConfirmModal">Cancel</button>
          <button type="button" class="btn btn-success" @click="handleConfirmBooking" :disabled="isConfirming">
            <span v-if="isConfirming" class="spinner-border spinner-border-sm me-2"></span>
            Yes, Confirm Booking
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reject Booking Modal (Single Confirmation) -->
  <div class="modal fade" :class="{ 'show d-block': showRejectModal }" tabindex="-1" @click.self="closeRejectModal" style="background-color: rgba(0,0,0,0.5);" v-if="showRejectModal">
    <div class="modal-dialog action-modal-top" style="max-width: 400px;">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reject Booking</h5>
          <button type="button" class="btn-close btn-close-white" @click="closeRejectModal"></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-2">
            Are you sure you want to <strong class="text-danger">reject</strong> this booking?
          </p>
          <div class="alert alert-light border mt-3 text-start">
            <p class="mb-1"><strong>Booking Reference:</strong> {{ bookingToReject?.booking_reference }}</p>
            <p class="mb-1"><strong>Resource:</strong> {{ bookingToReject?.resource?.name || bookingToReject?.details?.[0]?.item_name || 'N/A' }}</p>
            <p class="mb-0"><strong>User:</strong> {{ bookingToReject?.user_email }}</p>
          </div>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-secondary" @click="closeRejectModal">Cancel</button>
          <button type="button" class="btn btn-danger" @click="handleRejectBooking" :disabled="isRejecting">
            <span v-if="isRejecting" class="spinner-border spinner-border-sm me-2"></span>
            Yes, Reject Booking
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking Preview Modal - Centered on Page -->
  <teleport to="body">
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetailsModal">
      <div class="modal-container modal-container-lg">
        <div class="modal-content-wrapper">
          <div class="modal-header-custom bg-dark-teal">
            <h5 class="modal-title-custom">
              <i class="bi bi-calendar-check me-2"></i>
              Booking Details Preview
            </h5>
            <button type="button" class="btn-close-custom" @click="closeDetailsModal">×</button>
          </div>
          <div class="modal-body-custom">
            <div class="row g-4">
              <!-- Left Info Column -->
              <div class="col-md-6 border-end">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <span class="badge bg-light text-dark-teal border py-2 px-3 fs-6">
                    Ref: {{ bookingToView?.booking_reference }}
                  </span>
                  <span class="badge rounded-pill py-2 px-3" :class="getStatusClass(bookingToView?.status)">
                    {{ bookingToView?.status }}
                  </span>
                </div>
                
                <div class="info-group mb-3">
                  <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Reservation Time</label>
                  <div class="d-flex align-items-center">
                    <i class="bi bi-clock-fill text-dark-teal me-2"></i>
                    <span class="fw-bold">{{ bookingToView?.start_time }} - {{ bookingToView?.end_time }}</span>
                  </div>
                </div>

                <div class="info-group mb-3">
                  <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Booking Date</label>
                  <div class="d-flex align-items-center">
                    <i class="bi bi-calendar-event-fill text-dark-teal me-2"></i>
                    <span class="fw-bold">{{ formatDate(bookingToView?.booking_date) }}</span>
                  </div>
                </div>

                <div class="info-group mb-0">
                  <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Duration & Cost</label>
                  <div class="d-flex align-items-center">
                    <i class=" text-success me-2 fs-5"></i>
                    <span class="fw-bold fs-5 text-success">Total Amount: Rs. {{ bookingToView?.total_amount }}</span>
                  </div>
                </div>
              </div>

              <!-- Right Info Column -->
              <div class="col-md-6">
                <div class="info-group mb-4">
                  <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Resource Requested</label>
                  <div class="p-3 bg-light rounded shadow-sm border-start border-4 border-dark-teal">
                    <i class="bi bi-box-seam-fill text-dark-teal me-2"></i>
                    <span class="fw-bold text-dark-teal">
                      {{ bookingToView?.resource?.name || bookingToView?.details?.[0]?.item_name || 'Individual Item' }}
                    </span>
                  </div>
                </div>

                <div class="info-group mb-4">
                  <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Customer Information</label>
                  <div class="d-flex align-items-center p-2 rounded hover-light mb-2">
                    <i class="bi bi-envelope-at-fill text-muted me-3 fs-5"></i>
                    <div>
                      <div class="fw-bold text-dark">{{ bookingToView?.user_email }}</div>
                      <div class="extra-small text-muted">ID: {{ bookingToView?.user_id || 'Guest' }}</div>
                    </div>
                  </div>
                  <div v-if="bookingToView?.phone" class="d-flex align-items-center p-2 rounded hover-light">
                    <i class="bi bi-telephone-fill text-muted me-3 fs-5"></i>
                    <div>
                      <div class="fw-bold text-dark">{{ bookingToView?.phone }}</div>
                      <div class="extra-small text-muted">Contact Number</div>
                    </div>
                  </div>
                </div>

                <div v-if="bookingToView?.notes" class="info-group mb-0">
                  <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Additional Notes</label>
                  <div class="p-2 border rounded small italic bg-light">
                    "{{ bookingToView?.notes }}"
                  </div>
                </div>
              </div>

              <!-- Items & Accessories Row (Full Width) -->
              <div class="col-12" v-if="bookingToView?.details && bookingToView.details.length > 0">
                <hr class="my-2">
                <label class="text-muted small fw-bold text-uppercase mb-2 d-block">Included Items & Equipment</label>
                <div class="table-responsive rounded border">
                  <table class="table table-sm table-striped mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-3">Item Name</th>
                        <th>Type</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end pe-3">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in bookingToView.details" :key="item.id">
                        <td class="ps-3 fw-bold small text-dark-teal">{{ item.item_name || 'N/A' }}</td>
                        <td class="small text-muted">{{ item.item_type || 'N/A' }}</td>
                        <td class="text-center small">{{ item.quantity }}</td>
                        <td class="text-end pe-3 fw-bold small">Rs. {{ item.subtotal || item.unit_price * item.quantity }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer-custom">
            <div class="w-100 d-flex justify-content-between align-items-center">
              <div>
                <small class="text-muted">Booking Reference: {{ bookingToView?.booking_reference }}</small>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary px-4 shadow-sm" @click="closeDetailsModal">Close</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </teleport>

  <!-- Success Toast -->
  <div v-if="showSuccessToast" class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-success text-white">
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong class="me-auto">Success</strong>
        <button type="button" class="btn-close btn-close-white" @click="showSuccessToast = false"></button>
      </div>
      <div class="toast-body">
        {{ successMessage }}
      </div>
    </div>
  </div>

  <!-- Error Toast -->
  <div v-if="showErrorToast" class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-danger text-white">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong class="me-auto">Error</strong>
        <button type="button" class="btn-close btn-close-white" @click="showErrorToast = false"></button>
      </div>
      <div class="toast-body">
        {{ errorMessage }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';
import { bookingStore } from '../../store/bookingStore';

const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';

// Get current user ID
const getCurrentUserId = () => {
  const userStr = localStorage.getItem('user');
  if (userStr) {
    try {
      const user = JSON.parse(userStr);
      if (user && user.id) return user.id;
    } catch (e) {}
  }
  return localStorage.getItem('userId');
};

const getCurrentUserEmail = () => {
  const userStr = localStorage.getItem('user');
  if (userStr) {
    try {
      const user = JSON.parse(userStr);
      if (user && user.email) return user.email;
    } catch (e) {}
  }
  return localStorage.getItem('userEmail') || localStorage.getItem('email');
};

const isMasterAdmin = computed(() => {
  const roleId = localStorage.getItem('role_id') || localStorage.getItem('roleId');
  return parseInt(roleId || '0') === 1;
});

// State
const isLoading = computed(() => bookingStore.isLoading && !bookingStore.isLoaded);
const isRefreshing = ref(false);
const errorMessage = ref('');
const bookings = computed(() => bookingStore.bookings);

// Filter State
const selectedResource = ref('');
const selectedStatus = ref('');
const startDate = ref(''); 
const endDate = ref('');   
const focusedDate = ref(''); 

// Delete Modal State
const bookingToDelete = ref<any>(null);
const showDeleteConfirmation = ref(false);
const deleteStep = ref<'confirm' | 'final'>('confirm');
const isDeleting = ref(false);

// Booking Preview Modal State
const bookingToView = ref<any>(null);
const showDetailsModal = ref(false);

const viewBookingDetails = (bookingId: number) => {
  const booking = bookings.value.find((b: any) => b.id === bookingId);
  if (booking) {
    bookingToView.value = booking;
    showDetailsModal.value = true;
  }
};

const closeDetailsModal = () => {
  showDetailsModal.value = false;
  bookingToView.value = null;
};

// Confirm Modal State
const bookingToConfirm = ref<any>(null);
const showConfirmModal = ref(false);
const isConfirming = ref(false);

// Reject Modal State
const bookingToReject = ref<any>(null);
const showRejectModal = ref(false);
const isRejecting = ref(false);

// Toast State
const showSuccessToast = ref(false);
const showErrorToast = ref(false);
const successMessage = ref('');

// Calendar State
const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const currentDate = ref(new Date());
const viewMode = ref<'month' | 'day'>('month');

const navigatetoresource = () => {
    router.push('/master-admin/resource');
};

// --- Helper Functions ---
const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Confirmed': return 'bg-success';
    case 'Pending': return 'bg-warning text-dark';
    case 'Cancelled': return 'bg-danger';
    case 'Completed': return 'bg-info';
    case 'Requested_by_Guest': return 'bg-requested-guest text-white';
    default: return 'bg-secondary';
  }
};

const showSuccess = (message: string) => {
  successMessage.value = message;
  showSuccessToast.value = true;
  setTimeout(() => {
    showSuccessToast.value = false;
  }, 3000);
};

const showError = (message: string) => {
  errorMessage.value = message;
  showErrorToast.value = true;
  setTimeout(() => {
    showErrorToast.value = false;
  }, 3000);
};

// --- API Functions ---
const loadBookings = async () => {
  isRefreshing.value = true;
  errorMessage.value = '';
  
  try {
    await bookingStore.fetchAll(true);
    // Normalize resources for filtering consistency
    bookings.value.forEach((booking: any) => {
      booking.resource = booking.resource || booking.item || booking.details?.[0] || null;
    });
  } catch (error: any) {
    console.error('Error loading bookings:', error);
    errorMessage.value = 'Failed to load bookings. Please try again.';
  } finally {
    isRefreshing.value = false;
  }
};

// Confirm Booking Function
const confirmBooking = async (bookingId: number) => {
  isConfirming.value = true;
  
  try {
    const token = getAuthToken();
    
    await axios.patch(`${API_BASE_URL}/bookings/${bookingId}/status`, {
      status: 'Confirmed'
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Update store state
    const booking = bookings.value.find((b: any) => b.id === bookingId);
    if (booking) {
      bookingStore.updateBookingLocally({
        ...booking,
        status: 'Confirmed'
      });
    }
    
    showSuccess('Booking confirmed successfully!');
    closeConfirmModal();
    
    // If the details modal was open, refresh the status there too
    if (bookingToView.value && bookingToView.value.id === bookingId) {
       bookingToView.value.status = 'Confirmed';
    }
    
  } catch (error: any) {
    console.error('Error confirming booking:', error);
    showError(error.response?.data?.message || 'Failed to confirm booking');
  } finally {
    isConfirming.value = false;
  }
};

// Reject Booking Function
const rejectBooking = async (bookingId: number) => {
  isRejecting.value = true;
  
  try {
    const token = getAuthToken();
    
    await axios.patch(`${API_BASE_URL}/bookings/${bookingId}/status`, {
      status: 'Cancelled'
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Update store state
    const booking = bookings.value.find((b: any) => b.id === bookingId);
    if (booking) {
      bookingStore.updateBookingLocally({
        ...booking,
        status: 'Cancelled'
      });
    }
    
    showSuccess('Booking rejected successfully!');
    closeRejectModal();
    
    // If the details modal was open, refresh the status there too
    if (bookingToView.value && bookingToView.value.id === bookingId) {
       bookingToView.value.status = 'Cancelled';
    }
    
  } catch (error: any) {
    console.error('Error rejecting booking:', error);
    showError(error.response?.data?.message || 'Failed to reject booking');
  } finally {
    isRejecting.value = false;
  }
};

// Delete Booking Function
const deleteBooking = async (bookingId: number) => {
  isDeleting.value = true;
  
  try {
    const token = getAuthToken();
    
    await axios.delete(`${API_BASE_URL}/bookings/${bookingId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    // Remove from local state
    bookingStore.removeBookingLocally(bookingId);
    
    showSuccess('Booking deleted successfully!');
    handleCancelDeletion();
    
  } catch (error: any) {
    console.error('Error deleting booking:', error);
    
    if (error.response) {
      if (error.response.status === 404) {
        showError('Booking not found. It may have already been deleted.');
      } else if (error.response.status === 500) {
        showError('Server error. Please try again later.');
      } else if (error.response.data?.message) {
        showError(error.response.data.message);
      } else {
        showError(`Failed to delete booking: ${error.response.statusText}`);
      }
    } else if (error.request) {
      showError('No response from server. Please check your connection.');
    } else {
      showError(`Request error: ${error.message}`);
    }
    handleCancelDeletion();
  } finally {
    isDeleting.value = false;
  }
};


// --- Confirm Modal Functions ---
const openConfirmConfirmation = (booking: any) => {
  bookingToConfirm.value = booking;
  showConfirmModal.value = true;
};

const closeConfirmModal = () => {
  showConfirmModal.value = false;
  bookingToConfirm.value = null;
  isConfirming.value = false;
};

const handleConfirmBooking = () => {
  if (bookingToConfirm.value) {
    confirmBooking(bookingToConfirm.value.id);
  }
};

// --- Reject Modal Functions ---
const openRejectConfirmation = (booking: any) => {
  bookingToReject.value = booking;
  showRejectModal.value = true;
};

const closeRejectModal = () => {
  showRejectModal.value = false;
  bookingToReject.value = null;
  isRejecting.value = false;
};

const handleRejectBooking = () => {
  if (bookingToReject.value) {
    rejectBooking(bookingToReject.value.id);
  }
};

// --- Delete Modal Functions ---
const openDeleteConfirmation = (booking: any) => {
  bookingToDelete.value = booking;
  deleteStep.value = 'confirm';
  showDeleteConfirmation.value = true;
};

const handleFirstConfirmation = () => {
  deleteStep.value = 'final';
};

const handleCancelDeletion = () => {
  showDeleteConfirmation.value = false;
  bookingToDelete.value = null;
  deleteStep.value = 'confirm';
  isDeleting.value = false;
};

const handleDeleteBooking = async () => {
  if (!bookingToDelete.value) return;
  await deleteBooking(bookingToDelete.value.id);
};

// --- Filtering ---
const uniqueResources = computed(() => {
  const resources = new Set<string>();
  
  bookings.value.forEach((booking: any) => {
    // 1. Try to get name from the primary resource object
    if (booking.resource?.name) {
      resources.add(booking.resource.name);
    } 
    // 2. Otherwise, look through details for the item marked as 'resource'
    else if (booking.details && booking.details.length > 0) {
      booking.details.forEach((detail: any) => {
        if (detail.item_type === 'resource' && detail.item_name) {
          resources.add(detail.item_name);
        }
      });
    }
  });
  
  return Array.from(resources).sort();
});

const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('auth_token') || 
         localStorage.getItem('token');
};

const filteredBookings = computed(() => {
  return bookings.value.filter((booking: any) => {
    // Resource filter
    if (selectedResource.value) {
      let resourceName = '';
      if (booking.resource?.name) {
        resourceName = booking.resource.name;
      } else if (booking.details && booking.details.length > 0) {
        const resourceDetail = booking.details.find((d: any) => d.item_type === 'resource');
        resourceName = resourceDetail?.item_name || booking.details[0]?.item_name || '';
      }
      if (resourceName !== selectedResource.value) return false;
    }
    // Status filter
    if (selectedStatus.value && booking.status !== selectedStatus.value) return false;
    // Date range filter
    if (startDate.value && endDate.value) {
      if (!booking.booking_date) return false;
      try {
        const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
        if (bookingDate < startDate.value || bookingDate > endDate.value) return false;
      } catch (e) { return false; }
    }
    return true;
  });
});

const allResourceBookings = computed(() => {
  const adminEmail = getCurrentUserEmail();
  return filteredBookings.value.filter(b => b.user_email !== adminEmail);
});

const personalBookings = computed(() => {
  const adminEmail = getCurrentUserEmail();
  return filteredBookings.value.filter(b => b.user_email === adminEmail);
});

const focusedDateBookings = computed(() => {
  if (!focusedDate.value) return [];
  return bookings.value.filter((booking: any) => {
    if (!booking.booking_date) return false;
    try {
      const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
      return bookingDate === focusedDate.value;
    } catch (e) {
      return false;
    }
  });
});

const formatDateLong = (dateStr: string) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
};

// --- Calendar Functions ---
const currentMonthName = computed(() => 
  currentDate.value.toLocaleString('default', { month: 'long' })
);
const currentYear = computed(() => currentDate.value.getFullYear());

const changeMonth = (delta: number) => {
  const newMonthDate = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + delta, 1);
  currentDate.value = newMonthDate;
};

const changeDay = (delta: number) => {
  const date = focusedDate.value ? new Date(focusedDate.value) : new Date(currentDate.value);
  date.setDate(date.getDate() + delta);
  const dateString = date.toISOString().split('T')[0];
  focusedDate.value = dateString;
  
  // also update currentDate if month changes to keep calendar sync'd
  if (date.getMonth() !== currentDate.value.getMonth() || date.getFullYear() !== currentDate.value.getFullYear()) {
      currentDate.value = new Date(date.getFullYear(), date.getMonth(), 1);
  }
};

const navigateCalendar = (delta: number) => {
    if (viewMode.value === 'month') {
        changeMonth(delta);
    } else {
        changeDay(delta);
    }
};

// --- Day View Helpers ---
const formatHour = (hour: number) => {
    return `${hour.toString().padStart(2, '0')}:00`;
};

const timeToMinutes = (timeStr: string) => {
    if (!timeStr) return 0;
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + (minutes || 0);
};

const getBookingTimelineStyle = (booking: any) => {
    const startMins = timeToMinutes(booking.start_time);
    const endMins = timeToMinutes(booking.end_time);
    const durationMins = Math.max(endMins - startMins, 30); // Min 30 mins for visibility

    const topPosition = (startMins / 60) * 60; // 60px per hour
    const height = (durationMins / 60) * 60;

    // Overlap management (Simplified: calculate offset based on resource frequency)
    // In a real app, you'd calculate actual collisions. 
    // Here we use a random shift for aesthetic multiple resources.
    const resourceBookings = focusedDateBookings.value.filter((b: any) => 
        timeToMinutes(b.start_time) < endMins && timeToMinutes(b.end_time) > startMins
    );
    const index = resourceBookings.findIndex((b: any) => b.id === booking.id);
    const width = 100 / (resourceBookings.length || 1);
    const left = index * width;

    return {
        top: `${topPosition}px`,
        height: `${height}px`,
        left: `${left}%`,
        width: `${width}%`
    };
};

const parseDate = (dateString: string) => {
  return dateString.replace(/-/g, '');
};

const updateDateRange = (dateString: string) => {
  const clickedDateNum = parseDate(dateString);
  const startNum = startDate.value ? parseDate(startDate.value) : 0;

  if (!startDate.value || clickedDateNum < startNum || (startDate.value && endDate.value)) {
    startDate.value = dateString;
    endDate.value = '';
  } else if (clickedDateNum > startNum) {
    endDate.value = dateString;
  } else {
    startDate.value = '';
    endDate.value = '';
  }
};

const handleDayClick = (dateString: string) => {
  focusedDate.value = dateString;
  updateDateRange(dateString);
};

const isDateInRange = (dateString: string) => {
  const checkNum = parseDate(dateString);
  const startNum = startDate.value ? parseDate(startDate.value) : 0;
  const endNum = endDate.value ? parseDate(endDate.value) : 0;

  if (startNum && endNum && startNum < endNum) {
    return checkNum > startNum && checkNum < endNum;
  }
  return false;
};

const daysInMonth = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();

  const firstDayOfMonth = new Date(year, month, 1);
  const jsDayOfWeek = firstDayOfMonth.getDay();
  const startingDayOfWeekIndex = (jsDayOfWeek + 6) % 7;
  
  const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
  const daysInPreviousMonth = new Date(year, month, 0).getDate();

  const allDays = [];

  const monthStart = new Date(year, month, 1).toISOString().split('T')[0];
  const monthEnd = new Date(year, month + 1, 0).toISOString().split('T')[0];
  
  const monthBookings = filteredBookings.value.filter((booking: any) => {
    if (!booking.booking_date) return false;
    try {
      const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
      return bookingDate >= monthStart && bookingDate <= monthEnd;
    } catch (e) {
      return false;
    }
  });

  const bookingCountMap = new Map();
  monthBookings.forEach((booking: any) => {
    if (!booking.booking_date) return;
    try {
      const bookingDate = new Date(booking.booking_date).toISOString().split('T')[0];
      bookingCountMap.set(bookingDate, (bookingCountMap.get(bookingDate) || 0) + 1);
    } catch (e) {}
  });

  for (let i = startingDayOfWeekIndex; i > 0; i--) {
    const dayNumber = daysInPreviousMonth - i + 1;
    // Use a unique dummy date string for keys
    const dummyDate = `prev-${year}-${month}-${dayNumber}`;
    allDays.push({ dayNumber, isOutsideMonth: true, dateString: '', key: dummyDate, hasBooking: false, bookingCount: 0 });
  }

  for (let day = 1; day <= daysInCurrentMonth; day++) {
    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const bookingCount = bookingCountMap.get(dateString) || 0;
    const hasBooking = bookingCount > 0;
    
    allDays.push({ 
      dayNumber: day, 
      isOutsideMonth: false, 
      dateString, 
      key: dateString,
      hasBooking, 
      bookingCount 
    });
  }

  const totalCells = Math.ceil(allDays.length / 7) * 7;
  const remainingCells = totalCells - allDays.length;
  for (let i = 1; i <= remainingCells; i++) {
    const dummyDate = `next-${year}-${month}-${i}`;
    allDays.push({ dayNumber: i, isOutsideMonth: true, dateString: '', key: dummyDate, hasBooking: false, bookingCount: 0 });
  }

  return allDays;
});

// --- Initialize ---
onMounted(async () => {
  try {
    if (!bookingStore.isLoaded) {
      await bookingStore.fetchAll();
    }
    // Normalize resources for filtering consistency
    bookings.value.forEach((booking: any) => {
      booking.resource = booking.resource || booking.item || booking.details?.[0] || null;
    });
  } catch (error: any) {
    console.error('Initial load failed:', error);
    errorMessage.value = 'Failed to load bookings. Please refresh.';
  }
});
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

.section-title {
  color: #1e4449;
  font-weight: 600;
  margin-bottom: 24px;
}

.table-card,
.calendar-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.day-details-panel {
    border-top: 4px solid #10b981;
}

.bg-light-teal {
    background-color: #f0fdf4;
}

.booking-mini-card {
    background: white;
    transition: all 0.2s ease;
}

.booking-mini-card:hover {
    border-color: #10b981 !important;
    background-color: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.day-focused {
    box-shadow: inset 0 0 0 2px #10b981;
}

.btn-xs {
    padding: 2px 8px;
    font-size: 0.75rem;
    border-radius: 4px;
}

.text-dark-teal {
    color: #1e4449;
    font-weight: 600;
}

.input-group .form-control {
    border-right: none; 
}

.calendar-icon-fix {
    background-color: #f8f9fa; 
    color: #495057;
    border-left: none;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: min-content;
    gap: 5px;
    text-align: center;
    user-select: none;
}

.calendar-header {
    font-weight: 600;
    color: #1e4449;
    padding: 8px 0;
    font-size: 0.9em;
}

.calendar-day {
    position: relative; 
    padding: 8px 0; 
    min-height: 55px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.2s;
    font-weight: 500;
    font-size: 0.9em;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.booking-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background-color: #4BB66D;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.day-label {
    font-size: 0.65rem;
    font-weight: 700;
    line-height: 1;
    text-transform: uppercase;
    color: #1e4449;
    margin-bottom: 2px;
}
.day-number {
    line-height: 1;
}

.calendar-day:not(.day-outside-month):hover {
    background-color: #fcc30040;
    transform: scale(1.05);
}

.day-outside-month {
    color: #ccc;
    cursor: default;
}

.day-in-range {
    background-color: #e6f7ff; 
    border-radius: 0;
}
.day-is-start, .day-is-end {
    background-color: #fcc300 !important; 
    color: #1e4449 !important;
    font-weight: 700;
    border: 1px solid #1e4449;
}
.day-is-start .day-label, .day-is-end .day-label {
    color: #1e4449 !important;
}

.day-has-booking {
    background-color: #e8f5e8; 
    border: 1px solid #4BB66D;
    font-weight: 700;
}
.day-has-booking .day-label {
    color: #1e4449;
}

.table thead {
  background: #f8f9fa;
}

.table {
  font-size: 0.85rem;
}

.table th, .table td {
  padding: 0.6rem 0.5rem;
  vertical-align: middle;
}

.btn-outline-dark-teal {
    --bs-btn-color: #1e4449;
    --bs-btn-border-color: #1e4449;
    --bs-btn-hover-bg: #fcc300;
    --bs-btn-hover-color: #1e4449;
    --bs-btn-hover-border-color: #fcc300;
}

.detail-modal-top {
    max-width: 800px;
    margin-top: 50px;
}

.hover-light:hover {
    background-color: #f8f9fa;
}

.extra-small {
    font-size: 0.7rem;
}

.bg-dark-teal {
  background-color: #1e4449;
  color: white;
}

.btn-dark-teal {
    background-color: #1e4449;
    color: white;
    border-color: #1e4449;
}

.btn-dark-teal:hover {
    background-color: #143236;
    color: white;
}

.btn-outline-dark-teal {
  --bs-btn-color: #1e4449;
  --bs-btn-border-color: #1e4449;
  --bs-btn-hover-bg: #4BB66D;
  --bs-btn-hover-color: #ffffff;
  --bs-btn-hover-border-color: #4BB66D;
 
 
}

.view-switcher .btn.active {
    background-color: #1e4449;
    color: white;
}

/* Day View Timeline Styles */
.day-view-container {
    padding-top: 10px;
}

.timeline-scroll-area {
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    position: relative;
    background: #fdfdfd;
}

.timeline-grid {
    position: relative;
    height: 1440px; /* 24 hours * 60px */
}

.hour-row {
    height: 60px;
    display: flex;
    align-items: flex-start;
    border-bottom: 1px solid #f1f5f9;
}

.hour-label {
    width: 60px;
    font-size: 0.75rem;
    color: #94a3b8;
    text-align: right;
    padding-right: 10px;
    margin-top: -8px;
    background: white;
    z-index: 1;
}

.hour-line {
    flex-grow: 1;
}

.booking-layer {
    position: absolute;
    top: 0;
    left: 60px;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.timeline-booking-block {
    position: absolute;
    z-index: 10;
    pointer-events: auto;
    cursor: pointer;
    border-left: 4px solid rgba(0,0,0,0.2);
    border-radius: 4px;
    padding: 2px 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    font-size: 0.85rem;
    color: #ffffff !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-booking-block:hover {
    transform: scale(1.02);
    z-index: 20;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.booking-block-content {
    height: 100%;
}

.booking-block-ref {
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.booking-block-resource {
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #ffffff;
}

.booking-block-time {
    font-size: 0.75rem;
    color: #ffffff;
    opacity: 0.9;
}

.animate-fade-in {
    animation: fadeIn 0.4s ease;
}

.btn-success {
  background-color: #4BB66D; 
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.bg-success {
    background-color: #4BB66D !important;
}
.bg-warning {
    background-color: #ffc107 !important;
}
.bg-danger {
    background-color: #dc3545 !important;
}
.bg-info {
    background-color: #0dcaf0 !important;
}
.bg-secondary {
    background-color: #6c757d !important;
}
.text-dark { 
    color: #212529 !important;
}

.bg-requested-guest {
    background-color: #6f42c1 !important; /* Purple color */
}

.btn-group-sm .btn {
    padding: 0.25rem 0.4rem; 
    font-size: 0.8rem;
    margin-right: 2px;
}
.btn-outline-success {
    --bs-btn-color: #4BB66D;
    --bs-btn-border-color: #4BB66D;
    --bs-btn-hover-bg: #4BB66D;
    --bs-btn-hover-color: white;
}
.btn-outline-danger {
    --bs-btn-color: #dc3545;
    --bs-btn-border-color: #dc3545;
    --bs-btn-hover-bg: #dc3545;
    --bs-btn-hover-color: white;
}
.btn-outline-warning {
    --bs-btn-color: #ffc107;
    --bs-btn-border-color: #ffc107;
    --bs-btn-hover-bg: #ffc107;
    --bs-btn-hover-color: #212529;
}
.btn-outline-info {
    --bs-btn-color: #0dcaf0;
    --bs-btn-border-color: #0dcaf0;
    --bs-btn-hover-bg: #0dcaf0;
    --bs-btn-hover-color: white;
}

/* Modal Styles - FIXED CENTERING */
.modal {
    position: fixed; top: 0; left: 0; z-index: 1050; width: 100%; height: 100%; 
    overflow-x: hidden; overflow-y: auto; outline: 0; opacity: 0; transition: opacity 0.15s linear;
}
.modal.show { opacity: 1; }
.modal-dialog { position: relative; width: auto; margin: 0.5rem; pointer-events: none; transition: transform 0.3s ease-out; transform: translate(0, -50px); }
.modal.show .modal-dialog { transform: none; }
.modal-dialog-centered { display: flex; align-items: center; min-height: calc(100% - 1rem); }
.modal-content { position: relative; display: flex; flex-direction: column; width: 100%; pointer-events: auto; background-color: #ffffff; border: 1px solid rgba(0, 0, 0, 0.2); border-radius: 0.3rem; outline: 0; }

.modal-dialog.delete-modal-top,
.modal-dialog.action-modal-top { align-items: flex-start; margin-top: 50px; height: auto; }
@media (min-width: 576px) { 
    .modal-dialog.delete-modal-top,
    .modal-dialog.action-modal-top { max-width: 400px; margin: 1.75rem auto; }
}

/* New styles for centered modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.2s ease;
}

.modal-container {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalFadeIn 0.3s ease;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-container-lg {
  max-width: 900px;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-content-wrapper {
  display: flex;
  flex-direction: column;
}

.modal-header-custom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
}

.modal-title-custom {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: white;
}

.btn-close-custom {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: white;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.btn-close-custom:hover {
  opacity: 1;
}

.modal-body-custom {
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-footer-custom {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    width: 95%;
    max-height: 95vh;
  }
  
  .modal-body-custom {
    padding: 1rem;
  }
  
  .modal-header-custom {
    padding: 0.75rem 1rem;
  }
  
  .modal-footer-custom {
    padding: 0.75rem 1rem;
  }
}

.bg-warning { background-color: #ffc107 !important; }
.btn-warning { color: #212529 !important; background-color: #ffc107 !important; border-color: #ffc107 !important; }
.btn-danger { background-color: #dc3545 !important; border-color: #dc3545 !important; }
.btn-close-white { filter: invert(1); }

.toast-container {
    z-index: 1060;
}
.toast {
    min-width: 300px;
}
</style>