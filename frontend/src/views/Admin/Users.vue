<template>
  <navbar/>
  <admin-sidebar/>
  <div class="section">
    <!-- Modern Header Card -->
    <div class="dashboard-header-modern mb-4 p-4 rounded shadow-sm bg-white">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item active" aria-current="page">Users</li>
          </ol>
        </nav>
        <h2 class="mb-0 fw-bold text-dark-teal">User Directory</h2>
        <p class="text-muted mb-0">View all registered users within the system.</p>
      </div>
      <div class="text-end d-none d-md-block">
        <span class="badge bg-light-teal text-teal p-2 px-3 rounded-pill border border-teal-subtle">
          <i class="bi bi-people me-1"></i> {{ userStore.users.length }} Users
        </span>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div></div>
      <button class="btn btn-success btn-sm" @click="openAddModal" :disabled="isLoading">
        <i class="bi bi-plus-circle me-1"></i>Add New 
      </button>
    </div>

    <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ successMessage }}
      <button type="button" class="btn-close" @click="successMessage = ''"></button>
    </div>
    <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ errorMessage }}
      <button type="button" class="btn-close" @click="errorMessage = ''"></button>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(75, 182, 109, 0.1);">
            <i class="bi bi-people" style="color: #4BB66D; font-size: 32px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalUsers }}</h3>
            <p>Total Users</p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="stat-card">
          <div class="stat-icon" style="background-color: rgba(38, 213, 22, 0.1);">
            <i class="bi bi-person-badge" style="color: #26d516; font-size: 32px;"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.totalAdmins }}</h3>
            <p>Total Admins</p>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <div class="row g-3">
        <div class="col-md-8">
          <input
            type="text"
            class="form-control"
            placeholder="Search by name or email..."
            v-model="searchQuery"
            :disabled="isLoading"
          >
        </div>
        <div class="col-md-4">
          <select class="form-select" v-model="selectedRole" :disabled="isLoading">
            <option value="">All Roles</option>
            <option value="Master Admin">Master Admin</option>
            <option value="Admin">Admin</option>
            <option value="User">User</option>
          </select>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div v-if="isLoading" class="text-center py-5 text-muted">
        <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading users...
      </div>
      <div v-else-if="filteredUsers.length === 0" class="text-center py-5 text-muted">
        No users found matching your criteria.
      </div>
      <div v-else class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>User ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Role</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id">
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.department }}</td>
              <td>
                <span class="badge" :class="user.primaryRole.toLowerCase().includes('admin') ? 'bg-primary' : 'bg-info'">
                  {{ user.primaryRole }}
                </span>
              </td>
              <td>
                <!-- Status Toggle Switch -->
                <div class="form-check form-switch d-inline-block">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    :id="'statusToggle-' + user.id"
                    :checked="user.status === 'active'"
                    @change="toggleUserStatus(user)"
                    :disabled="isStatusUpdating || user.primaryRole.toLowerCase() === 'master admin'"
                  >
                  <label class="form-check-label ms-2" :for="'statusToggle-' + user.id">
                    <span class="badge" :class="user.status === 'active' ? 'bg-success' : 'bg-secondary'">
                      {{ user.status }}
                    </span>
                  </label>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="userFormModal" tabindex="-1" aria-labelledby="userFormModalLabel" aria-hidden="true" ref="userModalRef">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="userFormModalLabel">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="handleStore">
            <div class="modal-body">
              <div v-if="modalErrorMessage" class="alert alert-danger">{{ modalErrorMessage }}</div>

              <div class="mb-3">
                <label for="userName" class="form-label fw-bold">User Name <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="userName" 
                  v-model="newUser.name" 
                  :disabled="isSaving"
                  placeholder="Enter full name"
                />
                <small class="text-danger" v-if="validationErrors.name">{{ validationErrors.name[0] }}</small>
              </div>

               <div class="mb-3">
                  <label for="department" class="form-label fw-bold">Department<span class="text-danger">*</span></label>
                  <select
                      class="form-select"
                      id="department"
                      v-model="newUser.department"
                      required
                      :disabled="isLoading || isFetchingDepartments"
                  >
                      <option value="" disabled>Select your department</option>
                      <option v-for="dept in departments" :key="dept.id" :value="dept.name">
                          {{ dept.name }}
                      </option>
                  </select>
                  <div v-if="departmentsError" class="text-danger small mt-1">Failed to load departments.</div>
              </div>
              
              <div class="mb-3">
                <label for="userEmail" class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                <input 
                  type="email" 
                  class="form-control" 
                  id="userEmail" 
                  v-model="newUser.email" 
                  :disabled="isSaving"
                  placeholder="Enter email address"
                />
                <small class="text-danger" v-if="validationErrors.email">{{ validationErrors.email[0] }}</small>
              </div>

              <div class="mb-3">
                <label for="userPassword" class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="userPassword" 
                  v-model="newUser.password" 
                  :disabled="isSaving"
                  placeholder="Minimum 6 characters"
                />
                <small class="text-danger" v-if="validationErrors.password">{{ validationErrors.password[0] }}</small>
              </div>

              <div class="mb-3">
                <label for="userConfirmPassword" class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="userConfirmPassword" 
                  v-model="newUser.password_confirmation" 
                  :disabled="isSaving"
                  placeholder="Re-enter password"
                />
                <small class="text-danger" v-if="validationErrors.password_confirmation">{{ validationErrors.password_confirmation[0] }}</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" :disabled="isSaving">Cancel</button>
              <button type="submit" class="btn btn-success" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="bi bi-save me-1"></i> Save User
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Role Change Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true" ref="roleModalRef">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="roleModalLabel">Change User Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form @submit.prevent="handleRoleUpdate">
            <div class="modal-body">
              <div v-if="roleErrorMessage" class="alert alert-danger">{{ roleErrorMessage }}</div>

              <p class="mb-3">
                <span class="fw-bold">{{ userToEdit?.name }}</span>'s current role: 
                <span class="badge" :class="userToEdit?.primaryRole.toLowerCase().includes('admin') ? 'bg-primary' : 'bg-info'">
                  {{ userToEdit?.primaryRole }}
                </span>
              </p>

              <div class="mb-3">
                <label for="roleSelect" class="form-label fw-bold">New Role</label>
                <select id="roleSelect" class="form-select" v-model="selectedNewRole" :disabled="isRoleUpdating">
                  <option 
                    v-for="role in availableRoles" 
                    :key="role" 
                    :value="role">
                    {{ role }}
                  </option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" :disabled="isRoleUpdating">Cancel</button>
              <button type="submit" class="btn btn-warning text-dark" :disabled="isRoleUpdating">
                <span v-if="isRoleUpdating" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i v-else class="bi bi-person-gear me-1"></i> Update Role
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Permission Matrix Modal -->
    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true" ref="permissionModalRef">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-info">
          <div class="modal-header bg-info text-dark">
            <h5 class="modal-title" id="permissionModalLabel"><i class="bi bi-shield-lock-fill me-2"></i>Permission Matrix</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="permissionErrorMessage" class="alert alert-danger">{{ permissionErrorMessage }}</div>
            
            <div class="user-header mb-4 p-3 bg-light rounded d-flex align-items-center">
              <div class="avatar-circle me-3 bg-info text-white">
                {{ userToEditPermissions?.name?.charAt(0).toUpperCase() }}
              </div>
              <div>
                <h6 class="mb-0 fw-bold">{{ userToEditPermissions?.name }}</h6>
                <small class="text-muted">{{ userToEditPermissions?.email }}</small>
              </div>
            </div>

            <p class="text-muted small mb-4">Toggle specific task permissions for this Admin. Changes are applied immediately to all microservices.</p>

            <div class="permission-list">
              <div v-for="perm in taskPermissions" :key="perm.slug" class="permission-item d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                <div>
                  <div class="fw-bold">{{ perm.label }}</div>
                  <div class="text-muted x-small">{{ perm.description }}</div>
                </div>
                <div class="form-check form-switch">
                  <input 
                    class="form-check-input permission-toggle" 
                    type="checkbox" 
                    role="switch" 
                    :id="'perm-' + perm.slug"
                    :checked="currentUserPermissions.includes(perm.slug)"
                    @change="handlePermissionToggle(perm.slug, $event)"
                    :disabled="isPermissionUpdating"
                  >
                </div>
              </div>
            </div>
            
            <div v-if="isPermissionsLoading" class="text-center py-3">
              <span class="spinner-border spinner-border-sm text-info me-2"></span>
              Synchronizing with Auth Service...
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" :disabled="isPermissionUpdating">Close</button>
            <button type="button" class="btn btn-info text-dark fw-bold" data-bs-dismiss="modal" :disabled="isPermissionUpdating">
              <i class="bi bi-check-circle-fill me-1"></i> Finished
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal (same as category page) -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true" ref="deleteModalRef">
      <div class="modal-dialog delete-modal-top"> 
        <div class="modal-content">
          <template v-if="deleteStep === 'confirm'">
            <div class="modal-header bg-warning text-dark">
              <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              <p class="mb-0">Are you sure you want to delete the user <strong>"{{ userToDelete?.name }}"</strong>?</p>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="isDeleting">No</button>
              <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation" :disabled="isDeleting">Yes</button>
            </div>
          </template>

          <template v-else-if="deleteStep === 'final'">
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              <p class="mb-0">This action will permanently delete the user <strong>"{{ userToDelete?.name }}"</strong>. Are you sure?</p>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="isDeleting">Cancel</button>
              <button type="button" class="btn btn-danger" @click="handleDelete" :disabled="isDeleting">
                <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ isDeleting ? 'Deleting...' : 'Confirm' }}
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { userStore } from '../../store/userStore';
import { Modal } from 'bootstrap'; 
import Navbar from '../../components/Navbar.vue';
import AdminSidebar from '../../components/Sidebar/Admin_Sidebar.vue';


// --- API CONFIG ---
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'); 
const USERS_API_URL = `${API_BASE_URL}/users`; 
const getAuthToken = () => localStorage.getItem('authToken');

// --- INTERFACES ---
interface Role {
  id: number;
  name: string;
}

interface User {
  id: number | string;
  name: string;
  department:string;
  email: string;
  status: 'active' | 'inactive' | string;
  roles: Role[];
  primaryRole: string;
}

interface NewUserForm {
  name: string;
  department:string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface ValidationErrors {
  [key: string]: string[];
}

// --- STATE ---
const searchQuery = ref('');
const selectedRole = ref('');
const isLoading = computed(() => userStore.isLoading);
const isSaving = ref(false);
const isDeleting = ref(false);
const isStatusUpdating = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const modalErrorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});

const selectedDepartment = ref('');
const departments = ref<any[]>([]);
const isFetchingDepartments = ref(false);
const departmentsError = ref(false);

// Remove redundant local users ref
// const users = ref<User[]>([]);

// Delete modal state (same as category page)
const userToDelete = ref<User | null>(null);
const deleteStep = ref<'confirm' | 'final'>('confirm');

const availableRoles = ref(['Admin', 'User']);

// Role modal state
const isRoleUpdating = ref(false);
const roleErrorMessage = ref('');
const userToEdit = ref<User | null>(null);
const selectedNewRole = ref('');

// Permission modal state
const isPermissionsLoading = ref(false);
const isPermissionUpdating = ref(false);
const permissionErrorMessage = ref('');
const userToEditPermissions = ref<User | null>(null);
const currentUserPermissions = ref<string[]>([]);
const taskPermissions = [
  { slug: 'manage_resources', label: 'Resource Management', description: 'Create, Edit, and Delete physical resources' },
  { slug: 'manage_bookings', label: 'Booking Management', description: 'Manage and override user bookings' },
  { slug: 'view_reports', label: 'Financial Reports', description: 'Access financial and usage statistics' },
  { slug: 'manage_users', label: 'User Management', description: 'Manage other users and their roles' },
];

// Add User modal state
const initialNewUserState: NewUserForm = {
  name: '',
  department:'',
  email: '',
  password: '',
  password_confirmation: '',
};
const newUser = ref<NewUserForm>({ ...initialNewUserState });

// Modal references and instances
const userModalRef = ref<HTMLElement | null>(null);
const roleModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let userModalInstance: any = null;
let roleModalInstance: any = null;
let deleteModalInstance: any = null;
let permissionModalInstance: any = null;

const permissionModalRef = ref<HTMLElement | null>(null);

onMounted(async () => {
    isFetchingDepartments.value = true;
    try {
        const response = await fetch(((import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api') + '/departments'));
        if (response.ok) {
            departments.value = await response.json();
        } else {
            departmentsError.value = true;
        }
    } catch (e) {
        departmentsError.value = true;
    } finally {
        isFetchingDepartments.value = false;
    }
});


const stats = computed(() => ({
  totalUsers: userStore.users.length,
  totalAdmins: userStore.users.filter(u => u.primaryRole.toLowerCase().includes('admin')).length
}));

const filteredUsers = computed(() => {
  return userStore.users.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesRole = !selectedRole.value || user.primaryRole.toLowerCase() === selectedRole.value.toLowerCase();
    return matchesSearch && matchesRole;
  });
});

// --- HELPER FUNCTIONS ---
const handleApiError = (data: any, status: number) => {
  validationErrors.value = {};
  if (status === 422 && data.errors) {
    validationErrors.value = data.errors;
    modalErrorMessage.value = "Validation failed. Please check the fields and try again.";
  } else {
    errorMessage.value = data?.message || `Failed to perform operation (Status: ${status}).`;
  }
};

const resetNewUserForm = () => {
  newUser.value = { ...initialNewUserState };
  validationErrors.value = {};
  modalErrorMessage.value = '';
};

// --- STATUS TOGGLE FUNCTIONALITY ---
const toggleUserStatus = async (user: User) => {
  isStatusUpdating.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  
  const token = getAuthToken();
  if (!token) {
    isStatusUpdating.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  const userId = user.id;
  const newStatus = user.status === 'active' ? 'inactive' : 'active';
  
  // Use the existing update endpoint (PUT /api/users/{user})
  const url = `${USERS_API_URL}/${userId}`;

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json' 
      },
      body: JSON.stringify({
        status: newStatus,
      }),
    });

    const responseText = await response.text();
    let data = null;
    
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      errorMessage.value = `Error: Failed to process request (Status: ${response.status}).`;
      isStatusUpdating.value = false;
      // Revert the toggle if API call fails
      await userStore.fetchUsers(true);
      return;
    }

    if (response.ok) {
      userStore.updateUserLocally(user.id, { status: newStatus });               
    } else {
      handleApiError(data, response.status);
      // Revert the toggle if API call fails
      await userStore.fetchUsers(true);
    }
  } catch (e) {
    errorMessage.value = 'Network error: Could not connect to the API Gateway.';
    // Revert the toggle if network error
    await userStore.fetchUsers(true);
  } finally {
    isStatusUpdating.value = false;
  }
};

// --- DELETE FUNCTIONALITY (same as category page) ---
const openDeleteConfirmation = (user: User) => {
  userToDelete.value = user;
  deleteStep.value = 'confirm'; 
  deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
  deleteStep.value = 'final'; 
};

const handleCancelDeletion = () => {
  deleteModalInstance?.hide();
  userToDelete.value = null;
  deleteStep.value = 'confirm'; 
};

const handleDelete = async () => {
  if (deleteStep.value !== 'final' || !userToDelete.value) return; 
  
  isDeleting.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  
  const token = getAuthToken();
  const userId = userToDelete.value.id;

  if (!token) {
    isDeleting.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  try {
    const response = await fetch(`${USERS_API_URL}/${userId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });
    
    const data = await response.json().catch(() => ({ 
      message: 'Request successful but no content received.', 
      status: response.status 
    }));

    if (response.ok) {
      userStore.removeUserLocally(userToDelete.value.id);
      deleteModalInstance?.hide();
    } else {
      handleApiError(data, response.status);
    }
  } catch (e) {
    console.error('Failed to delete user:', e);
    errorMessage.value = 'Failed to delete user due to a network error.';
  } finally {
    isDeleting.value = false;
    if (successMessage.value || errorMessage.value) {
      userToDelete.value = null;
      deleteStep.value = 'confirm'; 
    }
  }
};

// --- ROLE CHANGE FUNCTIONALITY ---
const openRoleModal = (user: User) => {
  roleErrorMessage.value = '';
  userToEdit.value = user;
  selectedNewRole.value = user.primaryRole; 
  roleModalInstance?.show();
};

const handleRoleUpdate = async () => {
  if (!userToEdit.value || !selectedNewRole.value || selectedNewRole.value === userToEdit.value.primaryRole) {
    roleErrorMessage.value = 'Please select a new role to update.';
    return;
  }

  isRoleUpdating.value = true;
  roleErrorMessage.value = '';
  errorMessage.value = '';
  const token = getAuthToken();

  if (!token) {
    isRoleUpdating.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  const userId = userToEdit.value.id;
  const url = `${USERS_API_URL}/${userId}`;

  try {
    const response = await fetch(url, {
      method: 'PUT',
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json' 
      },
      body: JSON.stringify({
        role: selectedNewRole.value,
      }),
    });

    const responseText = await response.text();
    let data = null;
    
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      roleErrorMessage.value = `Error: Failed to process request (Status: ${response.status}).`;
      isRoleUpdating.value = false;
      return;
    }

    if (response.ok) {
      successMessage.value = `Role for ${userToEdit.value.name} updated to ${selectedNewRole.value} successfully!`;
      roleModalInstance?.hide();
      await userStore.fetchUsers(true);
    } else {
      roleErrorMessage.value = data.message || `Update failed (Status: ${response.status}).`;
    }
  } catch (e) {
    roleErrorMessage.value = 'Network error: Could not connect to the API Gateway.';
  } finally {
    isRoleUpdating.value = false;
  }
};

// --- PERMISSION MATRIX FUNCTIONALITY ---
const openPermissionModal = async (user: User) => {
  permissionErrorMessage.value = '';
  userToEditPermissions.value = user;
  currentUserPermissions.value = [];
  isPermissionsLoading.value = true;
  
  permissionModalInstance?.show();

  try {
    const permissions = await userStore.fetchUserPermissions(user.id);
    currentUserPermissions.value = permissions;
  } catch (e) {
    permissionErrorMessage.value = 'Failed to load user permissions. Please try again.';
  } finally {
    isPermissionsLoading.value = false;
  }
};

const handlePermissionToggle = async (slug: string, event: Event) => {
  const isChecked = (event.target as HTMLInputElement).checked;
  if (!userToEditPermissions.value) return;

  isPermissionUpdating.value = true;
  permissionErrorMessage.value = '';

  try {
    await userStore.updateUserPermission(userToEditPermissions.value.id, slug, isChecked);
    
    // Update local list
    if (isChecked) {
      if (!currentUserPermissions.value.includes(slug)) {
        currentUserPermissions.value.push(slug);
      }
    } else {
      currentUserPermissions.value = currentUserPermissions.value.filter(p => p !== slug);
    }
    
    successMessage.value = `Permission "${slug}" updated for ${userToEditPermissions.value.name}`;
  } catch (e) {
    permissionErrorMessage.value = 'Failed to update permission. Reverting UI state...';
    // Revert the checkbox in UI
    (event.target as HTMLInputElement).checked = !isChecked;
  } finally {
    isPermissionUpdating.value = false;
  }
};

// --- ADD USER FUNCTIONALITY ---
const openAddModal = () => {
  resetNewUserForm();
  userModalInstance?.show();
};

const handleStore = async () => {
  isSaving.value = true;
  modalErrorMessage.value = '';
  errorMessage.value = '';
  successMessage.value = '';
  
  const token = getAuthToken();
  if (!token) {
    isSaving.value = false;
    errorMessage.value = "Authentication token missing.";
    return;
  }

  try {
    const response = await fetch(USERS_API_URL, {
      method: 'POST',
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json' 
      },
      body: JSON.stringify({
        name: newUser.value.name,
        email: newUser.value.email,
        password: newUser.value.password,
        department:newUser.value.department,
        password_confirmation: newUser.value.password_confirmation,
      }),
    });

    const responseText = await response.text();
    let data = null;
    
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      handleApiError(null, response.status);
      userModalInstance?.hide();
      return;
    }

    if (response.ok) {
      userStore.addUserLocally(data);
      userModalInstance?.hide();
      successMessage.value = "User added successfully!";
    } else {
      handleApiError(data, response.status);
    }
  } catch (e) {
    modalErrorMessage.value = 'Network error: Could not connect to the API Gateway.';
  } finally {
    isSaving.value = false;
  }
};

// --- FETCH USERS ---
// Redundant fetchUsers removed in favor of userStore.fetchUsers
/*
const fetchUsers = async () => {
...
};
*/

// --- LIFECYCLE HOOK ---
onMounted(async () => {
  // 1. Initialize Bootstrap Modals first
  if (userModalRef.value) {
    userModalInstance = new Modal(userModalRef.value);
    userModalRef.value.addEventListener('hidden.bs.modal', resetNewUserForm);
  }
  
  if (roleModalRef.value) {
    roleModalInstance = new Modal(roleModalRef.value);
  }
  
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value);
    deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
  }

  if (permissionModalRef.value) {
    permissionModalInstance = new Modal(permissionModalRef.value);
  }

  // 2. Global Store Logic: Trigger fetch if needed
  await userStore.fetchUsers();
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

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat-icon {
  width: 70px;
  height: 70px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-content h3 {
  font-size: 32px;
  font-weight: 700;
  color: #1e4449;
  margin: 0;
}

.stat-content p {
  margin: 0;
  color: #6c757d;
}

.table-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.table thead {
  background: #f8f9fa;
}

.btn-success {
  background-color: #4BB66D; 
  border-color: #4BB66D;
}

.btn-success:hover {
  background-color: #3f975b;
  border-color: #3f975b;
}

/* Action button styling */
.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.btn-outline-danger {
  --bs-btn-color: #dc3545;
  --bs-btn-border-color: #dc3545;
}

.btn-outline-warning {
  --bs-btn-color: #ffc107;
  --bs-btn-border-color: #ffc107;
}

/* Status toggle switch styling */
.form-check-input:checked {
  background-color: #4BB66D;
  border-color: #4BB66D;
}

.form-check-input:focus {
  border-color: #4BB66D;
  box-shadow: 0 0 0 0.25rem rgba(75, 182, 109, 0.25);
}

/* Permission Modal Custom Styles */
.avatar-circle {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 1.2rem;
}

.x-small {
  font-size: 0.75rem;
}

.permission-toggle:checked {
  background-color: #0dcaf0;
  border-color: #0dcaf0;
}

.border-info {
  border-color: #0dcaf0 !important;
}

.btn-info {
  background-color: #0dcaf0;
  border-color: #0dcaf0;
}

.btn-outline-info {
  color: #0dcaf0;
  border-color: #0dcaf0;
}

.btn-outline-info:hover {
  background-color: #0dcaf0;
  color: white;
}

.form-switch {
  padding-left: 2.5em;
}

.form-switch .form-check-input {
  width: 2em;
  margin-left: -2.5em;
}

/* Delete modal styling (same as category page) */
.modal-dialog.delete-modal-top { 
  align-items: flex-start; 
  margin-top: 50px; 
  height: auto; 
}

.btn-warning {
  color: #212529 !important;
  background-color: #ffc107 !important;
  border-color: #ffc107 !important;
}

.btn-warning:hover {
  background-color: #e0a800 !important;
  border-color: #e0a800 !important;
}
</style>