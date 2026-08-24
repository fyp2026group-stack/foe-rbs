// src/store/reportStore.ts
import { reactive } from 'vue';
import axios from 'axios';

export const reportStore = reactive({
  resources: [] as any[],
  users: [] as any[],
  bookings: [] as any[],
  categories: [] as any[],
  isLoaded: false,
  isLoading: false,

  async fetchAllReports(force = false) {
    // 1. Prevent redundant API calls if already loaded
    if (this.isLoaded && !force) return;

    this.isLoading = true;
    const token = localStorage.getItem('authToken');
    const headers = { Authorization: `Bearer ${token}`, Accept: 'application/json' };
    const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api');

    try {
      // 2. Fetch all data in parallel for maximum performance
      const [res, usr, bkg, cat] = await Promise.all([
        axios.get(`${API_BASE_URL}/resources`, { headers }),
        axios.get(`${API_BASE_URL}/users`, { headers }),
        axios.get(`${API_BASE_URL}/bookings`, { headers }),
        axios.get(`${API_BASE_URL}/categories`, { headers })
      ]);

      this.resources = res.data.resources || res.data || [];
      this.users = (usr.data || []).map((u: any) => ({
        ...u,
        primaryRole: u.roles?.[0]?.name || 'User'
      }));
      this.bookings = bkg.data.bookings || bkg.data || [];
      this.categories = cat.data.categories || cat.data || [];
      
      this.isLoaded = true; // Mark as loaded so next visit is instant
    } catch (e) {
      console.error("Report Store failed to sync:", e);
    } finally {
      this.isLoading = false;
    }
  }
});