<template>
  <GuestLayout>
    <div class="section">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-teal" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Preparing booking interface...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMessage }}
        <button class="btn btn-sm btn-outline-danger ms-3" @click="loadResourceDetails">
          <i class="bi bi-arrow-clockwise me-1"></i>Retry
        </button>
      </div>

      <!-- Main Content -->
      <div v-else-if="resource" class="container-fluid">
        <!-- Dashboard Style Header -->
        <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white border-start border-5 border-teal">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                  <li class="breadcrumb-item"><router-link to="/guest-resources" class="text-teal text-decoration-none">Resources</router-link></li>
                  <li class="breadcrumb-item active" aria-current="page">{{ resource.name }}</li>
                </ol>
              </nav>
              <h2 class="mb-0 fw-bold text-dark-teal">Secure Reservation</h2>
              <p class="text-muted mb-0">Complete the form below to request access to this facility.</p>
            </div>
            <div class="text-end d-none d-md-block">
               <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
                 <i class="bi bi-shield-lock me-1"></i> Secure Booking
               </span>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Left Column - Resource Info & Booking History -->
          <div class="col-lg-8">
            <!-- Resource Summary Card -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
              <div class="row g-0">
                <div class="col-md-4">
                  <img :src="getImageUrl(resource)" :alt="resource.name" class="img-fluid h-100 object-fit-cover" style="min-height: 200px;">
                </div>
                <div class="col-md-8">
                  <div class="card-body">
                    <h5 class="card-title fw-bold text-dark-teal">{{ resource.name }}</h5>
                    <p class="card-text text-muted small mb-3">{{ resource.description?.substring(0, 150) }}{{ resource.description?.length > 150 ? '...' : '' }}</p>
                    <div class="d-flex gap-3 flex-wrap">
                      <div class="small"><i class="bi bi-geo-alt text-teal me-1"></i>{{ resource.location_name || 'N/A' }}</div>
                      <div class="small"><i class="bi bi-tag text-teal me-1"></i>{{ resource.category?.name || 'Unknown' }}</div>
                      <div class="small"><i class="bi bi-cash-stack text-teal me-1"></i>LKR {{ resource.base_price }}/hr</div>
                      <div class="small"><i class="bi bi-info-circle text-teal me-1"></i>Status: <span class="badge" :class="getStatusClass(resource.status)">{{ resource.status }}</span></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Booking History Section -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Booking History</h5>
                <div>
                  <button 
                    class="btn btn-sm btn-outline-primary"
                    @click="loadBookings"
                    :disabled="isLoadingBookings"
                  >
                    <i class="bi bi-arrow-clockwise" :class="{ 'fa-spin': isLoadingBookings }"></i>
                    Refresh
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div v-if="isLoadingBookings" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2 text-muted small">Loading booking history...</p>
                </div>

                <div v-else-if="bookings.length === 0" class="text-center py-5 text-muted">
                  <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                  <p class="mt-2 mb-0">No bookings found for this resource</p>
                </div>

                <div v-else class="table-responsive">
                  <table class="table table-hover">
                    <thead class="table-light">
                      <tr>
                        <th>No</th>
                        <th>Booking Date</th>
                        <th>Time Slot</th>
                        <th>Booked By</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(booking, index) in bookings" :key="booking.id">
                        <td>{{ index + 1 }}</td>
                        <td>{{ formatDate(booking.booking_date) }}</td>
                        <td>{{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</td>
                        <td>
                          <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle me-2"></i>
                            <div>
                              <div class="text-muted extra-small">{{ booking.user?.email || booking.user_email || 'N/A' }}</div>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="fw-bold text-success">
                            Rs. {{ calculateBookingAmount(booking) }}
                          </span>
                        </td>
                        <td>
                          <span class="badge" :class="getBookingStatusClass(booking.status)">
                            {{ getBookingStatusText(booking.status) }}
                          </span>
                        </td>
                        <td>
                          <small class="text-muted">
                            {{ formatDateTime(booking.created_at) }}
                          </small>
                        </td>
                        <td class="actions-cell">
                          <div class="btn-group btn-group-sm" role="group">
                            <button 
                              class="btn btn-outline-info"
                              @click="viewBookingDetails(booking)"
                              title="View Details"
                            >
                              <i class="bi bi-eye"></i>
                            </button>
                            <button 
                              v-if="booking.status === 'pending' || booking.status === 'confirmed'"
                              class="btn btn-outline-warning"
                              @click="cancelBooking(booking)"
                              title="Cancel Booking"
                            >
                              <i class="bi bi-x-circle"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Weekly Availability -->
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-white py-3 border-bottom border-light">
                <h5 class="mb-0 text-dark-teal"><i class="bi bi-calendar3 me-2"></i>Weekly Availability</h5>
              </div>
              <div class="card-body">
                <div class="row g-2">
                  <div v-for="day in sortedAvailability" :key="day.day_name" class="col">
                    <div class="text-center p-2 rounded border" :class="day.is_available ? 'bg-light-teal-soft border-teal-subtle' : 'bg-light border-light-subtle opacity-50'">
                      <div class="small fw-bold">{{ day.day_name.substring(0, 3) }}</div>
                      <div style="font-size: 0.65rem;" :class="day.is_available ? 'text-success' : 'text-danger'">
                        {{ day.is_available ? 'OPEN' : 'CLOSED' }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mt-4">
                  <p class="small text-muted mb-2">Available slots on selected days:</p>
                  <div class="d-flex flex-wrap gap-2">
                    <div v-for="day in sortedAvailability.filter(d => d.is_available && d.slots && d.slots.length > 0)" :key="'slots-'+day.day_name" class="p-2 px-3 bg-light rounded text-dark small border">
                      <strong>{{ day.day_name }}:</strong> 
                      <span v-for="(slot, idx) in day.slots" :key="idx">
                        {{ formatTimeShort(slot.start_time) }} - {{ formatTimeShort(slot.end_time) }}{{ idx < day.slots.length - 1 ? ', ' : '' }}
                      </span>
                    </div>
                    <div v-for="day in sortedAvailability.filter(d => d.is_available && (!d.slots || d.slots.length === 0))" :key="'all-day-'+day.day_name" class="p-2 px-3 bg-light rounded text-dark small border">
                      <strong>{{ day.day_name }}:</strong> All Day
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Booking Form -->
          <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
              <div class="card-header bg-teal text-white py-3 text-center">
                <h5 class="mb-0 fw-bold">Book This Resource</h5>
              </div>
              <div class="card-body p-4">
                <div class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                  <i class="bi bi-person-badge me-2 fs-5"></i>
                  <span><strong>External User (Guest)</strong> - Standard Charges Apply</span>
                </div>

                <form @submit.prevent="createBookingAndSendOTP">
                  
                  <div v-if="isResourceUnavailable" class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <span>Resource is UNAVAILABLE on this day. (Check weekly schedule)</span>
                  </div>

                  <div v-if="isBookingConflict" class="alert alert-danger py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-calendar-x-fill me-2 fs-5"></i>
                    <span>Slot UNAVAILABLE: This time is already booked and confirmed.</span>
                  </div>

                  <div v-if="bookingForm.startTime && bookingForm.endTime && bookingForm.startTime >= bookingForm.endTime" class="alert alert-danger py-2 mb-3 small d-flex align-items-center">
                    <i class="bi bi-clock-fill me-2 fs-5"></i>
                    <span>Invalid Time: End time must be after start time.</span>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Email Address</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-teal"></i></span>
                      <input type="email" class="form-control border-start-0" v-model="bookingForm.email" placeholder="name@example.com" required>
                    </div>
                    <div class="form-text x-small">Notification will be sent to this email.</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Phone Number</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-teal"></i></span>
                      <input type="tel" class="form-control border-start-0" v-model="bookingForm.phone" placeholder="+94 77 123 4567" required>
                    </div>
                    <div class="form-text x-small">Used for booking verification.</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-dark-teal">Select Date</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-day text-teal"></i></span>
                      <input type="date" class="form-control border-start-0" v-model="bookingForm.date" :min="minDate" required>
                    </div>
                  </div>

                  <div class="row g-2 mb-3">
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">Start Time (24h)</label>
                      <div class="d-flex gap-1 align-items-center">
                        <select v-model="startHour" class="form-select form-select-sm">
                          <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="fw-bold">:</span>
                        <select v-model="startMin" class="form-select form-select-sm">
                          <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-6">
                      <label class="form-label small fw-bold text-dark-teal">End Time (24h)</label>
                      <div class="d-flex gap-1 align-items-center">
                        <select v-model="endHour" class="form-select form-select-sm">
                          <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="fw-bold">:</span>
                        <select v-model="endMin" class="form-select form-select-sm">
                          <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div class="mt-3">
                    <p class="mb-1">
                      <strong>Resource Cost:</strong> 
                      <span v-if="calculatedCost">Rs. {{ calculatedCost }}</span>
                      <span v-else class="text-muted">--</span>
                    </p>
                    <small class="text-muted">Base Price: Rs. {{ resource.base_price }}/hour</small>
                  </div>

                  <div class="booking-equipment-section mb-4 pb-3 border-bottom">
                    <h6 class="border-bottom pb-2 mb-3">Add Equipment/Accessories (Optional)</h6>
                    
                    <div class="mb-3">
                      <label class="form-label">Search Equipment</label>
                      <div class="input-group">
                        <input
                          type="text"
                          class="form-control"
                          placeholder="Search equipment by name..."
                          v-model="equipmentSearch"
                          @input="searchEquipment"
                          @focus="searchEquipment"
                        >
                        <button
                          class="btn btn-outline-secondary"
                          type="button"
                          @click="clearEquipmentSearch"
                        >
                          <i class="bi bi-x"></i>
                        </button>
                      </div>
                      
                      <div v-if="showEquipmentDropdown && filteredEquipment.length > 0" class="equipment-dropdown mt-2 border rounded">
                        <div 
                          v-for="item in filteredEquipment" 
                          :key="item.id"
                          class="equipment-dropdown-item p-2 border-bottom"
                          @click="addEquipmentItem(item)"
                        >
                          <div class="d-flex justify-content-between align-items-center">
                            <div>
                              <strong>{{ item.name }}</strong>
                              <div class="small text-muted">{{ item.description }}</div>
                            </div>
                            <div class="text-end">
                              <div class="fw-bold">Rs. {{ item.price_per_hour }}/hr</div>
                              <div class="small text-muted">Available: {{ item.available_quantity }}</div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div v-if="equipmentSearch && filteredEquipment.length === 0" class="text-muted small mt-2">
                        No equipment found matching "{{ equipmentSearch }}"
                      </div>
                    </div>
                    
                    <div v-if="selectedEquipment.length > 0" class="selected-equipment-list">
                      <h6 class="mb-2">Selected Equipment:</h6>
                      <div class="list-group">
                        <div 
                          v-for="(item, index) in selectedEquipment" 
                          :key="item.id"
                          class="list-group-item p-3 mb-2 border rounded"
                        >
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                              <strong>{{ item.name }}</strong>
                              <div class="small text-muted">Rs. {{ item.price_per_hour }}/hr</div>
                            </div>
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-danger"
                              @click="removeEquipmentItem(index)"
                            >
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                          
                          <div class="row align-items-center">
                            <div class="col-6">
                              <label class="form-label small mb-1">Quantity</label>
                              <div class="input-group input-group-sm">
                                <button
                                  class="btn btn-outline-secondary"
                                  type="button"
                                  @click="decreaseQuantity(index)"
                                  :disabled="item.quantity <= 1"
                                >
                                  <i class="bi bi-dash"></i>
                                </button>
                                <input
                                  type="number"
                                  class="form-control text-center"
                                  v-model.number="item.quantity"
                                  min="1"
                                  :max="item.available_quantity"
                                  @change="validateQuantity(index)"
                                >
                                <button
                                  class="btn btn-outline-secondary"
                                  type="button"
                                  @click="increaseQuantity(index)"
                                  :disabled="item.quantity >= item.available_quantity"
                                >
                                  <i class="bi bi-plus"></i>
                                </button>
                              </div>
                            </div>
                            <div class="col-6 text-end">
                              <div class="small text-muted mb-1">Max: {{ item.available_quantity }}</div>
                              <div class="fw-bold text-success">
                                Rs. {{ calculateEquipmentItemCost(item) }}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mt-3 p-2 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="fw-medium">Equipment Total:</span>
                          <span class="fw-bold text-primary">
                            Rs. {{ equipmentTotalCost }}
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <div v-else class="text-center text-muted py-3 border rounded">
                      <i class="bi bi-tools" style="font-size: 1.5rem;"></i>
                      <p class="mt-2 mb-0">No equipment added yet</p>
                      <small>Search and add equipment from above</small>
                    </div>
                  </div>

                  <div class="cost-summary mb-4">
                    <h6 class="border-bottom pb-2">Cost Summary</h6>
                    <div class="cost-breakdown">
                      <div class="d-flex justify-content-between mb-2">
                        <span>Resource Cost:</span>
                        <span>Rs. {{ calculatedCost || 0 }}</span>
                      </div>
                      <div class="d-flex justify-content-between mb-2">
                        <span>Equipment Cost:</span>
                        <span>Rs. {{ equipmentTotalCost }}</span>
                      </div>
                      <div class="d-flex justify-content-between mb-2 border-top pt-2">
                        <span class="fw-bold">Total Cost:</span>
                        <span class="fw-bold fs-5 text-success">
                          Rs. {{ totalBookingCost }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="schedule-details mb-4 pb-3 border-bottom">
                    <h6 class="text-muted fw-bold mb-3">Weekly Availability</h6>
                    
                    <div v-if="!resource.availability || resource.availability.length === 0" class="text-muted small">
                        No schedule defined.
                    </div>
                    
                    <div v-else class="availability-list">
                      <div v-for="day in sortedAvailability" :key="day.day_name" class="day-availability mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="fw-medium text-dark">{{ day.day_name }}</span>
                          <span :class="day.is_available ? 'badge bg-success' : 'badge bg-secondary'">
                            {{ day.is_available ? 'Available' : 'Not Available' }}
                          </span>
                        </div>
                        
                        <div v-if="day.is_available && day.slots && day.slots.length > 0">
                          <div class="time-slots-container ms-2">
                            <div v-for="(slot, idx) in day.slots" :key="idx" class="time-slot mb-2">
                              <div class="d-flex align-items-center">
                                <i class="bi bi-clock text-dark-teal me-2"></i>
                                <span class="slot-time">
                                  {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                                </span>
                                <span v-if="day.slots.length > 1" class="badge bg-light text-dark border ms-2">
                                  Slot {{ idx + 1 }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <div v-else-if="day.is_available" class="text-muted small ms-2">
                          <i class="bi bi-info-circle me-1"></i> No specific time slots (available all day)
                        </div>
                        
                        <div v-else class="text-muted small ms-2">
                          <i class="bi bi-x-circle me-1"></i> Not available on this day
                        </div>
                      </div>
                    </div>
                  </div>

                  <button 
                    type="submit" 
                    class="btn btn-success w-100"
                    :disabled="isCreatingBooking || isResourceUnavailable || isBookingConflict || (bookingForm.startTime >= bookingForm.endTime)"
                  >
                    <span v-if="isCreatingBooking" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-send-check me-2"></i>
                    {{ isCreatingBooking ? 'Creating Booking...' : 'Book Now & Verify OTP' }}
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- OTP Verification Modal - RECTANGLE INPUT VERSION -->
      <div v-if="showOTPModal" class="modal-overlay-otp">
        <div class="modal-container-otp">
          <div class="modal-header-otp">
            <div class="header-icon">
              <i class="bi bi-shield-check"></i>
            </div>
            <h3 class="modal-title-otp">Verify Your Identity</h3>
            <button type="button" class="btn-close-otp" @click="closeOTPModal">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="modal-body-otp">
            <div class="otp-info-section">
              <div class="info-icon">
                <i class="bi bi-envelope-paper"></i>
              </div>
              <p class="info-text">
                We've sent a verification code to<br>
                <strong>{{ bookingForm.email }}</strong>
              </p>
              <div v-if="otpSentSuccess" class="alert-success-otp">
                <i class="bi bi-check-circle-fill"></i> OTP sent successfully!
              </div>
            </div>

            <!-- SINGLE RECTANGLE OTP INPUT FIELD -->
            <div class="otp-rectangle-container">
              <label class="otp-label">Enter Verification Code</label>
              <div class="otp-rectangle-input">
                <input
                  type="text"
                  class="otp-rectangle-field"
                  v-model="otpCode"
                  maxlength="6"
                  placeholder="••••••"
                  @input="onOtpRectangleInput"
                  :disabled="isVerifyingOTP"
                  autofocus
                />
                <div class="otp-rectangle-highlight"></div>
              </div>
              <p class="otp-hint">Enter the 6-digit code sent to your email</p>
            </div>

            <!-- Timer -->
            <div class="otp-timer-section">
              <div class="timer-circle" :class="{ 'timer-expired': otpExpired }">
                <i class="bi bi-clock-history"></i>
                <span>{{ formatCountdownTimer() }}</span>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="otpError" class="error-message-otp">
              <i class="bi bi-exclamation-triangle-fill"></i>
              {{ otpError }}
            </div>
          </div>

          <div class="modal-footer-otp">
            <button 
              type="button" 
              class="btn-resend-otp"
              @click="resendOTP"
              :disabled="!otpExpired || isResendingOTP"
            >
              <i class="bi bi-arrow-repeat" :class="{ 'fa-spin': isResendingOTP }"></i>
              {{ isResendingOTP ? 'Sending...' : 'Resend Code' }}
            </button>
            <div class="footer-buttons">
              <button 
                type="button" 
                class="btn-cancel-otp"
                @click="closeOTPModal"
                :disabled="isVerifyingOTP"
              >
                Cancel
              </button>
              <button 
                type="button" 
                class="btn-verify-otp"
                @click="verifyOTPAndConfirmBooking"
                :disabled="!isOtpComplete || isVerifyingOTP"
              >
                <span v-if="isVerifyingOTP" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check2-circle me-2"></i>
                {{ isVerifyingOTP ? 'Verifying...' : 'Verify & Confirm' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Modal -->
      <div v-if="showSuccessModal" class="modal-overlay">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-check-circle-fill me-2"></i>Booking Confirmed Successfully!
            </h5>
          </div>
          <div class="modal-body text-center">
            <div class="success-icon mb-3">
              <i class="bi bi-check-circle" style="font-size: 4rem; color: #4BB66D;"></i>
            </div>
            <h6 class="fw-bold mb-3">Your booking has been confirmed!</h6>
            
            <div class="booking-details bg-light p-3 rounded mb-3">
              <p class="mb-2"><strong>Resource:</strong> {{ resource?.name }}</p>
              <p class="mb-2"><strong>Date:</strong> {{ bookingForm.date }}</p>
              <p class="mb-2"><strong>Time:</strong> {{ bookingForm.startTime }} - {{ bookingForm.endTime }}</p>
              <div v-if="selectedEquipment.length > 0" class="mb-2">
                <strong>Equipment:</strong>
                <ul class="mb-0 ps-3 small">
                  <li v-for="item in selectedEquipment" :key="item.id">
                    {{ item.name }} (Qty: {{ item.quantity }})
                  </li>
                </ul>
              </div>
              <p class="mb-0"><strong>Total Cost:</strong> Rs. {{ totalBookingCost }}</p>
            </div>
            
            <p class="text-muted small">
              A confirmation email has been sent to <strong>{{ bookingForm.email }}</strong>
            </p>
            <div v-if="confirmedBookingReference" class="alert alert-info mt-3">
              <i class="bi bi-info-circle me-2"></i>
              Booking Reference: <strong>{{ confirmedBookingReference }}</strong>
              <br>
              <small>Status: <span class="badge status-confirmed">Confirmed</span></small>
            </div>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-outline-success" @click="closeSuccessModal">
              <i class="bi bi-calendar-plus me-2"></i>Book Another
            </button>
          </div>
        </div>
      </div>

      <!-- Booking Details Modal -->
      <div v-if="selectedBooking" class="modal-overlay" @click.self="selectedBooking = null">
        <div class="modal-container booking-details-modal">
          <div class="modal-header-custom">
            <h5 class="modal-title-custom">
              <i class="bi bi-calendar-check-fill me-2"></i> Booking Details
            </h5>
            <button type="button" class="btn-close-custom" @click="selectedBooking = null">×</button>
          </div>
          
          <div class="modal-body-custom">
            <div class="status-badge-wrapper mb-4">
              <span class="status-badge" :class="getBookingStatusClass(selectedBooking.status)">
                <i class="bi" :class="getStatusIcon(selectedBooking.status)"></i>
                {{ getBookingStatusText(selectedBooking.status) }}
              </span>
            </div>

            <div class="info-card mb-4">
              <div class="info-label">
                <i class="bi bi-upc-scan"></i> Booking Reference
              </div>
              <div class="info-value reference-value">
                {{ selectedBooking.booking_reference || 'N/A' }}
              </div>
            </div>

            <div class="row g-4">
              <div class="col-md-6">
                <div class="info-section">
                  <h6 class="section-title">
                    <i class="bi bi-info-circle-fill"></i> Booking Information
                  </h6>
                  <div class="info-card">
                    <div class="info-row">
                      <div class="info-label"><i class="bi bi-calendar-date"></i> Date</div>
                      <div class="info-value">{{ formatDate(selectedBooking.booking_date) }}</div>
                    </div>
                    <div class="info-row">
                      <div class="info-label"><i class="bi bi-clock-history"></i> Time Slot</div>
                      <div class="info-value">{{ formatTime(selectedBooking.start_time) }} - {{ formatTime(selectedBooking.end_time) }}</div>
                    </div>
                    <div class="info-row">
                      <div class="info-label"><i class="bi bi-hourglass-split"></i> Duration</div>
                      <div class="info-value">{{ calculateDuration(selectedBooking.start_time, selectedBooking.end_time) }} hours</div>
                    </div>
                    <div class="info-row">
                      <div class="info-label"><i class="bi bi-cash-stack"></i> Amount</div>
                      <div class="info-value amount-value">Rs. {{ calculateBookingAmount(selectedBooking) }}</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="info-section">
                  <h6 class="section-title">
                    <i class="bi bi-person-badge"></i> Customer Information
                  </h6>
                  <div class="info-card">
                    <div class="info-row">
                      <div class="info-label"><i class="bi bi-person-circle"></i> Name</div>
                      <div class="info-value">{{ selectedBooking.user?.name || 'Guest User' }}</div>
                    </div>
                    <div class="info-row">
                      <div class="info-label"><i class="bi bi-envelope-at"></i> Email</div>
                      <div class="info-value">{{ selectedBooking.user?.email || selectedBooking.user_email || 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                      <div class="info-label"><i class="bi bi-telephone"></i> Phone</div>
                      <div class="info-value">{{ selectedBooking.phone || selectedBooking.user?.phone || 'N/A' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="info-section mt-4">
              <h6 class="section-title">
                <i class="bi bi-building"></i> Resource Details
              </h6>
              <div class="info-card">
                <div class="info-row">
                  <div class="info-label"><i class="bi bi-box-seam"></i> Resource</div>
                  <div class="info-value resource-name">{{ resource?.name }}</div>
                </div>
                <div class="info-row">
                  <div class="info-label"><i class="bi bi-tag"></i> Category</div>
                  <div class="info-value">{{ resource?.category?.name || 'N/A' }}</div>
                </div>
                <div class="info-row">
                  <div class="info-label"><i class="bi bi-currency-rupee"></i> Rate</div>
                  <div class="info-value">Rs. {{ resource?.base_price }}/hour</div>
                </div>
              </div>
            </div>

            <div v-if="selectedBooking.details && selectedBooking.details.filter(d => d.item_type === 'equipment').length > 0" class="info-section mt-4">
              <h6 class="section-title">
                <i class="bi bi-tools"></i> Equipment Items
              </h6>
              <div class="equipment-list">
                <div v-for="item in selectedBooking.details.filter(d => d.item_type === 'equipment')" :key="item.id" class="equipment-item">
                  <div class="equipment-info">
                    <span class="equipment-name">{{ item.item_name || 'Equipment' }}</span>
                    <span class="equipment-qty">x{{ item.quantity }}</span>
                  </div>
                  <div class="equipment-price">Rs. {{ item.subtotal }}</div>
                </div>
              </div>
            </div>

            <div v-if="selectedBooking.notes" class="info-section mt-4">
              <h6 class="section-title">
                <i class="bi bi-chat-text"></i> Additional Notes
              </h6>
              <div class="notes-box">
                {{ selectedBooking.notes }}
              </div>
            </div>

            <div class="info-section mt-4">
              <h6 class="section-title">
                <i class="bi bi-clock-history"></i> Booking Timeline
              </h6>
              <div class="timeline">
                <div class="timeline-item">
                  <div class="timeline-icon bg-success">
                    <i class="bi bi-check-lg"></i>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-title">Booking Created</div>
                    <div class="timeline-date">{{ formatDateTime(selectedBooking.created_at) }}</div>
                  </div>
                </div>
                <div v-if="selectedBooking.confirmed_at" class="timeline-item">
                  <div class="timeline-icon bg-primary">
                    <i class="bi bi-check2-circle"></i>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-title">Booking Confirmed</div>
                    <div class="timeline-date">{{ formatDateTime(selectedBooking.confirmed_at) }}</div>
                  </div>
                </div>
                <div v-if="selectedBooking.cancelled_at" class="timeline-item">
                  <div class="timeline-icon bg-danger">
                    <i class="bi bi-x-lg"></i>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-title">Booking Cancelled</div>
                    <div class="timeline-date">{{ formatDateTime(selectedBooking.cancelled_at) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="modal-footer-custom">
            <button type="button" class="btn-close-modal" @click="selectedBooking = null">
              <i class="bi bi-x-lg me-1"></i> Close
            </button>
          </div>
        </div>
      </div>

    </div>
  </GuestLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import GuestLayout from '../../layouts/GuestLayout.vue';
import { bookingStore } from '../../store/bookingStore';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';

// Helper to get auth token
const getAuthToken = () => localStorage.getItem('token') || '';

// OTP Code - Single rectangle input
const otpCode = ref('');

// Bookings computed property
const bookings = computed(() => {
  if (!resource.value) return [];
  const currentResourceId = resource.value.id;
  return bookingStore.bookings.filter((b: any) => {
    return b.details && b.details.some((detail: any) => 
      detail.item_type === 'resource' && Number(detail.item_id) === Number(currentResourceId)
    );
  });
});

// Get status icon for modal
const getStatusIcon = (status: string) => {
  switch (status) {
    case 'pending': return 'bi-clock-history';
    case 'confirmed': return 'bi-check-circle-fill';
    case 'cancelled': return 'bi-x-circle-fill';
    case 'completed': return 'bi-check2-all';
    default: return 'bi-question-circle';
  }
};

// Booking Conflict Validation
const isBookingConflict = computed(() => {
  if (!resource.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  
  const selectedDateStr = bookingForm.value.date;
  const selectedStart = bookingForm.value.startTime.substring(0, 5);
  const selectedEnd = bookingForm.value.endTime.substring(0, 5);
  
  return bookings.value.some((b: any) => {
    const status = (b.status || '').toLowerCase();
    if (status !== 'confirmed' && status !== 'approved') return false;
    
    let bDateStr = '';
    if (b.booking_date) {
      const bDate = new Date(b.booking_date);
      bDateStr = bDate.toISOString().split('T')[0];
    }
    
    if (bDateStr !== selectedDateStr) return false;
    
    const bStart = (b.start_time || '').substring(0, 5);
    const bEnd = (b.end_time || '').substring(0, 5);
    
    if (!bStart || !bEnd) return false;
    
    const overlap = (selectedStart < bEnd) && (bStart < selectedEnd);
    return overlap;
  });
});

const sortedAvailability = computed(() => {
  if (!resource.value?.availability) return [];
  const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  return [...resource.value.availability].sort((a, b) => 
    daysOrder.indexOf(a.day_name) - daysOrder.indexOf(b.day_name)
  );
});

const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const isResourceUnavailable = computed(() => {
  if (!resource.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  
  const selectedDate = new Date(bookingForm.value.date);
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(
    (day: any) => day.day_name.toLowerCase() === selectedDayName.toLowerCase()
  );
  
  if (!dayAvailability || !dayAvailability.is_available) return true;
  
  if (dayAvailability.slots && dayAvailability.slots.length > 0) {
    const selectedStart = bookingForm.value.startTime.substring(0, 5);
    const selectedEnd = bookingForm.value.endTime.substring(0, 5);
    
    return !dayAvailability.slots.some((slot: any) => {
      const slotStart = slot.start_time.substring(0, 5);
      const slotEnd = slot.end_time.substring(0, 5);
      return selectedStart >= slotStart && selectedEnd <= slotEnd;
    });
  }
  
  return false;
});

const otpExpired = computed(() => otpTimer.value <= 0);
const isOtpComplete = computed(() => otpCode.value.length === 6);

const calculateBookingDuration = (): number => {
  if (!bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return hours;
};

const calculateAmountWithUserType = (baseAmount: number): number => {
  return baseAmount;
};

const calculatedCost = computed(() => {
  if (!resource.value || !bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  try {
    const hours = calculateBookingDuration();
    const baseAmount = Math.round(hours * (resource.value.base_price || 0));
    return calculateAmountWithUserType(baseAmount) || 0;
  } catch (e) {
    return 0;
  }
});

const equipmentTotalCost = computed(() => {
  if (!selectedEquipment.value.length) return 0;
  try {
    const hours = calculateBookingDuration();
    const total = selectedEquipment.value.reduce((total, item) => {
      return total + ((item.price_per_hour || 0) * (item.quantity || 0) * hours);
    }, 0);
    return calculateAmountWithUserType(total) || 0;
  } catch (e) {
    return 0;
  }
});

const totalBookingCost = computed(() => {
  return (Number(calculatedCost.value) || 0) + (Number(equipmentTotalCost.value) || 0);
});

// State
const resource = ref<any>(null);
const isLoading = ref(true);
const isLoadingBookings = ref(false);
const isLoadingEquipment = ref(false);
const errorMessage = ref('');
const selectedBooking = ref<any>(null);

// Equipment & Search
const availableEquipment = ref<any[]>([]);
const filteredEquipment = ref<any[]>([]);
const selectedEquipment = ref<any[]>([]);
const equipmentSearch = ref('');
const showEquipmentDropdown = ref(false);

// OTP & Modals
const showOTPModal = ref(false);
const showSuccessModal = ref(false);
const otpError = ref('');
const isVerifyingOTP = ref(false);
const isCreatingBooking = ref(false);
const isResendingOTP = ref(false);
const otpTimer = ref(300);
const otpTimerInterval = ref<any>(null);
const pendingBookingId = ref<number | null>(null);
const confirmedBookingReference = ref<string>('');
const otpSentSuccess = ref(false);

// Booking Form
const bookingForm = ref({
  email: '',
  phone: '',
  date: new Date().toISOString().split('T')[0],
  startTime: '08:00',
  endTime: '10:00',
  purpose: ''
});

// Time Helpers
const hourOptions = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const minuteOptions = ['00', '15', '30', '45'];
const startHour = ref('08');
const startMin = ref('00');
const endHour = ref('10');
const endMin = ref('00');

// Sync time selects with bookingForm
watch([startHour, startMin], () => { bookingForm.value.startTime = `${startHour.value}:${startMin.value}`; });
watch([endHour, endMin], () => { bookingForm.value.endTime = `${endHour.value}:${endMin.value}`; });

// OTP Rectangle Input Handler
const onOtpRectangleInput = (event: any) => {
  const value = event.target.value.replace(/\D/g, '').slice(0, 6);
  otpCode.value = value;
};

// Formatting Functions
const formatTime = (time: string | null): string => {
  if (!time) return '00:00';
  return time.substring(0, 5); 
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatDateTime = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatTimeShort = (time: string) => {
  if (!time) return '';
  return time.substring(0, 5);
};

const formatCountdownTimer = () => {
  const m = Math.floor(otpTimer.value / 60);
  const s = otpTimer.value % 60;
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

const calculateDuration = (startTime: string, endTime: string): string => {
  const start = new Date(`2000-01-01T${startTime}`);
  const end = new Date(`2000-01-01T${endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return hours.toFixed(1);
};

const calculateBookingAmount = (booking: any): number => {
  if (booking.total_amount) {
    return booking.total_amount;
  }
  
  if (booking.details && booking.details.length > 0) {
    return booking.details.reduce((sum: number, detail: any) => sum + detail.subtotal, 0);
  }
  
  return booking.total_amount || 0;
};

const getBookingStatusClass = (status: string) => {
  switch (status) {
    case 'pending': return 'status-pending';
    case 'confirmed': return 'status-confirmed';
    case 'cancelled': return 'status-cancelled';
    case 'completed': return 'status-completed';
    default: return 'bg-secondary';
  }
};

const getBookingStatusText = (status: string) => {
  switch (status) {
    case 'pending': return 'Pending';
    case 'confirmed': return 'Confirmed';
    case 'cancelled': return 'Cancelled';
    case 'completed': return 'Completed';
    default: return status.charAt(0).toUpperCase() + status.slice(1);
  }
};

// Main Functions
const loadResourceDetails = async () => {
  isLoading.value = true;
  try {
    const token = getAuthToken();
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await axios.get(`${API_BASE_URL}/resources/${route.params.id}`, { headers });
    resource.value = res.data.resource || res.data;
    
    if (resource.value.availability) {
      resource.value.availability = resource.value.availability.map((day: any) => {
        if (day.slots && Array.isArray(day.slots)) {
          return day;
        }
        const slots = [];
        if (day.start_time && day.end_time) {
          slots.push({
            start_time: day.start_time,
            end_time: day.end_time
          });
        }
        return { ...day, slots };
      });
    }
    
    await bookingStore.fetchByResource(resource.value.id);
  } catch (e) {
    errorMessage.value = "Could not load resource details.";
  } finally {
    isLoading.value = false;
  }
};

const loadBookings = async () => {
  if (!resource.value) return;
  
  isLoadingBookings.value = true;
  
  try {
    if (resource.value) {
      await bookingStore.fetchByResource(resource.value.id);
    }
  } catch (error: any) {
    console.error('Error loading bookings:', error);
  } finally {
    isLoadingBookings.value = false;
  }
};

const createBooking = async () => {
  if (!resource.value) {
    throw new Error('Resource not loaded');
  }
  
  if (isBookingConflict.value) {
    throw new Error('This time slot is already booked and confirmed. Please choose another time.');
  }
  
  try {
    const token = getAuthToken();
    
    const bookingPayload: any = {
      user_id: 0,
      user_email: bookingForm.value.email,
      phone: bookingForm.value.phone,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.purpose || 'Guest Reservation',
      total_amount: totalBookingCost.value,
      resources: [
        {
          resource_id: resource.value.id
        }
      ],
      booking_items: []
    };
    
    if (selectedEquipment.value.length > 0) {
      selectedEquipment.value.forEach(item => {
        bookingPayload.booking_items.push({
          item_id: item.id,
          item_type: 'equipment',
          quantity: item.quantity,
          price_per_hour: item.price_per_hour
        });
      });
    }
    
    const response = await axios.post(`${API_BASE_URL}/bookings`, bookingPayload, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    const data = response.data;
    if (data.booking) {
      pendingBookingId.value = data.booking.id;
      bookingStore.updateBookingLocally(data.booking);
    } else if (data.booking_id) {
      pendingBookingId.value = data.booking_id;
    } else if (data.id) {
      pendingBookingId.value = data.id;
      bookingStore.updateBookingLocally(data);
    }
    
    return response.data;
    
  } catch (error: any) {
    console.error('Error creating booking:', error);
    
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      if (errors) {
        throw new Error(Object.values(errors).flat().join(', '));
      } else if (error.response.data.message) {
        throw new Error(error.response.data.message);
      }
    } else if (error.response?.data?.message) {
      throw new Error(error.response.data.message);
    }
    throw error;
  }
};

const createBookingAndSendOTP = async () => {
  if (!bookingForm.value.email || !bookingForm.value.phone) {
    errorMessage.value = "Email and Phone Number are required.";
    return;
  }
  
  if (!bookingForm.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    errorMessage.value = 'Please enter a valid email address';
    return;
  }
  
  if (bookingForm.value.startTime >= bookingForm.value.endTime) {
    errorMessage.value = 'End time must be after start time';
    return;
  }
  
  const selectedDate = new Date(bookingForm.value.date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  if (selectedDate < today) {
    errorMessage.value = 'Cannot book for past dates';
    return;
  }
  
  if (isResourceUnavailable.value) {
    errorMessage.value = 'Resource is not available during the selected time';
    return;
  }
  
  if (isBookingConflict.value) {
    alert("This time slot is already booked and confirmed for this resource. Please choose another time.");
    errorMessage.value = 'Time slot is already booked and confirmed.';
    return;
  }
  
  for (const item of selectedEquipment.value) {
    if (item.quantity > item.available_quantity) {
      errorMessage.value = `Quantity for ${item.name} exceeds available quantity (${item.available_quantity})`;
      return;
    }
  }

  isCreatingBooking.value = true;
  errorMessage.value = '';
  
  try {
    await createBooking();
    
    if (pendingBookingId.value) {
      otpSentSuccess.value = true;
      otpCode.value = '';
      showOTPModal.value = true;
      startOTPTimer();
      
      await nextTick();
      const rectangleInput = document.querySelector('.otp-rectangle-field') as HTMLInputElement;
      if (rectangleInput) rectangleInput.focus();
    }
    
  } catch (error: any) {
    console.error('Error in booking flow:', error);
    errorMessage.value = error.message || 'Failed to create booking. Please try again.';
  } finally {
    isCreatingBooking.value = false;
  }
};

const verifyOTPAndConfirmBooking = async () => {
  const code = otpCode.value;
  if (code.length < 6) {
    otpError.value = 'Please enter complete 6-digit code';
    return;
  }

  isVerifyingOTP.value = true;
  otpError.value = '';

  try {
    const token = getAuthToken();
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`, 
      { otp_code: code },
      { headers }
    );

    confirmedBookingReference.value = response.data.booking?.booking_reference || 'REF-GUEST';
    showOTPModal.value = false;
    showSuccessModal.value = true;
    
    await loadBookings();
  } catch (error: any) {
    otpError.value = error.response?.data?.message || 'Invalid verification code';
    otpCode.value = '';
  } finally {
    isVerifyingOTP.value = false;
  }
};

const cancelBooking = async (booking: any) => {
  if (!confirm('Are you sure you want to cancel this booking?')) return;
  try {
    const token = getAuthToken();
    await axios.post(`${API_BASE_URL}/bookings/${booking.id}/cancel`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    });
    alert('Booking cancelled successfully');
    await loadBookings();
  } catch (e) {
    alert('Failed to cancel booking');
  }
};

const viewBookingDetails = (booking: any) => {
  selectedBooking.value = booking;
};

const startOTPTimer = () => {
  otpTimer.value = 300;
  if (otpTimerInterval.value) clearInterval(otpTimerInterval.value);
  otpTimerInterval.value = setInterval(() => {
    if (otpTimer.value > 0) otpTimer.value--;
  }, 1000);
};

const resendOTP = async () => {
  if (!pendingBookingId.value) return;
  isResendingOTP.value = true;
  otpError.value = '';
  try {
    const token = getAuthToken();
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/resend-otp`, {
        email: bookingForm.value.email
    }, { headers });
    
    startOTPTimer();
    otpCode.value = '';
    otpSentSuccess.value = true;
    otpError.value = '';
    
  } catch (error: any) {
    otpError.value = error.response?.data?.message || 'Failed to resend code';
  } finally {
    isResendingOTP.value = false;
  }
};

const closeOTPModal = () => {
  showOTPModal.value = false;
  otpError.value = '';
  otpCode.value = '';
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  router.push('/guest-resources');
};

// Equipment Functions
const loadAvailableEquipment = async () => {
  isLoadingEquipment.value = true;
  try {
    const token = getAuthToken();
    const params = {
      date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime
    };
    
    const headers: any = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const response = await axios.get(`${API_BASE_URL}/booking-items/availability`, {
      params,
      headers
    });
    
    const equipmentData = response.data;
    if (Array.isArray(equipmentData)) {
      availableEquipment.value = equipmentData.filter((item: any) => 
        item.status === 'Available' && item.available_quantity > 0
      );
    } else {
      availableEquipment.value = [];
    }
  } catch (error) {
    console.error('Error loading equipment:', error);
  } finally {
    isLoadingEquipment.value = false;
  }
};

const searchEquipment = () => {
  const searchTerm = equipmentSearch.value.toLowerCase().trim();
  filteredEquipment.value = availableEquipment.value.filter(item => {
    const nameMatch = item.name.toLowerCase().includes(searchTerm);
    return !searchTerm || nameMatch;
  });
  
  // Reset dropdown position
  showEquipmentDropdown.value = true;
  
  // Ensure dropdown appears above other content
  nextTick(() => {
    const dropdown = document.querySelector('.equipment-dropdown');
    if (dropdown) {
      const rect = dropdown.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      if (rect.bottom > viewportHeight - 50) {
        dropdown.style.top = 'auto';
        dropdown.style.bottom = '100%';
        dropdown.style.marginTop = '0';
        dropdown.style.marginBottom = '5px';
      } else {
        dropdown.style.top = '100%';
        dropdown.style.bottom = 'auto';
        dropdown.style.marginTop = '5px';
        dropdown.style.marginBottom = '0';
      }
    }
  });
};

const clearEquipmentSearch = () => {
  equipmentSearch.value = '';
  filteredEquipment.value = [];
  showEquipmentDropdown.value = false;
};

const addEquipmentItem = (item: any) => {
  const existingIndex = selectedEquipment.value.findIndex(selected => selected.id === item.id);
  if (existingIndex !== -1) {
    if (selectedEquipment.value[existingIndex].quantity < item.available_quantity) {
      selectedEquipment.value[existingIndex].quantity++;
    }
  } else {
    selectedEquipment.value.push({ ...item, quantity: 1 });
  }
  clearEquipmentSearch();
};

const removeEquipmentItem = (index: number) => {
  selectedEquipment.value.splice(index, 1);
};

const increaseQuantity = (index: number) => {
  const item = selectedEquipment.value[index];
  if (item.quantity < item.available_quantity) {
    selectedEquipment.value[index].quantity++;
  }
};

const decreaseQuantity = (index: number) => {
  if (selectedEquipment.value[index].quantity > 1) {
    selectedEquipment.value[index].quantity--;
  }
};

const validateQuantity = (index: number) => {
  const item = selectedEquipment.value[index];
  if (item.quantity < 1) {
    selectedEquipment.value[index].quantity = 1;
  } else if (item.quantity > item.available_quantity) {
    selectedEquipment.value[index].quantity = item.available_quantity;
    alert(`Maximum available quantity is ${item.available_quantity}`);
  }
};

const calculateEquipmentItemCost = (item: any): number => {
  const hours = calculateBookingDuration();
  return Math.round(item.price_per_hour * item.quantity * hours);
};

// Helper Functions
const getImageUrl = (res: any) => {
  if (res?.images && res.images.length > 0) {
    return `${API_BASE_URL}/resources/storage/${res.images[0].file_path}`;
  }
  return 'https://via.placeholder.com/600x400?text=No+Image';
};

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Active': return 'bg-success';
    case 'Maintenance': return 'bg-warning text-dark';
    default: return 'bg-secondary';
  }
};

// Watchers
watch([() => bookingForm.value.date, () => bookingForm.value.startTime, () => bookingForm.value.endTime], () => {
  if (bookingForm.value.date && bookingForm.value.startTime && bookingForm.value.endTime && bookingForm.value.startTime < bookingForm.value.endTime) {
    loadAvailableEquipment();
  }
});

onMounted(() => {
  loadResourceDetails();
});
</script>

<style scoped>
.text-dark-teal { color: #1a3a3d; }
.text-teal { color: #1e4449; }
.bg-teal { background-color: #1e4449; }
.bg-light-teal { background-color: #e5f4de; }
.bg-light-teal-soft { background-color: #f7fdf4; }
.bg-light-teal-hint { background-color: #f9fbf8; }
.border-teal { border-color: #1e4449 !important; }
.border-teal-subtle { border-color: #d1e7dd !important; }

.section {
  margin-left: 260px;
  padding: 24px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .section { margin-left: 85px; }
}

.dashboard-header-modern {
    background: linear-gradient(to right, #ffffff, #f7fdf4);
}

.btn-teal-modern {
    background: linear-gradient(135deg, #1e4449 0%, #2c5f65 100%);
    color: white;
    border: none;
    transition: all 0.2s ease;
    font-weight: 600;
}

.btn-teal-modern:hover {
    background: linear-gradient(135deg, #2c5f65 0%, #1e4449 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 68, 73, 0.2);
}

.btn-teal-modern:disabled {
    opacity: 0.6;
    transform: none;
}

.cursor-pointer { cursor: pointer; }
.hover-bg-teal-light:hover { background-color: #e5f4de; }

.equipment-dropdown {
    position: absolute;
    z-index: 1000;
    background: white;
    width: calc(100% - 2px);
    max-height: 200px;
    overflow-y: auto;
}

/* ========== OTP MODAL STYLES ========== */
.modal-overlay-otp {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    padding: 20px;
}

.modal-container-otp {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 32px;
    width: 100%;
    max-width: 480px;
    animation: modalSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header-otp {
    background: linear-gradient(135deg, #1e4449 0%, #2a6b6b 100%);
    padding: 1.25rem 1.25rem 1rem;  /* 1.75rem 1.5rem 1.5rem සිට */
    text-align: center;
    position: relative;
}

.header-icon {
    width: 52px;
    height: 52px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
}

.header-icon i {
    font-size: 2rem;
    color: white;
}

.modal-title-otp {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin: 0;
    letter-spacing: -0.5px;
}

.btn-close-otp {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.15);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
}

.btn-close-otp:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.modal-body-otp {
    padding: 1.25rem 1.5rem;
}

.otp-info-section {
    text-align: center;
    margin-bottom: 1rem;
}

.info-icon {
    width: 48px;
    height: 48px;
    background: #e8f5e9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.info-icon i {
    font-size: 1.5rem;
    color: #2e7d32;
}

.info-text {
    color: #475569;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.info-text strong {
    color: #1e4449;
    font-weight: 600;
}

.alert-success-otp {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 0.5rem;
}

/* RECTANGLE OTP INPUT FIELD */
.otp-rectangle-container {
    margin-bottom: 1.75rem;
}

.otp-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.75rem;
    text-align: center;
}

.otp-rectangle-input {
    position: relative;
}

.otp-rectangle-field {
    width: 100%;
    padding: 0.8rem 1rem;  
    font-size: 1.35rem;    
    font-weight: 600;
    letter-spacing: 0.4rem; 
    text-align: center;
    border: 2px solid #e2e8f0;
    border-radius: 14px;    
    background: white;
    transition: all 0.2s ease;
    font-family: 'Courier New', monospace;
}

.otp-rectangle-field:focus {
    outline: none;
    border-color: #1e4449;
    box-shadow: 0 0 0 4px rgba(30, 68, 73, 0.1);
}

.otp-rectangle-field::placeholder {
    letter-spacing: 0.25rem;
    font-size: 1.25rem;
    color: #cbd5e1;
}

.otp-rectangle-highlight {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #1e4449, #4BB66D);
    transition: width 0.3s ease;
    border-radius: 3px;
}

.otp-rectangle-field:focus + .otp-rectangle-highlight {
    width: 80%;
}

.otp-hint {
    font-size: 0.7rem;
    color: #94a3b8;
    text-align: center;
    margin-top: 0.75rem;
}

/* Timer Section */
.otp-timer-section {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.timer-circle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.35rem 1rem;  
    background: #f1f5f9;
    border-radius: 50px;
    font-size: 0.9rem;     
    font-weight: 600;
    color: #1e4449;
}

.timer-circle i {
    font-size: 1rem;
}

.timer-circle.timer-expired {
    background: #fee2e2;
    color: #dc2626;
}

/* Error Message */
.error-message-otp {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
    margin-top: 1rem;
}

/* Modal Footer */
.modal-footer-otp {
    padding: 0.85rem 1.5rem 1.25rem;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
}

.btn-resend-otp {
    width: 100%;
    background: transparent;
    border: none;
    color: #1e4449;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.5rem;
    cursor: pointer;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}

.btn-resend-otp:hover:not(:disabled) {
    color: #4BB66D;
}

.btn-resend-otp:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.footer-buttons {
    display: flex;
    gap: 1rem;
}

.btn-cancel-otp {
    flex: 1;
    padding: 0.6rem;    
    background: #f1f5f9;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.85rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel-otp:hover:not(:disabled) {
    background: #e2e8f0;
}

.btn-verify-otp {
    flex: 1.5;
    padding: 0.6rem;    
    background: linear-gradient(135deg, #1e4449 0%, #2a6b6b 100%);
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.85rem;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-verify-otp:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(30, 68, 73, 0.3);
}

.btn-verify-otp:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Booking Details Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 800px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalSlideIn 0.3s ease;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.booking-details-modal {
  border: none;
}

.modal-header-custom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #1e4449 0%, #2a5a60 100%);
  border-bottom: none;
  flex-shrink: 0;
}

.modal-title-custom {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: white;
  display: flex;
  align-items: center;
}

.modal-title-custom i {
  font-size: 1.35rem;
}

.btn-close-custom {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-close-custom:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-body-custom {
  padding: 1.5rem;
  background: #f8fafc;
  overflow-y: auto;
  flex: 1;
  max-height: calc(85vh - 120px);
}

.modal-body-custom::-webkit-scrollbar {
  width: 6px;
}

.modal-body-custom::-webkit-scrollbar-track {
  background: #e2e8f0;
  border-radius: 10px;
}

.modal-body-custom::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 10px;
}

.modal-body-custom::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

.modal-footer-custom {
  padding: 1rem 1.5rem;
  background: white;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  flex-shrink: 0;
}

.btn-close-modal {
  background: #e2e8f0;
  border: none;
  padding: 0.5rem 1.25rem;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
  transition: all 0.2s ease;
}

.btn-close-modal:hover {
  background: #cbd5e1;
  color: #1e293b;
}

.status-badge-wrapper {
  text-align: center;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 20px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.875rem;
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.status-badge.status-pending {
  background: #fef3c7;
  color: #d97706;
  border: 1px solid #fde68a;
}

.status-badge.status-confirmed {
  background: #d1fae5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.status-badge.status-cancelled {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.reference-value {
  font-family: 'Courier New', monospace;
  font-size: 1rem;
  letter-spacing: 1px;
  background: #f1f5f9;
  display: inline-block;
  padding: 6px 12px;
  border-radius: 8px;
}

.info-section {
  margin-bottom: 0.5rem;
}

.section-title {
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-title i {
  font-size: 1rem;
  color: #1e4449;
}

.info-card {
  background: white;
  border-radius: 16px;
  padding: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.info-value {
  font-size: 0.85rem;
  font-weight: 500;
  color: #1e293b;
}

.amount-value {
  font-size: 1rem;
  font-weight: 700;
  color: #059669;
}

.resource-name {
  font-weight: 600;
  color: #1e4449;
}

.equipment-list {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.equipment-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.equipment-item:last-child {
  border-bottom: none;
}

.equipment-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.equipment-name {
  font-weight: 500;
  color: #1e293b;
}

.equipment-qty {
  font-size: 0.75rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 20px;
}

.equipment-price {
  font-weight: 600;
  color: #059669;
}

.notes-box {
  background: #fffbeb;
  border-left: 4px solid #f59e0b;
  padding: 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
  color: #78350f;
  line-height: 1.5;
}

.timeline {
  position: relative;
  padding-left: 1.5rem;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e2e8f0;
}

.timeline-item {
  position: relative;
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.85rem;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
}

.timeline-icon.bg-success { background: #10b981; }
.timeline-icon.bg-primary { background: #3b82f6; }
.timeline-icon.bg-danger { background: #ef4444; }

.timeline-content {
  flex: 1;
  padding-bottom: 0.25rem;
}

.timeline-title {
  font-weight: 600;
  font-size: 0.85rem;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.timeline-date {
  font-size: 0.7rem;
  color: #94a3b8;
}

.actions-cell {
  white-space: nowrap;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  border-radius: 6px;
  margin: 0 2px;
  transition: all 0.2s ease;
}

.btn-group-sm .btn:hover {
  transform: translateY(-1px);
}

.btn-outline-info {
  border-color: #0dcaf0;
  color: #0dcaf0;
}

.btn-outline-info:hover {
  background: #0dcaf0;
  color: white;
}

.btn-outline-warning {
  border-color: #ffc107;
  color: #ffc107;
}

.btn-outline-warning:hover {
  background: #ffc107;
  color: white;
}

.modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 450px;
}

.badge.status-pending {
    background-color: #ffffff !important;
    color: #8B8000 !important;
    border: 1px solid #FFD700;
}

.badge.status-confirmed {
    background-color: #28a745 !important;
    color: white !important;
}

.badge.status-cancelled {
    background-color: #dc3545 !important;
    color: white !important;
}

.extra-small { font-size: 0.75rem; }
.x-small { font-size: 0.75rem; }

.table {
  font-size: 0.85rem;
}

.table th, .table td {
  padding: 0.6rem 0.5rem;
  vertical-align: middle;
}

.availability-list {
  max-height: 350px;
  overflow-y: auto;
  padding-right: 5px;
}

.day-availability {
  padding: 12px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f3f5;
  border-left: 4px solid #1e4449;
  margin-bottom: 12px;
}

.time-slots-container {
  background-color: #f8f9fa;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.time-slot {
  padding: 4px 8px;
  background-color: white;
  border-radius: 4px;
  border-left: 3px solid #4BB66D;
  margin-bottom: 5px;
}

.time-slot:last-child {
  margin-bottom: 0;
}

.slot-time {
  font-family: 'Courier New', Courier, monospace;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.85rem;
}

.availability-list::-webkit-scrollbar {
  width: 4px;
}

.availability-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.availability-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Equipment Section Container - Make it position relative but allow overflow */
.booking-equipment-section {
    position: relative;
    margin-top: 1.5rem;
}

/* Equipment Box - Allow overflow for dropdown */
.equipment-box {
    position: relative;
    overflow: visible !important;
    z-index: 100;
}

/* Equipment Dropdown - Make it appear outside/frame */
.equipment-dropdown {
    position: absolute;
    z-index: 1050;
    background: white;
    width: calc(100% - 0px);
    max-height: 250px;
    overflow-y: auto;
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    margin-top: 0;
    left: 0;
    right: 0;
}

/* Ensure dropdown items are clickable */
.equipment-dropdown-item {
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.equipment-dropdown-item:last-child {
    border-bottom: none;
}

.equipment-dropdown-item:hover {
    background: linear-gradient(135deg, #f0fdf4 0%, #e5f4de 100%);
    transform: translateX(4px);
}

/* Make sure search input container has proper z-index */
.booking-equipment-section .input-group {
    position: relative;
    z-index: 1;
}

/* Ensure parent card doesn't clip the dropdown */
.card-body {
    overflow: visible !important;
}

.card {
    overflow: visible !important;
}

/* Fix for sticky positioning */
.sticky-top {
    overflow: visible !important;
}

/* Equipment dropdown item styling enhancement */
.equipment-dropdown-item .fw-bold {
    color: #1e4449;
}

.equipment-dropdown-item .small.text-muted {
    font-size: 0.7rem;
}

/* Search input focus effect */
.booking-equipment-section .input-group input:focus {
    border-color: #1e4449;
    box-shadow: 0 0 0 3px rgba(30, 68, 73, 0.1);
}

/* Selected equipment list styling */
.selected-equipment-list {
    margin-top: 1rem;
}

.selected-equipment-list .list-group-item {
    border-radius: 12px !important;
    transition: all 0.2s ease;
}

.selected-equipment-list .list-group-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #4BB66D;
}

/* Equipment quantity input styling */
.selected-equipment-list .input-group-sm .btn {
    padding: 0.25rem 0.6rem;
    font-size: 0.75rem;
}

.selected-equipment-list input[type="number"] {
    -moz-appearance: textfield;
}

.selected-equipment-list input[type="number"]::-webkit-inner-spin-button,
.selected-equipment-list input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Equipment total section */
.selected-equipment-list .bg-light.rounded {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px !important;
}

</style>