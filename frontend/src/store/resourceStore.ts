// src/store/resourceStore.ts
import { reactive } from 'vue';
import axios from 'axios';

export const resourceStore = reactive({
  resources: [] as any[],
  categories: [] as any[],
  departments: [] as any[],
  isLoading: false,
  isLoaded: false,

  async fetchAll(force = false) {
    if (this.isLoaded && !force) return;
    
    this.isLoading = true;
    try {
      const token = localStorage.getItem('authToken');
      const headers: any = { Accept: 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;

      
      const [resResources, resCategories, resDepartments] = await Promise.all([
        axios.get('http://localhost:8000/api/resources', { headers }),
        axios.get('http://localhost:8000/api/categories', { headers }),
        axios.get('http://localhost:8000/api/departments', { headers })
      ]);

      this.resources = resResources.data.resources || resResources.data || [];
      this.categories = resCategories.data.categories || resCategories.data || [];
      this.departments = resDepartments.data.departments || resDepartments.data || [];
      this.isLoaded = true;
    } catch (e) {
      console.error("Resource Store failed to load", e);
    } finally {
      this.isLoading = false;
    }
  },

  // Resource Mutations
  addResource(resource: any) {
    this.resources.unshift(resource);
  },
  updateResource(resource: any) {
    const index = this.resources.findIndex(r => r.id === resource.id);
    if (index !== -1) this.resources[index] = { ...this.resources[index], ...resource };
  },
  removeResource(id: number | string) {
    this.resources = this.resources.filter(r => r.id !== id);
  },
  updateStatus(id: number | string, status: string) {
    const resource = this.resources.find(r => r.id === id);
    if (resource) resource.status = status;
  },

  // Category Mutations
  addCategory(category: any) {
    this.categories.unshift(category);
  },
  updateCategory(category: any) {
    const index = this.categories.findIndex(c => c.id === category.id);
    if (index !== -1) this.categories[index] = { ...this.categories[index], ...category };
  },
  removeCategory(id: number | string) {
    this.categories = this.categories.filter(c => c.id !== id);
  },

  // Department Mutations
  addDepartment(dept: any) {
    this.departments.unshift(dept);
  },
  updateDepartment(dept: any) {
    const index = this.departments.findIndex(d => d.id === dept.id);
    if (index !== -1) this.departments[index] = { ...this.departments[index], ...dept };
  },
  removeDepartment(id: number | string) {
    this.departments = this.departments.filter(d => d.id !== id);
  }
});