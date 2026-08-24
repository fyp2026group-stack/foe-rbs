<template>
    <navbar/>
    <master-admin-sidebar/>
    <div class="booking-items-page section">
        <h2 class="section-title">Booking Items Management</h2>
        
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
                    placeholder="Search Booking Items..."
                    v-model="searchTerm"
                    :disabled="loading"
                />
            </div>
                 <button class="btn btn-success btn-sm" @click="openAddItemModal" :disabled="loading">
                    <i class="bi bi-plus-circle me-2"></i>Add Booking Item
                 </button>
        </div>
        
        <div class="table-card">
            <h5 class="mb-3">Booking Item List</h5>

            <div v-if="loading" class="text-center py-5 text-muted">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading booking items...
            </div>

            <div v-else-if="filteredItems.length === 0" class="text-center py-5 text-muted">
                {{ searchTerm ? 'No items found matching your search.' : 'No booking items yet. Add your first item!' }}
            </div>

            <div v-else class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price (Rs./hr)</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in filteredItems" :key="item.id">
                            <td>{{ item.id }}</td>
                            <td>{{ item.name }}</td>
                            <td>{{ item.description }}</td>
                            <td>Rs. {{ Number(item.price_per_hour).toFixed(2) }}</td>
                            <td>{{ item.available_quantity }}</td>
                            <td>
                                <span :class="item.status === 'Available' ? 'text-success' : 'text-danger'" class="fw-medium">
                                    <i :class="item.status === 'Available' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'" class="me-1"></i>
                                    {{ item.status }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        @click="openEditItemModal(item)"
                                        class="btn btn-outline-primary"
                                        title="Edit"
                                        :disabled="saving"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        @click="openDeleteConfirmation(item)"
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

    <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true" ref="itemModalRef">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalLabel">{{ isEditMode ? 'Edit Booking Item' : 'Add New Booking Item' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="handleSave">
                         <div v-if="validationErrors.general" class="alert alert-warning mb-3">
                            {{ validationErrors.general[0] }}
                        </div>
                        <div v-if="savingErrorMessage" class="alert alert-danger mb-3">
                            {{ savingErrorMessage }}
                        </div>
                        
                        <div class="mb-3">
                            <label for="itemName" class="form-label">Booking Item Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="itemName"
                                placeholder="Enter item name (e.g., Tennis Racket)"
                                v-model="modalData.name"
                                required
                                :disabled="saving"
                            >
                            <small class="text-danger" v-if="validationErrors.name">{{ validationErrors.name[0] }}</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                class="form-control"
                                id="description"
                                rows="2"
                                placeholder="Brief description of the item"
                                v-model="modalData.description"
                                :disabled="saving"
                            ></textarea>
                            <small class="text-danger" v-if="validationErrors.description">{{ validationErrors.description[0] }}</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="pricePerHour" class="form-label">Price (Rs./hr) <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="pricePerHour"
                                    placeholder="e.g., 50.00"
                                    v-model.number="modalData.price_per_hour"
                                    min="0"
                                    step="0.01"
                                    required
                                    :disabled="saving"
                                >
                                <small class="text-danger" v-if="validationErrors.price_per_hour">{{ validationErrors.price_per_hour[0] }}</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="quantity"
                                    placeholder="e.g., 10"
                                    v-model.number="modalData.available_quantity"
                                    min="0"
                                    step="1"
                                    required
                                    :disabled="saving"
                                >
                                <small class="text-danger" v-if="validationErrors.available_quantity">{{ validationErrors.available_quantity[0] }}</small>
                            </div>
                           <div class="col-md-4 mb-3 d-flex align-items-center pt-md-4">
                                <div class="form-check form-switch">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="itemAvailability" 
                                        :checked="modalData.status === 'Available'"
                                        @change="modalData.status = $event.target.checked ? 'Available' : 'Unavailable'"
                                        :disabled="saving || modalData.available_quantity <= 0"
                                    >
                                    <label class="form-check-label" for="itemAvailability">
                                        {{ modalData.available_quantity <= 0 ? 'Out of Stock' : (modalData.status === 'Available' ? 'Currently Available' : 'Manually Unavailable') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-success" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ saving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update Item' : 'Save Item') }}
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
                        <p class="mb-0">Are you sure you want to delete the item **{{ itemToDelete?.name }}**?</p>
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
                        <p class="mb-0">This action will **permanently delete** the item **{{ itemToDelete?.name }}**. Are you sure?</p>
                         <p v-if="saving" class="text-danger mt-3"><span class="spinner-border spinner-border-sm me-2"></span>Deleting...</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" @click="handleCancelDeletion" :disabled="saving">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="handleDelete" :disabled="saving">
                            {{ saving ? 'Deleting...' : 'Confirm' }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Modal } from 'bootstrap'; 
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// --- API CONFIG ---
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'); 
const ITEMS_API_URL = `${API_BASE_URL}/booking-items`; 
const getAuthToken = () => localStorage.getItem('authToken');

// --- TYPES ---
interface BookingItem {
    id: number;
    name: string;
    description: string;
    // CRITICAL: Ensure these types are Number for toFixed() to work.
    price_per_hour: number;
    available_quantity: number; 
    status: 'Available' | 'Unavailable' | 'Maintenance'; 
}

interface ValidationErrors {
    [key: string]: string[];
}

// --- STATE ---

const items = ref<BookingItem[]>([]); 
const searchTerm = ref('');
const loading = ref(true);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const savingErrorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});


// Modal state
const isEditMode = ref(false);
const modalData = ref<Partial<BookingItem>>({
    name: '',
    description: '',
    price_per_hour: 0.00,
    available_quantity: 1,
    status: 'Available', 
});
const itemToDelete = ref<BookingItem | null>(null);

// NEW STATE FOR TWO-STEP DELETE
const deleteStep = ref<'confirm' | 'final'>('confirm');

// Bootstrap Modal References and Instances
const itemModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let itemModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// --- COMPUTED PROPERTIES ---

const filteredItems = computed(() => {
    const term = searchTerm.value.toLowerCase();
    
    return items.value.filter(
        (item) =>
            item.name.toLowerCase().includes(term) ||
            item.description.toLowerCase().includes(term) ||
            item.id.toString().includes(term)
    );
});

// --- WATCHERS ---
// Auto-update status logic based on quantity
watch(() => modalData.value.available_quantity, (newQty, oldQty) => {
    // 1. If quantity hits 0 or less, always force 'Unavailable' (Out of Stock).
    if (newQty !== undefined && newQty <= 0) {
        modalData.value.status = 'Unavailable';
    } 
    // 2. If quantity becomes positive (was 0 or less) AND the status is 'Unavailable',
    //    assume the unavailability was due to lack of stock and set it back to 'Available'.
    //    (This prevents manual checks overriding the user's intent to keep it unavailable for maintenance).
    else if (newQty !== undefined && newQty > 0 && (oldQty === undefined || oldQty <= 0) && modalData.value.status === 'Unavailable') {
        modalData.value.status = 'Available';
    }
}, { immediate: true }); 
// Adding { immediate: true } ensures the status is correct on modal open based on the initial quantity.


// You can also simplify the manual change handler in the template to avoid the watch completely if you rely solely on Vue's v-model concept:
// (The template change above using @change is the most direct fix for the original issue.)

// --- API METHODS ---

const handleApiError = (data: any, status: number, isFormSubmit: boolean) => {
    validationErrors.value = {};
    const message = data.message || `An error occurred (Status: ${status}).`;

    if (status === 422 && data.errors) {
        // Laravel validation errors
        validationErrors.value = data.errors;
        const firstError = Object.values(data.errors).flat()[0] || "Validation failed.";
        if (isFormSubmit) {
            
            savingErrorMessage.value = firstError;
        } else {
            errorMessage.value = firstError;
        }
    } else {
        // Gateway/Authentication/General Error
        if (isFormSubmit) {
            savingErrorMessage.value = message;
        } else {
            errorMessage.value = message;
        }
    }
};

/**
 * GET: Fetches all booking items.
 */
const fetchItems = async () => {
    loading.value = true;
    errorMessage.value = '';
    const token = getAuthToken();

    if (!token) {
        errorMessage.value = "Authentication token missing. Please log in.";
        loading.value = false;
        return;
    }

    try {
        const response = await fetch(ITEMS_API_URL, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const data = await response.json().catch(() => {
             // Failed to parse JSON, returning null/empty array
            console.error(`Failed to parse JSON for GET ${ITEMS_API_URL}. Status: ${response.status}`);
            return response.status === 200 ? [] : null; 
        });

        if (response.ok) { 
            // CRITICAL FIX: Ensure incoming values are explicitly converted to Number
            if (Array.isArray(data)) {
                items.value = data.map(item => ({
                    ...item,
                    price_per_hour: Number(item.price_per_hour),
                    available_quantity: Number(item.available_quantity),
                }));
            } else {
                items.value = [];
            }
        } else {
            handleApiError(data, response.status, false);
        }
    } catch (e) {
        errorMessage.value = 'Network error: Could not reach the API server.';
    } finally {
        loading.value = false;
    }
};

/**
 * POST/PUT: Handles creating or updating a booking item.
 */
const handleSave = async () => {
    if (!modalData.value.name || modalData.value.price_per_hour === undefined || modalData.value.available_quantity === undefined) {
        savingErrorMessage.value = 'Please fill in all required fields.';
        return;
    }
    
    saving.value = true;
    savingErrorMessage.value = '';
    successMessage.value = '';
    validationErrors.value = {};

    const token = getAuthToken();
    const isUpdate = isEditMode.value && modalData.value.id;
    
    const itemId = modalData.value.id;
    const url = isUpdate ? `${ITEMS_API_URL}/${itemId}` : ITEMS_API_URL;
    const method = isUpdate ? 'PUT' : 'POST';

    try {
        // Map frontend keys to backend validation keys
        const payload = {
            name: modalData.value.name.trim(),
            description: modalData.value.description ? modalData.value.description.trim() : null,
            price_per_hour: modalData.value.price_per_hour,
            available_quantity: modalData.value.available_quantity,
            status: modalData.value.status, 
        };

        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({ 
            message: response.statusText, 
            status: response.status 
        }));

        if (response.ok) { // Status 200/201
            successMessage.value = data.message || (isUpdate ? 'Booking item updated successfully!' : 'Booking item added successfully!');
            await fetchItems();
            itemModalInstance?.hide();
        } else {
            handleApiError(data, response.status, true);
        }
    } catch (e) {
        console.error('Failed to save item:', e);
        savingErrorMessage.value = 'Failed to save item due to network error.';
    } finally {
        saving.value = false;
    }
};

/**
 * DELETE: Handles deleting a booking item.
 */
const handleDelete = async () => {
    if (deleteStep.value !== 'final' || !itemToDelete.value) return; 
    
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    const token = getAuthToken();
    const itemId = itemToDelete.value.id;

    try {
        const response = await fetch(`${ITEMS_API_URL}/${itemId}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const data = await response.json().catch(() => ({ message: 'Item deletion successful.', status: response.status }));

        if (response.ok) { // 200 OK
            successMessage.value = data.message || 'Booking item deleted successfully.';
            await fetchItems();
            deleteModalInstance?.hide();
        } else {
            handleApiError(data, response.status, false);
        }
    } catch (e) {
        console.error('Failed to delete item:', e);
        errorMessage.value = 'Failed to delete item due to a network error.';
    } finally {
        saving.value = false;
        handleCancelDeletion(); // Close/reset delete modal state
    }
};


// --- MODAL AND UI HANDLERS ---

const resetModalData = () => {
    modalData.value = {
        name: '',
        description: '',
        price_per_hour: 0.00,
        available_quantity: 1,
        status: 'Available',
    };
    isEditMode.value = false;
    savingErrorMessage.value = '';
    validationErrors.value = {};
};

const openAddItemModal = () => {
    isEditMode.value = false;
    resetModalData();
    itemModalInstance?.show();
};

const openEditItemModal = (item: BookingItem) => {
    isEditMode.value = true;
    // Deep copy the item data for editing, ensuring all keys are present
    modalData.value = { ...item };
    itemModalInstance?.show();
};

const openDeleteConfirmation = (item: BookingItem) => {
    itemToDelete.value = item;
    deleteStep.value = 'confirm';
    deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final';
};

const handleCancelDeletion = () => {
    deleteModalInstance?.hide();
    itemToDelete.value = null;
    deleteStep.value = 'confirm';
};

// --- LIFECYCLE ---

onMounted(() => {
    // Initialize Bootstrap Modals
    if (itemModalRef.value) {
        itemModalInstance = new Modal(itemModalRef.value);
        // Ensure resetModalData runs when modal is closed
        itemModalRef.value.addEventListener('hidden.bs.modal', resetModalData);
    }
    if (deleteModalRef.value) {
        deleteModalInstance = new Modal(deleteModalRef.value);
        deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
    }
    
    fetchItems();
});
</script>

<style scoped>

/* --- Structure and Layout --- */
.booking-items-page.section {
    animation: fadeIn 0.3s ease;
    padding: 20px;
    margin-left: 260px; /* Standard sidebar width */
}

@media (max-width: 768px) {
    .booking-items-page.section {
        margin-left: 80px; /* Collapsed sidebar width */
    }
    .modal-dialog.modal-lg {
        max-width: 95% !important;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.section-title {
    color: #1e4449; /* Dark Teal color */
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
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    .input-group, .add-new-btn {
        width: 100% !important;
        max-width: 100% !important;
    }
}

/* --- Table Styles --- */
.table-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); 
}

.table thead {
    background: #f8f9fa;
}

.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 1px solid #dee2e6;
    padding: 12px 15px;
}

.table tbody td {
    padding: 12px 15px; 
    vertical-align: middle;
}

/* --- Button Styles --- */
.btn-success {
    background-color: #4BB66D;
    border-color: #4BB66D;
}
.btn-success:hover {
    background-color: #3f975b;
    border-color: #3f975b;
}
.btn-success.add-new-btn {
    padding: 10px 20px;
    border-radius: 8px; 
}
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem; 
}
.btn-outline-primary {
    --bs-btn-color: #0d6efd;
    --bs-btn-border-color: #0d6efd;
}
.btn-outline-danger {
    --bs-btn-color: #dc3545;
    --bs-btn-border-color: #dc3545;
}

/* --- Modal Styles --- */
.modal-content {
    border-radius: 12px;
}
.modal-header {
    border-bottom: 1px solid #dee2e6;
}
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