// src/store/bookingStore.ts
import { reactive } from 'vue';
import axios from 'axios';

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
  resource?: any;
  details: any[];
  [key: string]: any;
}

export const bookingStore = reactive({
  bookings: [] as Booking[],
  isLoading: false,
  isLoaded: false,
  lastFetched: 0,
  
  // Cache persistence (optional, but good for "feel")
  cacheTimeout: 5 * 60 * 1000, // 5 minutes

  getAuthToken() {
    return localStorage.getItem('authToken') || 
           localStorage.getItem('auth_token') || 
           localStorage.getItem('token');
  },

  async fetchAll(force = false) {
    const now = Date.now();
    if (this.isLoaded && !force && (now - this.lastFetched < this.cacheTimeout)) {
      return;
    }

    this.isLoading = true;
    try {
      const token = this.getAuthToken();
      const response = await axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/bookings'), {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      });

      let data = [];
      if (Array.isArray(response.data)) {
        data = response.data;
      } else if (response.data.data && Array.isArray(response.data.data)) {
        data = response.data.data;
      } else if (response.data.bookings && Array.isArray(response.data.bookings)) {
        data = response.data.bookings;
      }

      this.bookings = data;
      this.isLoaded = true;
      this.lastFetched = now;
    } catch (e) {
      console.error("Booking Store: Failed to load all bookings", e);
    } finally {
      this.isLoading = false;
    }
  },

  async fetchMyBookings(force = false) {
    const now = Date.now();
    this.isLoading = true;
    try {
      const token = this.getAuthToken();
      const response = await axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/bookings/my'), {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      });

      let data = [];
      if (Array.isArray(response.data)) {
        data = response.data;
      } else if (response.data.data && Array.isArray(response.data.data)) {
        data = response.data.data;
      } else if (response.data.bookings && Array.isArray(response.data.bookings)) {
        data = response.data.bookings;
      }

      this.bookings = data;
      this.isLoaded = true;
      this.lastFetched = now;
    } catch (e) {
      console.error("Booking Store: Failed to load personal bookings", e);
    } finally {
      this.isLoading = false;
    }
  },

  async fetchByResource(resourceId: number | string) {
    // We could check if we already have bookings for this resource in memory,
    // but a specific fetch is safer for detail views.
    // However, if we just fetched ALL, we might already have them.
    
    this.isLoading = true;
    try {
      const token = this.getAuthToken();
      const response = await axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + `/bookings/resource/${resourceId}`), {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      });

      let data: Booking[] = [];
      if (Array.isArray(response.data)) {
        data = response.data;
      } else if (response.data.data && Array.isArray(response.data.data)) {
        data = response.data.data;
      } else if (response.data.bookings && Array.isArray(response.data.bookings)) {
        data = response.data.bookings;
      }

      // Merge new data into existing bookings list, avoiding duplicates
      data.forEach(newBooking => {
        const index = this.bookings.findIndex(b => b.id === newBooking.id);
        if (index !== -1) {
          this.bookings[index] = newBooking;
        } else {
          this.bookings.push(newBooking);
        }
      });

      return data;
    } catch (e) {
      console.error(`Booking Store: Failed to load bookings for resource ${resourceId}`, e);
      return [];
    } finally {
      this.isLoading = false;
    }
  },

  async fetchAssignedBookings(adminId: number | string, force = false) {
    const now = Date.now();
    this.isLoading = true;
    try {
      const token = this.getAuthToken();
      const response = await axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + `/bookings/admin/assigned?admin_id=${adminId}`), {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      });

      let data = [];
      if (Array.isArray(response.data)) {
        data = response.data;
      } else if (response.data.data && Array.isArray(response.data.data)) {
        data = response.data.data;
      } else if (response.data.bookings && Array.isArray(response.data.bookings)) {
        data = response.data.bookings;
      }

      this.bookings = data;
      this.isLoaded = true;
      this.lastFetched = now;
    } catch (e) {
      console.error(`Booking Store: Failed to load assigned bookings for admin ${adminId}`, e);
      this.bookings = [];
    } finally {
      this.isLoading = false;
    }
  },

  async fetchAdminData(adminId: number | string) {
    this.isLoading = true;
    try {
      const token = this.getAuthToken();
      const [personalRes, assignedRes] = await Promise.all([
        axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/bookings/my'), {
          headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        }),
        axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + `/bookings/admin/assigned?admin_id=${adminId}`), {
          headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        })
      ]);

      const personal = personalRes.data.bookings || personalRes.data || [];
      const assigned = assignedRes.data.bookings || assignedRes.data || [];

      // Merge and remove duplicates by ID
      const allBookings = [...personal];
      assigned.forEach((b: any) => {
        if (!allBookings.find(existing => existing.id === b.id)) {
          allBookings.push(b);
        }
      });

      this.bookings = allBookings;
      this.isLoaded = true;
      this.lastFetched = Date.now();
    } catch (e) {
      console.error("Booking Store: Failed to load admin dashboard data", e);
    } finally {
      this.isLoading = false;
    }
  },

  async fetchGuestBookings(email: string) {
    this.isLoading = true;
    try {
      const response = await axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + `/bookings/guest-lookup?email=${encodeURIComponent(email)}`), {
        headers: {
          'Accept': 'application/json',
        }
      });

      let data: Booking[] = [];
      if (Array.isArray(response.data)) {
        data = response.data;
      } else if (response.data.bookings && Array.isArray(response.data.bookings)) {
        data = response.data.bookings;
      }

      this.bookings = data;
      this.isLoaded = true;
      this.lastFetched = Date.now();
    } catch (e) {
      console.error(`Booking Store: Failed to load guest bookings for ${email}`, e);
      this.bookings = [];
    } finally {
      this.isLoading = false;
    }
  },

  updateBookingLocally(updatedBooking: Booking) {
    const index = this.bookings.findIndex(b => b.id === updatedBooking.id);
    if (index !== -1) {
      this.bookings[index] = { ...this.bookings[index], ...updatedBooking };
    } else {
      this.bookings.push(updatedBooking);
    }
  },

  removeBookingLocally(id: number) {
    this.bookings = this.bookings.filter(b => b.id !== id);
  },

  clearCache() {
    this.isLoaded = false;
    this.lastFetched = 0;
  }
});
