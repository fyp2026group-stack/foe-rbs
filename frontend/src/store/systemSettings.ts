import { reactive } from 'vue';
import axios from 'axios';

export const systemStore = reactive({
  // Global derived state for UI components
  name: 'FOE RBS',
  logo: '',
  isLoaded: false,

  // Full settings object for form binding
  settings: {
    site_name: '',
    organization_name: '',
    contact_email: '',
    phone_number: '',
    address: ''
  },

  /**
   * Fetches settings only if not already loaded in memory.
   */
  async loadSettings(force = false) {
    if (this.isLoaded && !force) return;

    try {
      const res = await axios.get(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/settings'));
      this.updateState(res.data);
    } catch (e) {
      console.error("Store fetch failed", e);
    }
  },

  /**
   * Synchronizes incoming backend data with global memory.
   */
  updateState(data: any) {
    // 1. Update the form-compatible object
    this.settings = {
      site_name: data.site_name || '',
      organization_name: data.organization_name || '',
      contact_email: data.contact_email || '',
      phone_number: data.phone_number || '',
      address: data.address || ''
    };

    // 2. Update top-level UI properties
    this.name = data.site_name || 'FOE RBS';
    
    if (data.logo) {
      const filename = data.logo.split('/').pop();
      // Cache-busting timestamp ensures the logo updates instantly in the browser
      const timestamp = new Date().getTime();
      this.logo = ((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + `/settings/logo/${filename}?t=${timestamp}`);
    }
    
    this.isLoaded = true;
  }
});