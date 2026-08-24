<template>
    <navbar/>
    <master-admin-sidebar/>
    <div class="category-page section">
        <h2 class="section-title">Department</h2>
        
        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ successMessage }}
            <button type="button" class="btn-close" @click="successMessage = ''"></button>
        </div>
        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ errorMessage }}
            <button type="button" class="btn-close" @click="errorMessage = ''"></button>
        </div>

        <div class="page-header">
            <div class="input-group mb-3 mb-md-0 w-100 w-md-auto me-md-3" style="max-width: 300px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input
                    type="text"
                    class="form-control"
                    placeholder="Search Department..."
                    v-model="searchTerm"
                />
            </div>
            <button
                @click="openAddDepartmentsModal"
                class="btn btn-success add-new-btn" 
                :disabled="loading"
            >
                <i class="bi bi-plus-circle me-2"></i>Add New Department
            </button>
        </div>
        <div class="table-card">
            
            <h5 class="mb-3">Department List</h5>

            <div v-if="loading" class="text-center py-5 text-muted">
                 <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading department...
            </div>

            <div v-else-if="filteredDepartments.length === 0" class="text-center py-5 text-muted">
                {{ searchTerm ? 'No department found matching your search.' : 'No departments yet. Add your first department!' }}
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Department ID</th>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(department, index) in filteredDepartments" :key="department.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ department.name }}</td>
                            <td>{{ department.description }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        @click="openEditDepartmentModal(department)"
                                        class="btn btn-outline-primary"
                                        title="Edit"
                                        :disabled="saving"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        @click="openDeleteConfirmation(department)"
                                        class="btn btn-outline-danger ms-1"
                                        title="Delete"
                                        :disabled="saving"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true" ref="departmentModalRef">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentModalLabel">{{ isEditMode ? 'Edit Department' : 'Add New Department' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="handleSave">
                        <div class="mb-3">
                            <label for="departmentName" class="form-label">Department Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="categoryName"
                                placeholder="Enter department name (e.g., Computer Science)"
                                v-model="modalData.name"
                                required
                                :disabled="saving"
                            >
                            <small class="text-danger" v-if="validationErrors.name">{{ validationErrors.name[0] }}</small>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input
                                type="text"
                                class="form-control"
                                id="description"
                                placeholder="Enter description (e.g., Lecture Halls)"
                                v-model="modalData.description"
                                :disabled="saving"
                            >
                            <small class="text-danger" v-if="validationErrors.description">{{ validationErrors.description[0] }}</small>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ saving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update' : 'Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true" ref="deleteModalRef">
        <div class="modal-dialog delete-modal-top"> 
            <div class="modal-content">

                <template v-if="deleteStep === 'confirm'">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-question-circle-fill me-2"></i>Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-0">Are you sure you want to delete the department **{{ departmentToDelete?.name }}**?</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="saving">No</button>
                        <button type="button" class="btn btn-warning text-dark" @click="handleFirstConfirmation" :disabled="saving">Yes</button>
                    </div>
                </template>

                <template v-else-if="deleteStep === 'final'">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-0">This action will permanently delete the department **{{ departmentToDelete?.name }}**. Are you sure?</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="saving">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="handleDelete" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ saving ? 'Deleting...' : 'Confirm' }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Modal } from 'bootstrap'; 
import { resourceStore } from '../../store/resourceStore';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- API CONFIG ---
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'); 
const DEPARTMENTS_API_URL = `${API_BASE_URL}/departments`; 
const getAuthToken = () => localStorage.getItem('authToken');

// --- TYPES ---
interface Department {
    id: number;
    name: string; 
    description: string;
}

interface ValidationErrors {
    [key: string]: string[];
}

// --- STATE ---

const departments = computed(() => resourceStore.departments); 
const searchTerm = ref('');
const loading = computed(() => resourceStore.isLoading);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});


// Modal state
const isEditMode = ref(false);
const modalData = ref<Partial<Department>>({
    name: '',
    description: '',
});
const departmentToDelete = ref<Department | null>(null);
const deleteStep = ref<'confirm' | 'final'>('confirm');

// Bootstrap Modal References and Instances
const departmentModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let departmentModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// --- COMPUTED PROPERTIES ---

const filteredDepartments = computed(() => {
    const term = searchTerm.value.toLowerCase();
    
    return departments.value.filter(
        (department) =>
            department.name.toLowerCase().includes(term) ||
            department.description.toLowerCase().includes(term)
    );
});

// --- API METHODS ---

const handleApiError = (data: any, status: number) => {
    validationErrors.value = {};
    if (status === 422 && data.errors) {
        // Laravel validation errors
        validationErrors.value = data.errors;
        errorMessage.value = "Validation failed. Check the modal fields.";
    } else if (status === 503) {
        // Gateway connection error
        errorMessage.value = data.message || "Failed to connect to the resource service.";
    } else {
        // General error
        errorMessage.value = data.message || `An error occurred (Status: ${status}).`;
    }
};

/**
 * POST/PUT: Handles creating or updating a department.
 */

/**
 * POST/PUT: Handles creating or updating a department.
 */
const handleSave = async () => {
    if (!modalData.value.name) {
        errorMessage.value = 'Department Name is required.';
        return;
    }

    saving.value = true;
    errorMessage.value = '';
    validationErrors.value = {};
    successMessage.value = '';
    
    const token = getAuthToken();
    if (!token) {
        saving.value = false;
        errorMessage.value = "Authentication token missing.";
        return;
    }
    
    const isUpdate = isEditMode.value && modalData.value.id;
    const url = isUpdate ? `${DEPARTMENTS_API_URL}/${modalData.value.id}` : DEPARTMENTS_API_URL;
    const method = isUpdate ? 'PUT' : 'POST';

    try {
        const payload = {
            name: modalData.value.name.trim(),
            description: modalData.value.description ? modalData.value.description.trim() : null,
        };

        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        // Try to parse JSON, but handle empty responses
        let data: any;
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            // Handle non-JSON responses or empty responses
            data = { 
                message: response.status === 204 ? 'Operation successful' : response.statusText,
                status: response.status 
            };
        }

        if (response.ok) { // 200 OK or 201 CREATED
            successMessage.value = data.message || (isUpdate ? 'Department updated successfully!' : 'Department added successfully!');
            
            // Update store
            if (isUpdate) {
                resourceStore.updateDepartment(data.department || data);
            } else {
                resourceStore.addDepartment(data.department || data);
            }
            
            departmentModalInstance?.hide();
        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        console.error('Failed to save department:', e);
        errorMessage.value = 'Failed to save department due to a network error.';
    } finally {
        saving.value = false;
    }
};

/**
 * DELETE: Handles deleting a department.
 */
const handleDelete = async () => {
    if (deleteStep.value !== 'final' || !departmentToDelete.value) return; 
    
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    const token = getAuthToken();
    const departmentId = departmentToDelete.value.id;

    try {
        const response = await fetch(`${DEPARTMENTS_API_URL}/${departmentId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
        });
        
        // FIXED: Properly handle different response types
        let data: any;
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            // Try to parse as JSON
            try {
                data = await response.json();
            } catch (e) {
                // If JSON parsing fails, create a simple data object
                data = { message: 'Operation completed successfully' };
            }
        } else {
            // Handle non-JSON responses (empty body, text, etc.)
            // For 204 No Content or empty 200 responses
            if (response.status === 204 || response.ok) {
                data = { message: 'Department deleted successfully' };
            } else {
                data = { 
                    message: response.statusText || 'Delete operation completed',
                    status: response.status 
                };
            }
        }

        if (response.ok) { // 200 OK or 204 No Content
            successMessage.value = data.message || 'Department deleted successfully.';
            resourceStore.removeDepartment(departmentId);
            deleteModalInstance?.hide();
            
            // Reset deletion state after successful delete
            departmentToDelete.value = null;
            deleteStep.value = 'confirm';
        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        console.error('Failed to delete department:', e);
        errorMessage.value = 'Failed to delete department due to a network error.';
    } finally {
        saving.value = false;
    }
};

// --- MODAL AND UI HANDLERS ---

const resetModalData = () => {
    modalData.value = {
        name: '',
        description: '',
    };
    validationErrors.value = {};
};

const openAddDepartmentsModal = () => {
    isEditMode.value = false;
    resetModalData();
    departmentModalInstance?.show();
};

const openEditDepartmentModal = (department: Department) => {
    isEditMode.value = true;
    modalData.value = {
        id: department.id,
        name: department.name,
        description: department.description,
    };
    validationErrors.value = {};
    departmentModalInstance?.show();
};

const openDeleteConfirmation = (department: Department) => {
    departmentToDelete.value = department;
    deleteStep.value = 'confirm'; 
    deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final'; 
};

const handleCancelDeletion = () => {
    deleteModalInstance?.hide();
    departmentToDelete.value = null;
    deleteStep.value = 'confirm'; 
};

// --- LIFECYCLE ---
onMounted(() => {
    // Initialize Bootstrap Modals
    if (departmentModalRef.value) {
        departmentModalInstance = new Modal(departmentModalRef.value);
        departmentModalRef.value.addEventListener('hidden.bs.modal', resetModalData);
    }
    if (deleteModalRef.value) {
        deleteModalInstance = new Modal(deleteModalRef.value);
        deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
    }
    
    // Fetch initial data if not loaded
    if (!resourceStore.isLoaded) {
        resourceStore.fetchAll();
    }
});
</script>

<style scoped>
/* --- General Section & Sidebar Layout --- */
.section {
    animation: fadeIn 0.3s ease;
    padding: 20px; 
    margin-left: 260px; /* Standard sidebar width */
}
@media (max-width: 768px) {
    .section {
        margin-left: 80px; /* Collapsed sidebar width */
    }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.section-title {
    color: #1e4449;
    font-weight: 600;
    margin-bottom: 24px;
}
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px; 
    gap: 20px;
}
/* Custom Success Button Color */
.btn-success { background-color: #4BB66D; border-color: #4BB66D; }
.btn-success:hover { background-color: #3f975b; border-color: #3f975b; }
.btn-success.add-new-btn { padding: 10px 20px; border-radius: 8px; }
/* Table card structure */
.table-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); 
}
.table thead { background: #f8f9fa; }
.table thead th {
    background-color: #f8f9fa; 
    font-weight: 600;
    border-bottom: 1px solid #dee2e6; 
    padding: 12px 15px;
}
.table tbody td { padding: 12px 15px; vertical-align: middle; }
/* Ensure outline buttons are visible */
.btn-outline-primary { --bs-btn-color: #0d6efd; --bs-btn-border-color: #0d6efd; }
.btn-outline-danger { --bs-btn-color: #dc3545; --bs-btn-border-color: #dc3545; }
/* Action button sizing */
.btn-group-sm .btn { padding: 0.25rem 0.5rem; }
/* NEW STYLING TO MOVE DELETE MODAL TO THE TOP */
.modal-dialog.delete-modal-top { align-items: flex-start; margin-top: 50px; height: auto; }
/* Custom style for the first step button (Modal) */
.btn-warning {
    color: #212529 !important;
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
}
.btn-warning:hover {
    background-color: #e0a800 !important;
    border-color: #e0a800 !important;
}
/* Modal styling (Already consistent, just ensuring defaults are here) */
.modal-dialog.modal-lg { max-width: 900px !important; }
@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: stretch; }
    .input-group { width: 100% !important; max-width: 100% !important; }
    .btn-success.add-new-btn { width: 100%; }
    .modal-dialog.modal-lg { max-width: 95% !important; }
}
</style>