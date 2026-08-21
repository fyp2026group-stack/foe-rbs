<template>
  <navbar/>
  <user-sidebar/>
  
  <div class="section">
    <!-- Debug Button (Temporary) -->
    <button 
      v-if="!isLoading && !resource" 
      class="btn btn-warning mb-3"
      @click="debugResourceLoading"
    >
      <i class="bi bi-bug me-1"></i> Debug Resource Loading
    </button>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading resource details...</p>
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
      <div class="row">
        <!-- Left Column - Resource Details -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header bg-dark-teal text-white">
              <h4 class="mb-0">{{ resource.name }}</h4>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <strong><i class="bi bi-geo-alt me-2"></i>Location:</strong>
                    <p class="mb-0">{{ resource.location_name || 'N/A' }}</p>
                  </div>
                  
                  <div class="mb-3">
                    <strong><i class="bi bi-tag me-2"></i>Category:</strong>
                    <p class="mb-0">{{ resource.category?.name || 'Unknown' }}</p>
                  </div>
                  
                  <div class="mb-3">
                    <strong><i class="bi bi-currency-rupee me-2"></i>Base Price:</strong>
                    <p class="mb-0">Rs. {{ resource.base_price }}/hour</p>
                  </div>

                  <div class="mb-3">
                    <strong><i class="bi bi-tag me-2"></i>Department:</strong>
                    <p class="mb-0">{{ resource.department  || 'N/A'}} </p>
                  </div>
                  
                  <div class="mb-3">
                    <strong><i class="bi bi-info-circle me-2"></i>Status:</strong>
                    <span class="badge" :class="getStatusClass(resource.status)">
                      {{ resource.status }}
                    </span>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="resource-image-large mb-3">
                    <img :src="getImageUrl(resource)" :alt="resource.name" class="img-fluid rounded">
                  </div>
                  
                  <div v-if="resource.description" class="mb-3">
                    <strong><i class="bi bi-card-text me-2"></i>Description:</strong>
                    <p class="mb-0">{{ resource.description }}</p>
                  </div>
                  
                  <div v-if="resource.capacity" class="mb-3">
                    <strong><i class="bi bi-people me-2"></i>Capacity:</strong>
                    <p class="mb-0">{{ resource.capacity }} people</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Booking History Section -->
          <div class="card mt-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Booking History</h5>
              <div>
                <button 
                  class="btn btn-success btn-sm me-2"
                  @click="loadBookings"
                  :disabled="isLoadingBookings"
                >
                  <i class="bi bi-arrow-clockwise" :class="{ 'fa-spin': isLoadingBookings }"></i>
                  Refresh
                </button>
              </div>
            </div>
            <div class="card-body">
              <!-- Loading State for Bookings -->
              <div v-if="isLoadingBookings" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted small">Loading booking history...</p>
              </div>

              <!-- No Bookings Found -->
              <div v-else-if="bookings.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                <p class="mt-2 mb-0">No bookings found for this resource</p>
              </div>

              <!-- Bookings Table -->
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
                      <td>
                        {{ formatDate(booking.booking_date) }}
                      </td>
                      <td>
                        {{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <i class="bi bi-person-circle me-2"></i>
                          <div>
                            <div class="text-muted extra-small">{{ booking.user?.email || booking.user_email || 'N/A' }}</div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="fw-bold" :class="getAmountColorClassForBooking(booking)">
                          {{ formatAmountWithUserType(booking) }}
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
                      <td>
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

                <!-- Pagination -->
                <div v-if="bookings.length > 0" class="d-flex justify-content-between align-items-center mt-3">
                  <div class="text-muted small">
                    Showing {{ bookings.length }} bookings
                  </div>
                  <nav aria-label="Booking history pagination">
                    <ul class="pagination pagination-sm mb-0">
                      <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                      </li>
                      <li class="page-item active"><a class="page-link" href="#">1</a></li>
                      <li class="page-item"><a class="page-link" href="#">2</a></li>
                      <li class="page-item"><a class="page-link" href="#">3</a></li>
                      <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                      </li>
                    </ul>
                  </nav>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Booking Form -->
        <div class="col-md-4">
          <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0">Book This Resource</h5>
            </div>
            <div class="card-body">
              <!-- User Type Display -->
              <div class="alert" :class="isInternalUser ? 'alert-info' : 'alert-warning'">
                <i class="bi bi-person-badge me-2"></i>
                <strong>{{ isInternalUser ? 'Internal User' : 'External User (Guest)' }}</strong>
                <span v-if="isInternalUser" class="ms-2">(Free Booking - No Charges Applied)</span>
                <span v-else class="ms-2">(Standard Charges Apply)</span>
              </div>

              <form @submit.prevent="createBookingAndSendOTP">
                <!-- Resource Unavailable Message -->
                <div v-if="isResourceUnavailable" class="alert alert-warning">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  Resource is UNAVAILABLE on this day. (Check weekly schedule)
                </div>

                <!-- Booking Conflict Message -->
                <div v-if="isBookingConflict" class="alert alert-danger">
                  <i class="bi bi-calendar-x me-2"></i>
                  Slot UNAVAILABLE: This time is already booked and confirmed.
                </div>

                <!-- Invalid Range Message -->
                <div v-if="bookingForm.startTime && bookingForm.endTime && bookingForm.startTime >= bookingForm.endTime" class="alert alert-danger">
                  <i class="bi bi-clock me-2"></i>
                  Invalid Time: End time must be after start time.
                </div>

                <!-- Email Input - AUTO FILLED FROM LOCALSTORAGE, READONLY -->
                <div class="mb-3">
                  <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i>E-Mail
                  </label>
                  <input
                    type="email"
                    id="email"
                    class="form-control"
                    placeholder="Enter e-mail (e.g. abc@gmail.com)"
                    v-model="bookingForm.email"
                    readonly
                    disabled
                    style="background-color: #f5f5f5; cursor: not-allowed;"
                  >
                  <small class="text-muted">This email is auto-filled from your account and cannot be changed.</small>
                </div>

                <!-- Phone Input -->
                <div class="mb-3">
                  <label for="phone" class="form-label">
                    <i class="bi bi-telephone me-1"></i>Phone Number
                  </label>
                  <input
                    type="tel"
                    id="phone"
                    class="form-control"
                    placeholder="Enter phone number (e.g. +94 77 123 4567)"
                    v-model="bookingForm.phone"
                    required
                  >
                </div>

                <!-- 1. Reservation Details -->
                <div class="mb-4">
                  <h6 class="border-bottom pb-2">1. Reservation Details</h6>
                  
                  <div class="mb-3">
                    <label for="date" class="form-label">Select Date</label>
                    <input
                      type="date"
                      id="date"
                      class="form-control"
                      v-model="bookingForm.date"
                      :min="minDate"
                      required
                    >
                  </div>
                  
                  <div class="row">
                    <div class="col-6">
                      <label class="form-label">Start Time (24h)</label>
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
                      <label class="form-label">End Time (24h)</label>
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
                      <span v-if="isInternalUser" class="text-success fw-bold">FREE for Internal Users</span>
                      <span v-else-if="calculatedCost">Rs. {{ calculatedCost }}</span>
                      <span v-else class="text-muted">--</span>
                    </p>
                    <small class="text-muted">Base Price: Rs. {{ resource.base_price }}/hour</small>
                    <div v-if="isInternalUser" class="text-success small mt-1">
                      <i class="bi bi-gift-fill me-1"></i> Internal users get free booking!
                    </div>
                  </div>
                </div>

                <!-- 2. Booking Equipment Section -->
                <div class="booking-equipment-section mb-4 pb-3 border-bottom">
                  <h6 class="border-bottom pb-2 mb-3">2. Add Equipment/Accessories (Optional)</h6>
                  
                  <!-- Equipment Search and Add -->
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
                    
                    <!-- Equipment Dropdown -->
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
                  
                  <!-- Selected Equipment List -->
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
                        
                        <!-- Quantity Selector -->
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
                            <div class="fw-bold" :class="isInternalUser ? 'text-muted' : 'text-success'">
                              {{ isInternalUser ? 'FREE' : 'Rs. ' + calculateEquipmentItemCost(item) }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Equipment Total -->
                    <div class="mt-3 p-2 bg-light rounded">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-medium">Equipment Total:</span>
                        <span class="fw-bold" :class="isInternalUser ? 'text-success' : 'text-primary'">
                          {{ isInternalUser ? 'FREE for Internal Users' : 'Rs. ' + equipmentTotalCost }}
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

                <!-- 3. Cost Summary -->
                <div class="cost-summary mb-4">
                  <h6 class="border-bottom pb-2">3. Cost Summary</h6>
                  <div class="cost-breakdown">
                    <div class="d-flex justify-content-between mb-2">
                      <span>Resource Cost:</span>
                      <span :class="isInternalUser ? 'text-success fw-bold' : ''">
                        {{ isInternalUser ? 'FREE' : 'Rs. ' + (calculatedCost || 0) }}
                      </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                      <span>Equipment Cost:</span>
                      <span :class="isInternalUser ? 'text-success fw-bold' : ''">
                        {{ isInternalUser ? 'FREE' : 'Rs. ' + equipmentTotalCost }}
                      </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-top pt-2">
                      <span class="fw-bold">Total Cost:</span>
                      <span class="fw-bold fs-5" :class="isInternalUser ? 'text-success' : 'text-success'">
                        {{ isInternalUser ? 'FREE (Internal User Benefit)' : 'Rs. ' + totalBookingCost }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Weekly Availability (Beautiful UI) -->
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
                        <i class="bi bi-info-circle me-1"></i> No specific time slots (available all day)
                      </div>
                      
                      <div v-else class="text-muted small ms-2">
                        <i class="bi bi-x-circle me-1"></i> Not available on this day
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <button 
                  type="submit" 
                  class="btn btn-success w-100"
                  :disabled="isCreatingBooking || isResourceUnavailable || isBookingConflict || (bookingForm.startTime >= bookingForm.endTime)"
                >
                  <span v-if="isCreatingBooking" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-send-check me-2"></i>
                  {{ isCreatingBooking ? 'Creating Booking...' : (isInternalUser ? 'Book Now (FREE)' : 'Book Now & Pay') }}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- OTP Verification Modal -->
  <div v-if="showOTPModal" class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="bi bi-shield-lock me-2"></i>OTP Verification
        </h5>
        <button type="button" class="btn-close btn-close-white" @click="closeOTPModal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <div class="otp-icon mb-3">
            <i class="bi bi-envelope-check" style="font-size: 3rem; color: #4BB66D;"></i>
          </div>
          <h6 class="fw-bold">Verify Your Email</h6>
          <p class="text-muted small">
            We've sent a 6-digit OTP to:<br>
            <strong>{{ bookingForm.email }}</strong>
          </p>
          <div v-if="otpSentSuccess" class="alert alert-success alert-sm py-2">
            <i class="bi bi-check-circle me-1"></i>OTP sent successfully! Please check your email.
          </div>
        </div>
        
        <!-- OTP Input -->
        <div class="otp-input-container mb-4">
          <div class="d-flex justify-content-center gap-2">
            <input
              v-for="n in 6"
              :key="n"
              type="text"
              maxlength="1"
              class="otp-digit"
              v-model="otpDigits[n-1]"
              @input="onOtpInput(n-1, $event)"
              @keydown="onOtpKeydown(n-1, $event)"
              :ref="el => { if (el) otpInputs[n-1] = el as any }"
              :disabled="isVerifyingOTP"
            />
          </div>
          <div v-if="otpError" class="text-danger text-center mt-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ otpError }}
          </div>
        </div>
        
        <!-- Countdown Timer -->
        <div class="text-center mb-3">
          <small class="text-muted">
            OTP expires in: 
            <span class="fw-bold" :class="otpExpired ? 'text-danger' : 'text-success'">
              {{ formatCountdownTimer() }}
            </span>
          </small>
        </div>
        
        <!-- Resend OTP -->
        <div class="text-center">
          <button 
            class="btn btn-link btn-sm text-decoration-none"
            @click="resendOTP"
            :disabled="!otpExpired || isResendingOTP"
          >
            <span v-if="isResendingOTP" class="spinner-border spinner-border-sm me-1"></span>
            <span v-else><i class="bi bi-arrow-clockwise me-1"></i></span>
            Resend OTP
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button 
          type="button" 
          class="btn btn-secondary" 
          @click="closeOTPModal"
          :disabled="isVerifyingOTP"
        >
          Cancel
        </button>
        <button 
          type="button" 
          class="btn btn-success" 
          @click="verifyOTPAndConfirmBooking"
          :disabled="!isOtpComplete || isVerifyingOTP"
        >
          <span v-if="isVerifyingOTP" class="spinner-border spinner-border-sm me-2"></span>
          <i class="bi bi-check-circle me-2"></i>
          {{ isVerifyingOTP ? 'Verifying...' : 'Verify & Confirm Booking' }}
        </button>
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
          <p class="mb-0">
            <strong>Total Cost:</strong> 
            <span :class="isInternalUser ? 'text-success' : 'text-success'">
              {{ isInternalUser ? 'FREE (Internal User Benefit)' : 'Rs. ' + totalBookingCost }}
            </span>
          </p>
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
        <button type="button" class="btn btn-success" @click="redirectToBookings">
          <i class="bi bi-list-check me-2"></i>View My Bookings
        </button>
        <button type="button" class="btn btn-outline-success" @click="closeSuccessModal">
          <i class="bi bi-calendar-plus me-2"></i>Book Another
        </button>
      </div>
    </div>
  </div>

  <!-- Booking Details Modal -->
  <div v-if="selectedBooking" class="modal-overlay">
    <div class="modal-content" style="max-width: 700px;">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">
          <i class="bi bi-calendar-check me-2"></i>Booking Details
        </h5>
        <button type="button" class="btn-close btn-close-white" @click="selectedBooking = null"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h6 class="fw-bold mb-3">Booking Information</h6>
            <table class="table table-sm table-borderless">
              <tbody>
                <tr>
                  <th width="40%">Reference:</th>
                  <td>{{ selectedBooking.booking_reference || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Status:</th>
                  <td>
                    <span class="badge" :class="getBookingStatusClass(selectedBooking.status)">
                      {{ getBookingStatusText(selectedBooking.status) }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <th>Date:</th>
                  <td>{{ formatDate(selectedBooking.booking_date) }}</td>
                </tr>
                <tr>
                  <th>Time:</th>
                  <td>{{ formatTime(selectedBooking.start_time) }} - {{ formatTime(selectedBooking.end_time) }}</td>
                </tr>
                <tr>
                  <th>Duration:</th>
                  <td>{{ calculateDuration(selectedBooking.start_time, selectedBooking.end_time) }} hours</td>
                </tr>
                <tr>
                  <th>Amount:</th>
                  <td class="fw-bold" :class="getAmountColorClassForBooking(selectedBooking)">
                    {{ formatAmountWithUserType(selectedBooking) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div class="col-md-6">
            <h6 class="fw-bold mb-3">Customer Information</h6>
            <table class="table table-sm table-borderless">
              <tbody>
                <tr>
                  <th width="40%">Name:</th>
                  <td>{{ selectedBooking.user?.name || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Email:</th>
                  <td>{{ selectedBooking.user?.email || selectedBooking.user_email || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Phone:</th>
                  <td>{{ selectedBooking.phone || selectedBooking.user?.phone || 'N/A' }}</td>
                </tr>
              </tbody>
            </table>

            <h6 class="fw-bold mb-3 mt-4">Resource Details</h6>
            <table class="table table-sm table-borderless">
              <tbody>
                <tr>
                  <th width="40%">Resource:</th>
                  <td>{{ resource?.name }}</td>
                </tr>
                <tr>
                  <th>Category:</th>
                  <td>{{ resource?.category?.name || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Rate:</th>
                  <td>Rs. {{ resource?.base_price }}/hour</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Booking Notes -->
        <div v-if="selectedBooking.notes" class="mt-4">
          <h6 class="fw-bold mb-2">Notes</h6>
          <div class="alert alert-light border">
            {{ selectedBooking.notes }}
          </div>
        </div>

        <!-- Booking Timeline -->
        <div class="mt-4">
          <h6 class="fw-bold mb-3">Booking Timeline</h6>
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-marker bg-success"></div>
              <div class="timeline-content">
                <h6 class="mb-1">Booking Created & Confirmed</h6>
                <p class="text-muted small mb-0">{{ formatDateTime(selectedBooking.created_at) }}</p>
              </div>
            </div>
            <div v-if="selectedBooking.confirmed_at" class="timeline-item">
              <div class="timeline-marker bg-primary"></div>
              <div class="timeline-content">
                <h6 class="mb-1">Booking Confirmed via OTP</h6>
                <p class="text-muted small mb-0">{{ formatDateTime(selectedBooking.confirmed_at) }}</p>
              </div>
            </div>
            <div v-if="selectedBooking.cancelled_at" class="timeline-item">
              <div class="timeline-marker bg-danger"></div>
              <div class="timeline-content">
                <h6 class="mb-1">Booking Cancelled</h6>
                <p class="text-muted small mb-0">{{ formatDateTime(selectedBooking.cancelled_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="selectedBooking = null">
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Navbar from '../../components/Navbar.vue';
import { bookingStore } from '../../store/bookingStore';
import UserSidebar from '../../components/Sidebar/UserSidebar.vue';

const route = useRoute();
const router = useRouter();

// API Configuration
const API_BASE_URL = 'http://localhost:8000/api';
const STORAGE_URL_ROOT = 'http://localhost:8000/api/resources/storage';

// Get auth token
const getAuthToken = () => {
  return localStorage.getItem('authToken') || 
         localStorage.getItem('auth_token') || 
         localStorage.getItem('token');
};

// Get logged-in user email from localStorage
const getLoggedInUserEmail = () => {
  return localStorage.getItem('userEmail') || 
         localStorage.getItem('email') || 
         localStorage.getItem('user_email') || 
         '';
};

// Get user role ID from localStorage
const getUserRoleId = () => {
  const roleId = localStorage.getItem('role_id') || 
                 localStorage.getItem('roleId') || 
                 localStorage.getItem('user_role_id') || 
                 '4'; // Default to guest (4)
  return parseInt(roleId as string);
};

// 🔥 CRITICAL FIX: Check if user is internal based on email domain or role
// Internal users: role_id = 1 (Master Admin), 2 (Admin), 3 (User)
// External users: role_id = 4 (Guest)
const isInternalUser = computed(() => {
  const roleId = getUserRoleId();
  // role_id 1, 2, 3 are internal users
  const isInternalByRole = roleId === 1 || roleId === 2 || roleId === 3;
  
  // Also check by email domain as fallback
  const email = getLoggedInUserEmail().toLowerCase();
  const isInternalByEmail = email.includes('@university.edu') || 
                            email.includes('@staff.edu') || 
                            email.includes('@student.edu');
  
  console.log('User Role ID:', roleId);
  console.log('User Email:', email);
  console.log('Is Internal User (by role):', isInternalByRole);
  console.log('Is Internal User (by email):', isInternalByEmail);
  
  // If role_id is not set properly, use email domain as fallback
  // But primary method is role_id
  if (roleId === 4) {
    return isInternalByEmail; // If guest but has internal email, treat as internal
  }
  
  return isInternalByRole;
});

// Get user type string for display
const getUserType = computed(() => {
  const roleId = getUserRoleId();
  if (roleId === 1) return 'Master Admin';
  if (roleId === 2) return 'Admin';
  if (roleId === 3) return 'Internal User';
  return 'External User (Guest)';
});

// Calculate amount based on user type - INTERNAL USERS GET 0, GUESTS PAY
const calculateAmountWithUserType = (baseAmount: number): number => {
  if (isInternalUser.value) {
    return 0; // Internal users (role_id 1,2,3) get free booking
  }
  return baseAmount; // External users (role_id 4) pay full amount
};

// Format amount with user type
const formatAmountWithUserType = (booking: any): string => {
  const roleId = booking.user?.role_id || getUserRoleId();
  
  // Internal users (role_id 1,2,3) get free booking
  if (roleId === 1 || roleId === 2 || roleId === 3) {
    return 'Rs. 0.00 (Internal User - Free)';
  }
  
  const amount = calculateBookingAmountForBooking(booking);
  return `Rs. ${amount}`;
};

// Calculate booking amount for a booking object
const calculateBookingAmountForBooking = (booking: any): number => {
  if (booking.total_amount !== undefined && booking.total_amount !== null) {
    return booking.total_amount;
  }
  
  if (booking.details && booking.details.length > 0) {
    return booking.details.reduce((sum: number, detail: any) => sum + (detail.subtotal || 0), 0);
  }
  
  if (!resource.value) return 0;
  
  const start = new Date(`2000-01-01T${booking.start_time}`);
  const end = new Date(`2000-01-01T${booking.end_time}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  
  return Math.round(hours * resource.value.base_price);
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

const calculateDuration = (startTime: string, endTime: string): string => {
  const start = new Date(`2000-01-01T${startTime}`);
  const end = new Date(`2000-01-01T${endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  return hours.toFixed(1);
};

// Interfaces
interface Resource {
  id: number;
  name: string;
  location_name?: string;
  category_id: number;
  category: ResourceCategory;
  base_price: number;
  department:string;
  description?: string;
  status: 'Active' | 'Inactive' | 'Maintenance';
  capacity?: number;
  availability: ResourceAvailability[]; 
  images?: Array<{
    file_path: string;
    file_name: string;
  }>;
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
    slots: TimeSlot[];
}

interface ResourceCategory {
    id: number;
    name: string;
}

interface BookingEquipment {
    id: number;
    name: string;
    description: string;
    price_per_hour: number;
    available_quantity: number;
    status: 'Available' | 'Unavailable' | 'Maintenance';
}

interface SelectedEquipmentItem extends BookingEquipment {
    quantity: number;
}

interface BookingForm {
  email: string;
  phone: string;
  date: string;
  startTime: string;
  endTime: string;
  purpose?: string;
}

interface Booking {
  id: number;
  booking_reference: string;
  user_id: number;
  user_email: string;
  booking_date: string;
  start_time: string;
  end_time: string;
  total_amount: number;
  status: string;
  notes: string;
  created_at: string;
  updated_at: string;
  confirmed_at: string | null;
  cancelled_at: string | null;
  details: BookingDetail[];
  user?: {
    id: number;
    name: string;
    email: string;
    phone?: string;
    role_id?: number;
  };
}

interface BookingDetail {
  id: number;
  item_type: string;
  item_id: number;
  quantity: number;
  unit_price: number;
  subtotal: number;
}

// State
const resource = ref<Resource | null>(null);
const bookings = computed(() => {
  if (!resource.value) return [];
  const currentResourceId = resource.value.id;
  return bookingStore.bookings.filter((b: any) => {
    return b.details && b.details.some((detail: any) => 
      detail.item_type === 'resource' && Number(detail.item_id) === Number(currentResourceId)
    );
  });
});
const selectedBooking = ref<Booking | null>(null);
const isLoading = ref(true);
const isLoadingBookings = ref(false);
const errorMessage = ref('');

// Equipment State
const availableEquipment = ref<BookingEquipment[]>([]);
const filteredEquipment = ref<BookingEquipment[]>([]);
const selectedEquipment = ref<SelectedEquipmentItem[]>([]);
const equipmentSearch = ref('');
const isLoadingEquipment = ref(false);
const showEquipmentDropdown = ref(false);

// OTP State
const showOTPModal = ref(false);
const showSuccessModal = ref(false);
const otpDigits = ref<string[]>(Array(6).fill(''));
const otpInputs = ref<(HTMLInputElement | null)[]>(Array(6).fill(null));
const otpError = ref('');
const isVerifyingOTP = ref(false);
const isCreatingBooking = ref(false);
const isResendingOTP = ref(false);
const otpTimer = ref(300);
const otpTimerInterval = ref<number | null>(null);
const pendingBookingId = ref<number | null>(null);
const confirmedBookingReference = ref<string>('');
const otpSentSuccess = ref(false);

// Booking Form
const bookingForm = ref<BookingForm>({
  email: '',
  phone: '',
  date: '',
  startTime: '08:00',
  endTime: '10:00',
  purpose: ''
});

// Computed Properties
const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const calculatedCost = computed(() => {
  if (!resource.value || !bookingForm.value.startTime || !bookingForm.value.endTime) {
    return 0;
  }
  
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  
  const baseAmount = Math.round(hours * resource.value.base_price);
  return calculateAmountWithUserType(baseAmount);
});

const equipmentTotalCost = computed(() => {
  const total = selectedEquipment.value.reduce((total, item) => {
    const hours = calculateBookingDuration();
    return total + (item.price_per_hour * item.quantity * hours);
  }, 0);
  return calculateAmountWithUserType(total);
});

const totalBookingCost = computed(() => {
  return calculatedCost.value + equipmentTotalCost.value;
});

const isResourceUnavailable = computed(() => {
  if (!resource.value || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) return false;
  
  const selectedDate = new Date(bookingForm.value.date);
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(
    day => day.day_name.toLowerCase() === selectedDayName.toLowerCase()
  );
  
  if (!dayAvailability || !dayAvailability.is_available) return true;
  
  if (dayAvailability.slots && dayAvailability.slots.length > 0) {
    const selectedStart = bookingForm.value.startTime.substring(0, 5);
    const selectedEnd = bookingForm.value.endTime.substring(0, 5);
    
    return !dayAvailability.slots.some(slot => {
      const slotStart = slot.start_time.substring(0, 5);
      const slotEnd = slot.end_time.substring(0, 5);
      return selectedStart >= slotStart && selectedEnd <= slotEnd;
    });
  }
  
  return false;
});

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

const isOtpComplete = computed(() => {
  return otpDigits.value.every(digit => digit.length === 1);
});

const otpExpired = computed(() => {
  return otpTimer.value <= 0;
});

const sortedAvailability = computed(() => {
  if (!resource.value || !resource.value.availability) return [];
  
  const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  
  return [...resource.value.availability].sort((a, b) => {
    return dayOrder.indexOf(a.day_name) - dayOrder.indexOf(b.day_name);
  });
});

const hourOptions = computed(() => Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0')));
const minuteOptions = computed(() => Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0')));

const startHour = ref('08');
const startMin = ref('00');
const endHour = ref('10');
const endMin = ref('00');

// Color class for amount based on user type
const getAmountColorClassForBooking = (booking: any) => {
  const roleId = booking.user?.role_id || getUserRoleId();
  if (roleId === 1 || roleId === 2 || roleId === 3) {
    return 'text-success';
  }
  return 'text-success';
};

watch([startHour, startMin], () => {
  bookingForm.value.startTime = `${startHour.value}:${startMin.value}`;
});

watch([endHour, endMin], () => {
  bookingForm.value.endTime = `${endHour.value}:${endMin.value}`;
});

watch([() => bookingForm.value.date, () => bookingForm.value.startTime, () => bookingForm.value.endTime], () => {
  if (bookingForm.value.date && bookingForm.value.startTime && bookingForm.value.endTime && bookingForm.value.startTime < bookingForm.value.endTime) {
    loadAvailableEquipment();
  }
});

watch(() => bookingForm.value.startTime, (newVal: string) => {
  if (newVal && newVal.includes(':')) {
    const [h, m] = newVal.split(':');
    startHour.value = h.substring(0, 2).padStart(2, '0');
    startMin.value = m.substring(0, 2).padStart(2, '0');
  }
}, { immediate: true });

watch(() => bookingForm.value.endTime, (newVal: string) => {
  if (newVal && newVal.includes(':')) {
    const [h, m] = newVal.split(':');
    endHour.value = h.substring(0, 2).padStart(2, '0');
    endMin.value = m.substring(0, 2).padStart(2, '0');
  }
}, { immediate: true });

const processAvailabilityData = (availabilityData: any[]) => {
  if (!availabilityData || !Array.isArray(availabilityData)) return [];
  
  return availabilityData.map(day => {
    if (day.slots && Array.isArray(day.slots)) {
      return {
        ...day,
        slots: day.slots.map((slot: any) => ({
          start_time: slot.start_time || '',
          end_time: slot.end_time || ''
        }))
      };
    }
    
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

const calculateBookingDuration = (): number => {
  if (!bookingForm.value.startTime || !bookingForm.value.endTime) return 0;
  
  const start = new Date(`2000-01-01T${bookingForm.value.startTime}`);
  const end = new Date(`2000-01-01T${bookingForm.value.endTime}`);
  const hours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
  
  return hours > 0 ? hours : 0;
};

const calculateEquipmentItemCost = (item: SelectedEquipmentItem): number => {
  const hours = calculateBookingDuration();
  const baseAmount = Math.round(item.price_per_hour * item.quantity * hours);
  return calculateAmountWithUserType(baseAmount);
};

const loadAvailableEquipment = async () => {
  if (!bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) {
    try {
      const token = getAuthToken();
      const response = await axios.get(`${API_BASE_URL}/booking-items`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
      });
      let equipmentData = response.data.items || response.data.data || response.data;
      availableEquipment.value = equipmentData.filter((item: any) => item.status === 'Available');
    } catch (error) {
      console.error('Error loading static equipment:', error);
    }
    return;
  }

  isLoadingEquipment.value = true;
  try {
    const token = getAuthToken();
    const params = {
      date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime
    };
    
    const response = await axios.get(`${API_BASE_URL}/booking-items/availability`, {
      params,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    const equipmentData = response.data;
    
    if (Array.isArray(equipmentData)) {
      availableEquipment.value = equipmentData.filter((item: any) => 
        item.status === 'Available' && item.available_quantity > 0
      );
    } else {
      console.warn('API returned non-array for availability:', equipmentData);
      availableEquipment.value = [];
    }

    selectedEquipment.value.forEach(selectedItem => {
      const liveData = equipmentData.find((item: any) => item.id === selectedItem.id);
      if (liveData) {
        selectedItem.available_quantity = liveData.available_quantity;
        if (selectedItem.quantity > liveData.available_quantity) {
          selectedItem.quantity = liveData.available_quantity;
        }
      }
    });
    
  } catch (error: any) {
    console.error('Error loading dynamic equipment:', error);
  } finally {
    isLoadingEquipment.value = false;
  }
};

const searchEquipment = () => {
  const searchTerm = equipmentSearch.value.toLowerCase().trim();
  
  filteredEquipment.value = availableEquipment.value.filter(item => {
    const nameMatch = item.name.toLowerCase().includes(searchTerm);
    const descMatch = item.description?.toLowerCase().includes(searchTerm) || false;
    const matchesSearch = !searchTerm || nameMatch || descMatch;
    
    return matchesSearch && 
           item.status === 'Available' && 
           item.available_quantity > 0;
  });
  
  showEquipmentDropdown.value = true;
};

const clearEquipmentSearch = () => {
  equipmentSearch.value = '';
  filteredEquipment.value = [];
  showEquipmentDropdown.value = false;
};

const addEquipmentItem = (item: BookingEquipment) => {
  const existingIndex = selectedEquipment.value.findIndex(selected => selected.id === item.id);
  
  if (existingIndex !== -1) {
    const existingItem = selectedEquipment.value[existingIndex];
    if (existingItem.quantity < item.available_quantity) {
      selectedEquipment.value[existingIndex].quantity++;
    } else {
      alert(`Cannot add more. Maximum available quantity is ${item.available_quantity}`);
    }
  } else {
    const selectedItem: SelectedEquipmentItem = {
      ...item,
      quantity: 1
    };
    selectedEquipment.value.push(selectedItem);
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
  const item = selectedEquipment.value[index];
  if (item.quantity > 1) {
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

const getImageUrl = (resource: Resource): string => {
   if (resource && resource.images && resource.images.length > 0) {
       const filePath = resource.images[0].file_path;
       return filePath.startsWith('http') ? filePath : `${STORAGE_URL_ROOT}/${filePath}`;
   }
   return 'https://via.placeholder.com/600x400?text=No+Image';
};

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

const getBookingStatusClass = (status: string): string => {
  switch (status) {
    case 'pending':
      return 'status-pending';
    case 'confirmed':
      return 'status-confirmed';
    case 'cancelled':
      return 'status-cancelled';
    case 'completed':
      return 'status-completed';
    default:
      return 'bg-secondary';
  }
};

const getBookingStatusText = (status: string): string => {
  switch (status) {
    case 'pending':
      return 'Pending';
    case 'confirmed':
      return 'Confirmed';
    case 'cancelled':
      return 'Cancelled';
    case 'completed':
      return 'Completed';
    default:
      return status.charAt(0).toUpperCase() + status.slice(1);
  }
};

const calculateBookingAmount = (booking: Booking): number => {
  if (booking.total_amount) {
    return booking.total_amount;
  }
  
  if (booking.details && booking.details.length > 0) {
    return booking.details.reduce((sum, detail) => sum + detail.subtotal, 0);
  }
  
  const start = new Date(`2000-01-01T${booking.start_time}`);
  const end = new Date(`2000-01-01T${booking.end_time}`);
  const diff = end.getTime() - start.getTime();
  const hours = diff > 0 ? diff / (1000 * 60 * 60) : 0;
  
  return Math.round(hours * (resource.value?.base_price || 0));
};

const formatCountdownTimer = () => {
  const minutes = Math.floor(otpTimer.value / 60);
  const seconds = otpTimer.value % 60;
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

const loadResourceDetails = async () => {
  const resourceId = route.query.resourceId || route.params.id;
  
  if (!resourceId) {
    errorMessage.value = 'Resource ID is required';
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const token = getAuthToken();
    
    const resourceResponse = await axios.get(`${API_BASE_URL}/resources/${resourceId}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      }
    });
    
    let resourceData = null;
    
    if (resourceResponse.data) {
      if (resourceResponse.data.resource) {
        resourceData = resourceResponse.data.resource;
      } else if (resourceResponse.data.data) {
        resourceData = resourceResponse.data.data;
      } else {
        resourceData = resourceResponse.data;
      }
    }
    
    if (resourceData) {
      if (resourceData.availability) {
        resourceData.availability = processAvailabilityData(resourceData.availability);
      } else {
        resourceData.availability = [];
      }
      resource.value = resourceData;
      
      await loadBookings();
      await loadAvailableEquipment();
    } else {
      errorMessage.value = 'Resource data not found in response';
    }

    bookingForm.value.date = minDate.value;

  } catch (error: any) {
    console.error('Error loading resource:', error);
    
    if (error.response) {
      if (error.response.status === 401) {
        errorMessage.value = 'Authentication required. Please login again.';
        setTimeout(() => router.push('/login'), 2000);
      } else if (error.response.status === 404) {
        errorMessage.value = 'Resource not found.';
        setTimeout(() => router.push('/resources'), 2000);
      } else if (error.response.status === 500) {
        errorMessage.value = 'Server error. Please try again later.';
      } else {
        errorMessage.value = `Failed to load resource: ${error.response.data?.message || 'Unknown error'}`;
      }
    } else if (error.request) {
      errorMessage.value = 'No response from server. Please check your connection.';
    } else {
      errorMessage.value = `Request error: ${error.message}`;
    }
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
  
  try {
    const token = getAuthToken();
    
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const userId = currentUser.id || 0;
    const roleId = getUserRoleId();
    
    // Calculate final amount based on user type
    let finalAmount = totalBookingCost.value;
    if (isInternalUser.value) {
      finalAmount = 0;
    }
    
    const bookingPayload: any = {
      user_id: userId,
      user_email: bookingForm.value.email,
      phone: bookingForm.value.phone,
      user_role_id: roleId,
      booking_date: bookingForm.value.date,
      start_time: bookingForm.value.startTime,
      end_time: bookingForm.value.endTime,
      notes: bookingForm.value.purpose || '',
      total_amount: finalAmount,
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
          price_per_hour: isInternalUser.value ? 0 : item.price_per_hour
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
    
    console.log('Booking created, pending ID:', pendingBookingId.value);
    console.log('User Type:', isInternalUser.value ? 'Internal' : 'External');
    console.log('Total Amount:', finalAmount);
    
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
  if (!resource.value) {
    errorMessage.value = 'Resource not loaded. Please try again.';
    return;
  }
  
  if (!bookingForm.value.email || !bookingForm.value.phone || !bookingForm.value.date || !bookingForm.value.startTime || !bookingForm.value.endTime) {
    errorMessage.value = 'Please fill all required fields';
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
  
  if (isBookingConflict.value) {
    alert("This time slot is already booked and confirmed for this resource. Please choose another time.");
    errorMessage.value = 'Time slot is already booked and confirmed.';
    return;
  }
  
  const selectedDate = new Date(bookingForm.value.date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  if (selectedDate < today) {
    errorMessage.value = 'Cannot book for past dates';
    return;
  }
  
  const selectedDayName = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
  const dayAvailability = resource.value.availability?.find(
    day => day.day_name.toLowerCase() === selectedDayName.toLowerCase()
  );
  
  if (!dayAvailability || !dayAvailability.is_available) {
    errorMessage.value = `Resource is not available on ${selectedDayName}`;
    return;
  }
  
  if (isResourceUnavailable.value) {
    errorMessage.value = `Resource is not available during the selected time on ${selectedDayName}`;
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
      showOTPModal.value = true;
      startOTPTimer();
      otpDigits.value = Array(6).fill('');
      
      nextTick(() => {
        const firstInput = otpInputs.value[0];
        if (firstInput) {
          firstInput.focus();
          firstInput.value = '';
        }
      });
    }
    
  } catch (error: any) {
    console.error('Error in booking flow:', error);
    errorMessage.value = error.message || 'Failed to create booking. Please try again.';
  } finally {
    isCreatingBooking.value = false;
  }
};

const verifyOTPAndConfirmBooking = async () => {
  const enteredOTP = otpDigits.value.join('');
  
  if (enteredOTP.length !== 6) {
    otpError.value = 'Please enter complete 6-digit OTP';
    return;
  }
  
  isVerifyingOTP.value = true;
  otpError.value = '';
  
  try {
    const token = getAuthToken();
    
    console.log(`Verifying OTP for Booking ID: ${pendingBookingId.value}`);
    console.log(`Entered OTP: "${enteredOTP}"`);

    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/verify-otp`, {
      otp_code: enteredOTP.trim(),
      email: bookingForm.value.email
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    console.log('OTP verified and booking confirmed:', response.data);
    
    const confirmedBooking = response.data.booking || response.data;
    confirmedBookingReference.value = confirmedBooking.booking_reference;
    
    bookingStore.updateBookingLocally(confirmedBooking);
    
    closeOTPModal();
    showSuccessModal.value = true;
    
    await loadBookings();
    
  } catch (error: any) {
    console.error('Error verifying OTP:', error);
    
    if (error.response?.status === 422) {
      otpError.value = error.response.data.message || 'Invalid OTP. Please try again.';
    } else if (error.response?.status === 400) {
      otpError.value = error.response.data.message || 'Invalid or expired OTP. Please request a new one.';
    } else if (error.response?.data?.message) {
      otpError.value = error.response.data.message;
    } else {
      otpError.value = 'Failed to verify OTP. Please try again.';
    }
    
    otpDigits.value = Array(6).fill('');
    nextTick(() => {
      const firstInput = otpInputs.value[0];
      if (firstInput) {
        firstInput.focus();
        firstInput.value = '';
      }
    });
  } finally {
    isVerifyingOTP.value = false;
  }
};

const resendOTP = async () => {
  if (!pendingBookingId.value) {
    otpError.value = 'No pending booking found';
    return;
  }
  
  isResendingOTP.value = true;
  otpError.value = '';
  
  try {
    const token = getAuthToken();
    
    const response = await axios.post(`${API_BASE_URL}/bookings/${pendingBookingId.value}/resend-otp`, {
      email: bookingForm.value.email
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    startOTPTimer();
    otpDigits.value = Array(6).fill('');
    otpSentSuccess.value = true;
    otpError.value = 'New OTP sent successfully!';
    
    nextTick(() => {
      const firstInput = otpInputs.value[0];
      if (firstInput) {
        firstInput.focus();
        firstInput.value = '';
      }
    });
    
  } catch (error: any) {
    console.error('Error resending OTP:', error);
    otpError.value = error.response?.data?.message || 'Failed to resend OTP. Please try again.';
  } finally {
    isResendingOTP.value = false;
  }
};

const onOtpInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement;
  const value = input.value;
  
  if (value.length === 6 && /^\d{6}$/.test(value)) {
    const digits = value.split('');
    digits.forEach((digit, i) => {
      if (i < 6) {
        otpDigits.value[i] = digit;
      }
    });
    
    nextTick(() => {
      const lastInput = otpInputs.value[5];
      if (lastInput) lastInput.focus();
    });
    return;
  }
  
  if (value && !/^\d$/.test(value)) {
    otpDigits.value[index] = '';
    return;
  }
  
  otpDigits.value[index] = value;
  
  if (value && index < 5) {
    nextTick(() => {
      const nextInput = otpInputs.value[index + 1];
      if (nextInput) nextInput.focus();
    });
  }
};

const onOtpKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    nextTick(() => {
      const prevInput = otpInputs.value[index - 1];
      if (prevInput) prevInput.focus();
    });
  }
};

const startOTPTimer = () => {
  otpTimer.value = 300;
  if (otpTimerInterval.value) {
    clearInterval(otpTimerInterval.value);
  }
  
  otpTimerInterval.value = window.setInterval(() => {
    if (otpTimer.value > 0) {
      otpTimer.value--;
    } else {
      if (otpTimerInterval.value) {
        clearInterval(otpTimerInterval.value);
      }
    }
  }, 1000);
};

const closeOTPModal = () => {
  showOTPModal.value = false;
  otpDigits.value = Array(6).fill('');
  otpInputs.value.forEach(input => {
    if (input) input.value = '';
  });
  otpError.value = '';
  otpSentSuccess.value = false;
  isVerifyingOTP.value = false;
  isResendingOTP.value = false;
  
  if (otpTimerInterval.value) {
    clearInterval(otpTimerInterval.value);
    otpTimerInterval.value = null;
  }
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
  
  bookingForm.value.email = getLoggedInUserEmail();
  bookingForm.value.phone = JSON.parse(localStorage.getItem('user') || '{}').phone || '';
  bookingForm.value.date = minDate.value;
  bookingForm.value.startTime = '08:00';
  bookingForm.value.endTime = '10:00';
  bookingForm.value.purpose = '';
  selectedEquipment.value = [];
  equipmentSearch.value = '';
  filteredEquipment.value = [];
  showEquipmentDropdown.value = false;
  pendingBookingId.value = null;
  confirmedBookingReference.value = '';
};

const redirectToBookings = () => {
  closeSuccessModal();
  router.push('/user/booking');
};

const viewBookingDetails = (booking: Booking) => {
  selectedBooking.value = booking;
};

const cancelBooking = async (booking: Booking) => {
  if (!confirm('Are you sure you want to cancel this booking?')) return;
  
  try {
    const token = getAuthToken();
    
    const response = await axios.put(`${API_BASE_URL}/bookings/${booking.id}/cancel`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    bookingStore.updateBookingLocally(response.data.booking || response.data);
    
    if (selectedBooking.value && selectedBooking.value.id === booking.id) {
      selectedBooking.value = response.data.booking || response.data;
    }
    
    alert('Booking cancelled successfully!');
    await loadBookings();
    
  } catch (error: any) {
    console.error('Error cancelling booking:', error);
    alert(error.response?.data?.message || 'Failed to cancel booking');
  }
};

const debugResourceLoading = async () => {
  console.log('=== DEBUG RESOURCE LOADING ===');
  console.log('Route:', route);
  console.log('Query:', route.query);
  console.log('Params:', route.params);
  console.log('Resource ID:', route.query.resourceId || route.params.id);
  
  const resourceId = route.query.resourceId || route.params.id;
  if (resourceId) {
    await loadResourceDetails();
  } else {
    console.error('No resource ID found in URL');
  }
};

watch(
  [() => bookingForm.value.date, () => bookingForm.value.startTime, () => bookingForm.value.endTime],
  (newValues) => {
    if (newValues[0] && newValues[1] && newValues[2]) {
      loadAvailableEquipment();
    }
  }
);

watch(
  () => route.query.resourceId,
  (newResourceId) => {
    if (newResourceId) {
      loadResourceDetails();
    }
  }
);

onMounted(() => {
  // Auto-fill email from localStorage
  const userEmail = getLoggedInUserEmail();
  bookingForm.value.email = userEmail;
  
  // Auto-fill phone from localStorage user profile if available
  const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
  if (currentUser.phone) {
    bookingForm.value.phone = currentUser.phone;
  }
  
  console.log('========== USER INFO ==========');
  console.log('Auto-filled email:', userEmail);
  console.log('User Role ID:', getUserRoleId());
  console.log('Is Internal User:', isInternalUser.value);
  console.log('User Type:', getUserType.value);
  console.log('================================');
  
  loadResourceDetails();
});
</script>

<style scoped>
/* Existing styles remain - keeping same as original */
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

.bg-dark-teal {
  background-color: #1e4449;
  color: white;
}

.resource-image-large {
  height: 200px;
  overflow: hidden;
  border-radius: 8px;
}

.resource-image-large img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card {
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  margin-bottom: 20px;
}

.card-header {
  border-radius: 8px 8px 0 0 !important;
}

.sticky-top {
  position: sticky;
  z-index: 100;
}

.booking-equipment-section {
  margin-top: 1.5rem;
}

.equipment-dropdown {
  max-height: 250px;
  overflow-y: auto;
  background-color: white;
  z-index: 1000;
  position: absolute;
  width: 100%;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  margin-top: 0.25rem;
}

.equipment-dropdown-item {
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 0.75rem 1rem;
}

.equipment-dropdown-item:hover {
  background-color: #f8f9fa;
}

.selected-equipment-list .list-group-item {
  transition: all 0.3s;
  border: 1px solid #dee2e6;
}

.selected-equipment-list .list-group-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-color: #4BB66D;
}

.input-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.cost-summary {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e9ecef;
}

.cost-breakdown {
  font-size: 0.95rem;
}

.badge {
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
  border-radius: 4px;
}

.table {
  font-size: 0.85rem;
}

.table th, .table td {
  padding: 0.6rem 0.5rem;
  vertical-align: middle;
}

.btn-success {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

.badge.status-pending {
  background-color: #ffffff !important;
  color: #8B8000 !important;
  border: 1px solid #FFD700;
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.badge.status-confirmed {
  background-color: #28a745 !important;
  color: white !important;
  font-weight: 500;
  border: none;
}

.badge.status-cancelled {
  background-color: #dc3545 !important;
  color: white !important;
  font-weight: 500;
  border: none;
}

.badge.status-completed {
  background-color: #17a2b8 !important;
  color: white !important;
  font-weight: 500;
  border: none;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 450px;
  animation: modalSlideIn 0.3s ease;
}

.otp-digit {
  width: 45px;
  height: 55px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 600;
  border: 2px solid #dee2e6;
  border-radius: 8px;
  transition: all 0.2s;
}

.otp-digit:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 3px rgba(75, 182, 109, 0.1);
  outline: none;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.extra-small {
  font-size: 0.75rem;
}

.small {
  font-size: 0.875rem;
}

.text-success {
  color: #4BB66D !important;
}

.text-primary {
  color: #1e4449 !important;
}

.text-muted {
  color: #6c757d !important;
}

.form-control:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.2rem rgba(75, 182, 109, 0.25);
}

.alert-warning {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #856404;
}

.alert-success {
  background-color: #d1e7dd;
  border-color: #badbcc;
  color: #0f5132;
}

.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
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
</style>