<template>
    <Navbar/>
    <MasterAdminSidebar/>
    <div class="template-page section">
        <h2 class="section-title">Resource Templates</h2>
        
        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ successMessage }}
            <button type="button" class="btn-close" @click="successMessage = ''"></button>
        </div>
        <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ errorMessage }}
            <button type="button" class="btn-close" @click="errorMessage = ''"></button>
        </div>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input v-model="searchQuery" type="text" class="form-control" placeholder="Search Templates...">
                </div>
            </div>
            <button class="btn btn-success" @click="openAddModal">
                <i class="bi bi-plus-circle me-2"></i> Add New Template
            </button>
        </div>
        
        <div class="table-responsive card shadow-sm p-3">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Template Name</th>
                        <th>Category</th>
                        <th>Fields</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="5" class="text-center py-4">Loading...</td></tr>
                    <tr v-else-if="filteredTemplates.length === 0">
                        <td colspan="5" class="text-center py-4 text-muted">
                            No templates found. {{ searchQuery ? 'Try a different search.' : 'Click "Add New Template" to create one.' }}
                        </td>
                    </tr>
                    <tr v-for="template in filteredTemplates" :key="template.id">
                        <td><strong>{{ template.template_name }}</strong></td>
                        <td>{{ getCategoryName(template.category_id) }}</td>
                        <td><span class="badge bg-secondary">{{ template.fields?.length || 0 }} Fields</span></td>
                        <td>
                            <span :class="['badge', template.status === 'Active' ? 'bg-success' : 'bg-danger']">
                                {{ template.status }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-2" @click="openEditModal(template)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="openDeleteConfirmation(template)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="templateModalLabel">{{ isEditMode ? 'Edit Template' : 'Add New Template' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveTemplate">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="templateName" class="form-label">Template Name</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="templateName"
                                        placeholder="Enter template name (e.g., Category Specification)"
                                        v-model="formData.template_name"
                                        required
                                        :disabled="isSaving"
                                    >
                                    <small class="text-danger" v-if="validationErrors.template_name">{{ validationErrors.template_name[0] }}</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select v-model="formData.category_id" class="form-select" required :disabled="isSaving">
                                        <option value="" disabled>Select Category</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <small class="text-danger" v-if="validationErrors.category_id">{{ validationErrors.category_id[0] }}</small>
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea 
                                        class="form-control" 
                                        id="description"
                                        rows="2" 
                                        placeholder="Optional description"
                                        v-model="formData.description"
                                        :disabled="isSaving"
                                    ></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status</label>
                                    <select v-model="formData.status" class="form-select" required :disabled="isSaving">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                    <small class="text-danger" v-if="validationErrors.status">{{ validationErrors.status[0] }}</small>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2"></i>Form Fields</h6>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addField('input')" :disabled="isSaving">+ Text Input</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addField('checkbox')" :disabled="isSaving">+ Checkbox</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addField('dropdown')" :disabled="isSaving">+ Dropdown</button>
                                </div>
                            </div>

                            <div class="field-container p-3 bg-light rounded border">
                                <div v-if="formData.fields.length === 0" class="text-center text-muted py-4">
                                    No fields added yet. Click a button above to add fields.
                                </div>
                                
                                <div v-for="(field, index) in formData.fields" :key="index" class="field-card mb-3 p-3 bg-white shadow-sm border-start border-4" 
                                    :class="field.type === 'dropdown' ? 'border-primary' : (field.type === 'checkbox' ? 'border-success' : 'border-info')">
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge rounded-pill text-uppercase" :class="field.type === 'dropdown' ? 'bg-primary' : (field.type === 'checkbox' ? 'bg-success' : 'bg-info')">
                                            {{ field.type === 'input' ? 'Text' : field.type }}
                                        </span>
                                        <button type="button" class="btn-close" @click="removeField(index)" :disabled="isSaving"></button>
                                    </div>

                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-12">
                                            <label class="small text-muted mb-1">Field Label / Name</label>
                                            <input v-model="field.field_name" type="text" class="form-control" placeholder="e.g. Enter text..." required :disabled="isSaving">
                                        </div>
                                    </div>

                                    <div v-if="field.type === 'dropdown'" class="mt-3 p-2 bg-light rounded">
                                        <label class="small fw-bold mb-2">Dropdown Options:</label>
                                        <div v-for="(opt, optIdx) in field.options" :key="optIdx" class="input-group input-group-sm mb-1">
                                            <input v-model="field.options[optIdx]" type="text" class="form-control" placeholder="Option Name" :disabled="isSaving">
                                            <button type="button" class="btn btn-outline-danger" @click="removeOption(index, optIdx)" :disabled="isSaving"><i class="bi bi-dash"></i></button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-decoration-none" @click="addOption(index)" :disabled="isSaving">+ Add Option</button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal" :disabled="isSaving">Cancel</button>
                                <button type="submit" class="btn btn-success" :disabled="isSaving">
                                    <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    {{ isSaving ? (isEditMode ? 'Updating...' : 'Saving...') : (isEditMode ? 'Update' : 'Save') }}
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
                            <p class="mb-0">Are you sure you want to delete the template <strong>{{ templateToDelete?.template_name }}</strong>?</p>
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
                            <p class="mb-0">This action will permanently delete the template <strong>{{ templateToDelete?.template_name }}</strong>. Are you sure?</p>
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
import { ref, onMounted, computed } from 'vue';
import { Modal } from 'bootstrap';
import Navbar from '../../components/Navbar.vue';
import MasterAdminSidebar from '../../components/Sidebar/MasterAdminSidebar.vue';

// Types
interface Field {
    id?: number;
    field_name: string;
    field_type: string; // text, number, textarea, checkbox, image, dropdown (Backend)
    type: 'input' | 'checkbox' | 'dropdown'; // UI Logic
    options: string[];
    metadata?: any;
}

interface Template {
    id: number;
    template_name: string;
    category_id: number;
    description: string;
    status: 'Active' | 'Inactive';
    created_by: number;
    fields: any[];
    category?: { id: number; name: string };
}

interface Category {
    id: number;
    name: string;
    description?: string;
    status?: string;
}

interface ValidationErrors {
    [key: string]: string[];
}

// State
const templates = ref<Template[]>([]);
const categories = ref<Category[]>([]);
const searchQuery = ref('');
const isLoading = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const validationErrors = ref<ValidationErrors>({});
const isEditMode = ref(false);
const templateToDelete = ref<Template | null>(null);
const deleteStep = ref<'confirm' | 'final'>('confirm');
const deletedFieldIds = ref<number[]>([]);

// Default form data
const defaultFormData = {
    id: null as number | null,
    template_name: '',
    category_id: '' as number | '',
    description: '',
    status: 'Active' as 'Active' | 'Inactive',
    created_by: 1,
    fields: [] as Field[]
};

const formData = ref({ ...defaultFormData });

// Bootstrap Modal References and Instances
const templateModalRef = ref<HTMLElement | null>(null);
const deleteModalRef = ref<HTMLElement | null>(null);
let templateModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;

// API Base URL
const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api');
const RESOURCE_TEMPLATES_API_URL = `${API_BASE_URL}/resource-templates`;

// Helper to get auth token
const getAuthToken = () => {
    return localStorage.getItem('authToken') || 
           localStorage.getItem('auth_token') || 
           localStorage.getItem('token') || 
           localStorage.getItem('access_token') ||
           sessionStorage.getItem('auth_token');
};

// Helper to get category name from ID
const getCategoryName = (categoryId: number) => {
    const category = categories.value.find(cat => cat.id === categoryId);
    return category ? category.name : 'Uncategorized';
};

// Helper to convert UI type to backend field_type
const getBackendFieldType = (uiType: string): string => {
    switch(uiType) {
        case 'input': return 'text';
        case 'checkbox': return 'checkbox';
        case 'dropdown': return 'dropdown';
        default: return 'text';
    }
};

// Helper to convert backend field_type to UI type
const getUiType = (backendType: string): 'input' | 'checkbox' | 'dropdown' => {
    switch(backendType) {
        case 'text': return 'input';
        case 'checkbox': return 'checkbox';
        case 'dropdown': return 'dropdown';
        default: return 'input';
    }
};

// API Error Handler
const handleApiError = (data: any, status: number) => {
    validationErrors.value = {};
    if (status === 422 && data.errors) {
        validationErrors.value = data.errors;
        errorMessage.value = "Validation failed. Check the form fields.";
    } else if (status === 503) {
        errorMessage.value = data.message || "Failed to connect to the resource service.";
    } else {
        errorMessage.value = data.message || `An error occurred (Status: ${status}).`;
    }
};

// CRUD Operations

// GET - Fetch all templates and categories
const fetchData = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    try {
        const token = getAuthToken();
        const headers: HeadersInit = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const [templatesResponse, categoriesResponse] = await Promise.all([
            fetch(`${API_BASE_URL}/resource-templates`, { headers }),
            fetch(`${API_BASE_URL}/categories`, { headers })
        ]);

        if (!templatesResponse.ok) {
            const errorText = await templatesResponse.text();
            throw new Error(`Failed to fetch templates: ${templatesResponse.status} ${templatesResponse.statusText}`);
        }

        if (!categoriesResponse.ok) {
            const errorText = await categoriesResponse.text();
            throw new Error(`Failed to fetch categories: ${categoriesResponse.status} ${categoriesResponse.statusText}`);
        }

        const templatesData = await templatesResponse.json();
        const categoriesData = await categoriesResponse.json();
        
        templates.value = Array.isArray(templatesData) ? templatesData : [];
        
        if (Array.isArray(categoriesData)) {
            categories.value = categoriesData;
        } else if (categoriesData.categories && Array.isArray(categoriesData.categories)) {
            categories.value = categoriesData.categories;
        } else if (categoriesData.data && Array.isArray(categoriesData.data)) {
            categories.value = categoriesData.data;
        } else {
            categories.value = [];
        }

    } catch (e: any) { 
        console.error('Fetch data error:', e);
        errorMessage.value = e.message || "Failed to load data. Please check your connection and try again."; 
    } finally { 
        isLoading.value = false; 
    }
};

// Open modals
const openAddModal = () => {
    isEditMode.value = false;
    formData.value = { ...defaultFormData };
    deletedFieldIds.value = [];
    validationErrors.value = {};
    templateModalInstance?.show();
};

const openEditModal = (template: Template) => {
    isEditMode.value = true;
    validationErrors.value = {};
    deletedFieldIds.value = [];
    
    formData.value = {
        id: template.id,
        template_name: template.template_name,
        category_id: template.category_id,
        description: template.description || '',
        status: template.status,
        created_by: template.created_by,
        fields: template.fields.map(f => {
            const uiType = getUiType(f.field_type);
            
            let options: string[] = [];
            if (uiType === 'dropdown' && f.metadata) {
                try {
                    const meta = typeof f.metadata === 'string' ? JSON.parse(f.metadata) : f.metadata;
                    options = meta.options || [];
                } catch (e) {
                    console.error('Error parsing metadata:', e);
                    options = [];
                }
            }
            
            return {
                id: f.id,
                field_name: f.field_name,
                field_type: f.field_type,
                type: uiType,
                options: uiType === 'dropdown' ? (options.length ? [...options] : ['Option 1', 'Option 2']) : []
            };
        })
    };
    
    templateModalInstance?.show();
};

// Two-step delete modal handlers
const openDeleteConfirmation = (template: Template) => {
    templateToDelete.value = template;
    deleteStep.value = 'confirm'; 
    deleteModalInstance?.show();
};

const handleFirstConfirmation = () => {
    deleteStep.value = 'final'; 
};

const handleCancelDeletion = () => {
    deleteModalInstance?.hide();
    templateToDelete.value = null;
    deleteStep.value = 'confirm'; 
};

// Field management
const addField = (uiType: 'input' | 'checkbox' | 'dropdown') => {
    const backendType = getBackendFieldType(uiType);
    formData.value.fields.push({
        field_name: '',
        field_type: backendType,
        type: uiType,
        options: uiType === 'dropdown' ? ['Option 1', 'Option 2'] : []
    });
};

const removeField = (index: number) => {
    const field = formData.value.fields[index];
    if (field.id) deletedFieldIds.value.push(field.id);
    formData.value.fields.splice(index, 1);
};

const addOption = (idx: number) => {
    if (formData.value.fields[idx].options) {
        formData.value.fields[idx].options.push(`Option ${formData.value.fields[idx].options.length + 1}`);
    }
};

const removeOption = (fIdx: number, oIdx: number) => {
    if (formData.value.fields[fIdx].options) {
        formData.value.fields[fIdx].options.splice(oIdx, 1);
    }
};

// POST/PUT - Save template
const saveTemplate = async () => {
    isSaving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    validationErrors.value = {};
    
    try {
        if (!formData.value.template_name.trim()) {
            errorMessage.value = "Template name is required.";
            isSaving.value = false;
            return;
        }

        if (!formData.value.category_id) {
            errorMessage.value = "Please select a category.";
            isSaving.value = false;
            return;
        }

        for (const field of formData.value.fields) {
            if (!field.field_name.trim()) {
                errorMessage.value = "All fields must have a name.";
                isSaving.value = false;
                return;
            }
        }

        const token = getAuthToken();
        if (!token) {
            errorMessage.value = "Authentication token not found. Please login again.";
            isSaving.value = false;
            return;
        }

        const headers: HeadersInit = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
        };

        const payloadFields = formData.value.fields.map((f, i) => {
            const obj: any = {
                field_name: f.field_name.trim(),
                field_type: f.field_type,
                is_required: 0, // Always set to 0 (false) since we removed the checkbox
                order_index: i
            };
            
            if (f.id) obj.id = f.id;
            
            if (f.type === 'dropdown') {
                const validOptions = (f.options || []).filter(o => o.trim() !== '');
                obj.metadata = JSON.stringify({ 
                    options: validOptions.length > 0 ? validOptions : ['Default Option'] 
                });
            }
            
            return obj;
        });

        const payload: any = {
            template_name: formData.value.template_name.trim(),
            category_id: formData.value.category_id,
            description: formData.value.description ? formData.value.description.trim() : '',
            status: formData.value.status,
            created_by: formData.value.created_by,
            fields: payloadFields
        };

        if (isEditMode.value && deletedFieldIds.value.length > 0) {
            payload.delete_fields = deletedFieldIds.value;
        }

        const url = isEditMode.value 
            ? `${RESOURCE_TEMPLATES_API_URL}/${formData.value.id}`
            : RESOURCE_TEMPLATES_API_URL;
            
        const method = isEditMode.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers,
            body: JSON.stringify(payload)
        });

        let responseData;
        const responseText = await response.text();
        
        try {
            responseData = JSON.parse(responseText);
        } catch (jsonError) {
            if (response.ok && responseText.trim() === '') {
                responseData = { message: 'Operation successful' };
            } else {
                throw new Error(`Invalid server response: ${response.status} ${response.statusText}`);
            }
        }

        if (response.ok) {
            successMessage.value = isEditMode.value 
                ? "Template updated successfully!" 
                : "Template created successfully!";
            
            templateModalInstance?.hide();
            await fetchData();
        } else {
            handleApiError(responseData, response.status);
        }
    } catch (e: any) { 
        console.error('Save template error:', e);
        errorMessage.value = e.message || "An unexpected error occurred. Please try again."; 
    } finally { 
        isSaving.value = false; 
    }
};

// DELETE - Confirm and delete template
const handleDelete = async () => {
    if (deleteStep.value !== 'final' || !templateToDelete.value) return; 
    
    isDeleting.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    const token = getAuthToken();
    const templateId = templateToDelete.value.id;

    try {
        const response = await fetch(`${RESOURCE_TEMPLATES_API_URL}/${templateId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
        });
        
        let responseData;
        try {
            responseData = await response.json();
        } catch (e) {
            responseData = { message: 'Request successful but no content received.', status: response.status };
        }

        if (response.ok) {
            successMessage.value = responseData.message || 'Template deleted successfully.';
            await fetchData();
            deleteModalInstance?.hide();
        } else {
            handleApiError(responseData, response.status);
        }
    } catch (e: any) {
        console.error('Failed to delete template:', e);
        errorMessage.value = 'Failed to delete template due to a network error.';
    } finally {
        isDeleting.value = false;
        if (successMessage.value || errorMessage.value) {
            templateToDelete.value = null;
            deleteStep.value = 'confirm'; 
        }
    }
};

// Initialize
onMounted(() => {
    templateModalRef.value = document.getElementById('templateModal') as HTMLElement;
    deleteModalRef.value = document.getElementById('deleteConfirmationModal') as HTMLElement;
    
    if (templateModalRef.value) {
        templateModalInstance = new Modal(templateModalRef.value);
        templateModalRef.value.addEventListener('hidden.bs.modal', () => {
            validationErrors.value = {};
        });
    }
    
    if (deleteModalRef.value) {
        deleteModalInstance = new Modal(deleteModalRef.value);
        deleteModalRef.value.addEventListener('hidden.bs.modal', handleCancelDeletion);
    }
    
    fetchData();
});

// Computed
const filteredTemplates = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return templates.value;
    
    return templates.value.filter(t => 
        t.template_name.toLowerCase().includes(query) ||
        getCategoryName(t.category_id).toLowerCase().includes(query)
    );
});
</script>

<style scoped>
/* --- General Section & Sidebar Layout --- */
.template-page {
    animation: fadeIn 0.3s ease;
    padding: 20px; 
    margin-left: 260px;
    background: #f8f9fa;
    min-height: 100vh;
}

@media (max-width: 768px) {
    .template-page {
        margin-left: 80px;
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

/* --- Page Header --- */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px; 
    gap: 20px;
}

.search-box .input-group {
    width: 300px;
}

.search-box .input-group-text {
    background-color: #fff;
    border-right: none;
}

.search-box .form-control {
    border-left: none;
}

.btn-success { 
    background-color: #4BB66D; 
    border-color: #4BB66D; 
}

.btn-success:hover { 
    background-color: #3f975b; 
    border-color: #3f975b; 
}

/* --- Table Card --- */
.table-responsive.card {
    background: white;
    border-radius: 8px;
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

.badge {
    padding: 0.35em 0.65em;
    font-weight: 500;
}

.btn-outline-primary { 
    --bs-btn-color: #0d6efd; 
    --bs-btn-border-color: #0d6efd; 
}

.btn-outline-danger { 
    --bs-btn-color: #dc3545; 
    --bs-btn-border-color: #dc3545; 
}

.btn-group-sm .btn { 
    padding: 0.25rem 0.5rem; 
}

/* --- Modal Styling --- */
.modal-dialog.modal-lg { 
    max-width: 900px !important; 
}

.modal-header.bg-light {
    background-color: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6;
}

.form-select {
    cursor: pointer;
    border-color: #ced4da;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-select:focus {
    border-color: #4BB66D;
    box-shadow: 0 0 0 0.25rem rgba(75, 182, 109, 0.25);
}

.form-select option {
    padding: 8px;
}

/* Field container styling */
.field-container {
    max-height: 500px;
    overflow-y: auto;
    border-radius: 8px;
}

.field-card {
    border-radius: 8px;
    transition: all 0.2s;
    background: white;
}

.field-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

.field-card.border-info {
    border-left-color: #0dcaf0 !important;
}

.field-card.border-success {
    border-left-color: #198754 !important;
}

.field-card.border-primary {
    border-left-color: #0d6efd !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
}

.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-primary {
    background-color: #0d6efd !important;
}

.field-card .bg-light {
    background-color: #f8f9fa !important;
}

.btn-primary:disabled,
.btn-danger:disabled,
.btn-warning:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.2em;
}

.text-danger {
    color: #dc3545 !important;
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

/* --- Responsive Design --- */
@media (max-width: 768px) {
    .page-header { 
        flex-direction: column; 
        align-items: stretch; 
    }
    
    .search-box .input-group {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .btn-success {
        width: 100%;
    }
    
    .modal-dialog.modal-lg { 
        max-width: 95% !important; 
    }
    
    .field-card .row {
        flex-direction: column;
    }
    
    .field-card .col-md-12 {
        width: 100%;
        margin-bottom: 10px;
    }
}

.text-center.py-4 {
    color: #6c757d;
    font-style: italic;
}

.text-muted {
    color: #6c757d !important;
}

.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-control:focus,
.form-select:focus {
    border-color: #4BB66D;
    box-shadow: 0 0 0 0.25rem rgba(75, 182, 109, 0.25);
}

.input-group.input-group-sm {
    margin-bottom: 5px;
}

.input-group.input-group-sm .form-control {
    border-radius: 4px;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

.modal-header.bg-danger.text-white {
    background-color: #dc3545 !important;
}

.modal-body .row.g-3 {
    margin-bottom: 1rem;
}

@media (min-width: 769px) {
    .template-page {
        width: calc(100% - 260px);
        margin-left: 260px;
    }
}

@media (max-width: 576px) {
    .template-page {
        padding: 15px;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .btn-group .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
}
</style>