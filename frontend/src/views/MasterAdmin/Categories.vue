<template>
    <navbar/>
    <master-admin-sidebar/>
    <div class="category-page section">
        <h2 class="section-title">Resource Categories</h2>
        
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
                    placeholder="Search Category..."
                    v-model="searchTerm"
                />
            </div>
            <button
                @click="openAddCategoryModal"
                class="btn btn-success add-new-btn" 
                :disabled="loading"
            >
                <i class="bi bi-plus-circle me-2"></i>Add New Category
            </button>
        </div>
        <div class="table-card">
            
            <h5 class="mb-3">Category List</h5>

            <div v-if="loading" class="text-center py-5 text-muted">
                 <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading categories...
            </div>

            <div v-else-if="filteredCategories.length === 0" class="text-center py-5 text-muted">
                {{ searchTerm ? 'No categories found matching your search.' : 'No categories yet. Add your first category!' }}
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Category ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(category, index) in filteredCategories" :key="category.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ category.name }}</td>
                            <td>{{ category.description }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        @click="openEditCategoryModal(category)"
                                        class="btn btn-outline-primary"
                                        title="Edit"
                                        :disabled="saving"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        @click="openDeleteConfirmation(category)"
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

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true" ref="categoryModalRef">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">{{ isEditMode ? 'Edit Category' : 'Add New Category' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="handleSave">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="categoryName"
                                placeholder="Enter category name (e.g., Academic Space)"
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
                        <p class="mb-0">Are you sure you want to delete the category **{{ categoryToDelete?.name }}**?</p>
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
                        <p class="mb-0">This action will permanently delete the category **{{ categoryToDelete?.name }}**. Are you sure?</p>
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
const CATEGORIES_API_URL = `${API_BASE_URL}/categories`; 
const getAuthToken = () => localStorage.getItem('authToken');

// --- TYPES ---
interface Category {
    id: number;
    name: string; 
    description: string;
}

interface ValidationErrors {
    [key: string]: string[];
}

// --- STATE ---

const categories = computed(() => resourceStore.categories); 
const searchTerm = ref('');
const loading = computed(() => resourceStore.isLoading);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});


// Modal state
const isEditMode = ref(false);
const modalData = ref<Partial<Category>>({
    name: '',
    description: '',
});
const categoryToDelete = ref<Category | null>(null);
const deleteStep = ref<'confirm' | 'final'>('confirm');

// Bootstrap Modal References and Instances
const categoryModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let categoryModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// --- COMPUTED PROPERTIES ---

const filteredCategories = computed(() => {
    const term = searchTerm.value.toLowerCase();
    
    return categories.value.filter(
        (category) =>
            category.name.toLowerCase().includes(term) ||
            category.description.toLowerCase().includes(term)
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
 * POST/PUT: Handles creating or updating a category.
 */

/**
 * POST/PUT: Handles creating or updating a category.
 */
const handleSave = async () => {
    if (!modalData.value.name) {
        errorMessage.value = 'Category Name is required.';
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
    const url = isUpdate ? `${CATEGORIES_API_URL}/${modalData.value.id}` : CATEGORIES_API_URL;
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

        // The Gateway returns the response JSON directly, but we must handle empty body cases
        const data = await response.json().catch(() => ({ 
            message: response.statusText, 
            status: response.status 
        }));

        if (response.ok) { // 200 OK or 201 CREATED
            successMessage.value = data.message || (isUpdate ? 'Category updated successfully!' : 'Category added successfully!');
            
            // Update store
            if (isUpdate) {
                resourceStore.updateCategory(data.category || data);
            } else {
                resourceStore.addCategory(data.category || data);
            }
            
            categoryModalInstance?.hide();
        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        console.error('Failed to save category:', e);
        errorMessage.value = 'Failed to save category due to a network error.';
    } finally {
        saving.value = false;
    }
};

/**
 * DELETE: Handles deleting a category.
 */
const handleDelete = async () => {
    if (deleteStep.value !== 'final' || !categoryToDelete.value) return; 
    
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    const token = getAuthToken();
    const categoryId = categoryToDelete.value.id;

    try {
        const response = await fetch(`${CATEGORIES_API_URL}/${categoryId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
        });
        
        // The Gateway returns the response JSON directly.
        const data = await response.json().catch(() => ({ message: 'Request successful but no content received.', status: response.status }));

        if (response.ok) { // 200 OK
            successMessage.value = data.message || 'Category deleted successfully.';
            resourceStore.removeCategory(categoryId);
            deleteModalInstance?.hide();
        } else {
            handleApiError(data, response.status);
        }
    } catch (e) {
        console.error('Failed to delete category:', e);
        errorMessage.value = 'Failed to delete category due to a network error.';
    } finally {
        saving.value = false;
        // Only reset deletion state if successful or final error handled
        if (successMessage.value || errorMessage.value) {
            categoryToDelete.value = null;
            deleteStep.value = 'confirm'; 
        }
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

const openAddCategoryModal = () => {
    isEditMode.value = false;
    resetModalData();
    categoryModalInstance?.show();
};

const openEditCategoryModal = (category: Category) => {
    isEditMode.value = true;
    modalData.value = {
        id: category.id,
        name: category.name,
        description: category.description,
    };
    validationErrors.value = {};
    categoryModalInstance?.show();
};

const openDeleteConfirmation = (category: Category) => {
    categoryToDelete.value = category;
    deleteStep.value = 'confirm'; 
    deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final'; 
};

const handleCancelDeletion = () => {
    deleteModalInstance?.hide();
    categoryToDelete.value = null;
    deleteStep.value = 'confirm'; 
};

onMounted(() => {
    // Initialize Bootstrap Modals
    if (categoryModalRef.value) {
        categoryModalInstance = new Modal(categoryModalRef.value);
        categoryModalRef.value.addEventListener('hidden.bs.modal', resetModalData);
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