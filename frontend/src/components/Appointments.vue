<template>
  <div class="app-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <h1 class="logo">Appointments Lite</h1>
        <button class="new-btn" @click="openCreateModal">+ New Appointment</button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main">
      <div class="page-header">
        <h2 class="page-title">Your Appointments</h2>
        <p class="page-subtitle">Manage and track all your appointments</p>
      </div>

      <!-- Filter + Sort toolbar -->
      <div class="toolbar">
        <input
          class="search-input"
          type="text"
          v-model="searchQuery"
          placeholder="Search by patient or doctor..."
          aria-label="Search by patient or doctor"
        />
        <div class="sort">
          <label class="sort-label" for="sortOrder">Sort by date:</label>
          <select id="sortOrder" class="sort-select" v-model="sortOrder" aria-label="Sort by date">
            <option value="desc">Newest first</option>
            <option value="asc">Oldest first</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Appointments Table -->
      <div v-if="!loading && visibleAppointments.length > 0" class="table-container">
        <table class="appointments-table">
          <thead>
            <tr>
              <th>Patient Name</th>
              <th>Doctor</th>
              <th>Date & Time</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="appointment in visibleAppointments" :key="appointment.id">
              <td>{{ appointment.patient_name }}</td>
              <td>{{ appointment.doctor }}</td>
              <td>{{ formatDateTime(appointment.datetime) }}</td>
              <td>
                <span :class="['status-badge', `status-${appointment.status.toLowerCase()}`]">
                  {{ appointment.status }}
                </span>
              </td>
              <td>
                <button 
                  @click="openEditModal(appointment)"
                  class="edit-btn"
                >
                  Edit
                </button>
                <button 
                  @click="openDeleteModal(appointment)"
                  class="delete-btn"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>


      <!-- Empty State -->
      <div v-if="!loading && visibleAppointments.length === 0" class="empty-state">
        <p class="empty-text">No appointments found</p>
      </div>
    </main>

    <!-- Load more -->
    <div v-if="!loading && appointments.length > 0 && hasMore" class="load-more">
      <button class="btn-primary load-more" @click="loadMore" :disabled="loadingMore">
        {{ loadingMore ? 'Loading...' : 'Load more' }}
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Edit Appointment' : 'New Appointment' }}</h3>
          <button class="close-btn" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <!-- Form errors -->
          <div v-if="modalError" class="modal-error">{{ modalError }}</div>

          <form @submit.prevent="submitForm">
            <div class="form-group">
              <label for="patient_name">Patient Name *</label>
              <input
                id="patient_name"
                v-model="formData.patient_name"
                type="text"
                required
                placeholder="Enter patient name"
              />
            </div>

            <div class="form-group">
              <label for="doctor">Doctor *</label>
              <select id="doctor" v-model="formData.doctor" required>
                <option value="">Select a doctor</option>
                <option value="Dr. Ionescu">Dr. Ionescu</option>
                <option value="Dr. Matei">Dr. Matei</option>
                <option value="Dr. Pop">Dr. Pop</option>
              </select>
            </div>

            <div class="form-group">
              <label for="datetime">Date & Time *</label>
              <input
                id="datetime"
                v-model="formData.datetime"
                type="datetime-local"
                required
              />
            </div>

            <div class="form-group">
              <label for="status">Status *</label>
              <select id="status" v-model="formData.status" required>
                <option value="">Select status</option>
                <option value="Scheduled">Scheduled</option>
                <option value="Done">Done</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeModal">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Saving...' : (isEditMode ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal modal-small">
        <div class="modal-header">
          <h3>Delete Appointment</h3>
          <button class="close-btn" @click="closeDeleteModal">&times;</button>
        </div>
        <div class="modal-body">
          <p class="delete-message">Are you sure you want to delete this appointment?</p>
          <div v-if="appointmentToDelete" class="delete-details">
            <p><strong>Patient:</strong> {{ appointmentToDelete.patient_name }}</p>
            <p><strong>Doctor:</strong> {{ appointmentToDelete.doctor }}</p>
            <p><strong>Date:</strong> {{ formatDateTime(appointmentToDelete.datetime) }}</p>
          </div>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
          <button class="btn-secondary" @click="closeDeleteModal">
            Cancel
          </button>
          <button class="btn-danger" @click="confirmDelete" :disabled="deleting">
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div
    v-if="toast.visible"
    :class="['toast', toast.type === 'success' ? 'toast-success' : 'toast-error']"
    role="status"
    aria-live="polite"
  >
    {{ toast.message }}
  </div>
</template>

<script>
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

export default {
  name: 'AppointmentsApp',
  data() {
    return {
      appointments: [],
      loading: false,
      error: null,       
      modalError: null,  
      showModal: false,
      showDeleteModal: false,
      isEditMode: false,
      saving: false,
      deleting: false,
      appointmentToDelete: null,
      formData: {
        id: null,
        patient_name: '',
        doctor: '',
        datetime: '',
        status: ''
      },
      // pagination state
      page: 1,
      perPage: 10,
      lastPage: 1,
      total: 0,
      loadingMore: false,
      searchQuery: '',
      debouncedQuery: '',
      sortOrder: 'desc', // 'asc' | 'desc'
      _searchTimer: null,

      // Toast state
      toast: {
        visible: false,
        message: '',
        type: 'success', // 'success' | 'error'
      },
    };
  },
  computed: {
    hasMore() {
      return this.page < this.lastPage;
    },
    // Client-side filtered + sorted list of appointments
    visibleAppointments() {
      const q = (this.debouncedQuery || '').trim().toLowerCase();
      let list = this.appointments.slice();

      if (q) {
        list = list.filter(a => {
          const patient = String(a.patient_name ?? '').toLowerCase();
          const doctor = String(a.doctor ?? '').toLowerCase();
          return patient.includes(q) || doctor.includes(q);
        });
      }

      list.sort((a, b) => {
        const da = new Date(a.datetime).getTime();
        const db = new Date(b.datetime).getTime();
        if (isNaN(da) || isNaN(db)) return 0;
        return this.sortOrder === 'asc' ? da - db : db - da;
      });

      return list;
    }
  },
  created() {
    this.page = 1;
    this.fetchAppointments({ append: false });
  },
  watch: {
    // Debounce search input
    searchQuery(val) {
      if (this._searchTimer) clearTimeout(this._searchTimer);
      this._searchTimer = setTimeout(() => {
        this.debouncedQuery = val;
      }, 300);
    }
  },
  methods: {
    fetchAppointments({ append = false } = {}) {
      if (!append) this.loading = true;
      this.error = null;

      axios
        .get(`${API_URL}/appointments`, { params: { page: this.page, per_page: this.perPage } })
        .then(response => {
          const payload = response.data;

          if (Array.isArray(payload)) {
            // Fallback if API was called with all=true by mistake
            this.appointments = append ? [...this.appointments, ...payload] : payload;
            this.lastPage = 1;
            this.total = payload.length;
          } else {
            const items = payload.data || [];
            if (append) {
              this.appointments.push(...items);
            } else {
              this.appointments = items;
            }
            this.page = payload.current_page ?? this.page;
            this.lastPage = payload.last_page ?? 1;
            this.perPage = payload.per_page ?? this.perPage;
            this.total = payload.total ?? this.total;
          }
        })
        .catch(error => {
          // keep fetch error internal (not shown on page)
          this.error = 'Failed to load appointments.';
          console.error('API Error:', error);
        })
        .finally(() => {
          this.loading = false;
          this.loadingMore = false;
        });
    },

    loadMore() {
      if (!this.hasMore || this.loadingMore) return;
      this.loadingMore = true;
      this.page += 1;
      this.fetchAppointments({ append: true });
    },

    openCreateModal() {
      this.isEditMode = false;
      this.resetForm();
      this.modalError = null;
      this.showModal = true;
    },
    
    openEditModal(appointment) {
      this.isEditMode = true;
      this.formData = {
        id: appointment.id,
        patient_name: appointment.patient_name,
        doctor: appointment.doctor,
        datetime: this.formatDateTimeForInput(appointment.datetime),
        status: appointment.status
      };
      this.modalError = null;
      this.showModal = true;
    },
    
    openDeleteModal(appointment) {
      this.appointmentToDelete = appointment;
      this.showDeleteModal = true;
    },
    
    closeModal() {
      this.showModal = false;
      this.modalError = null;
      this.resetForm();
    },
    
    closeDeleteModal() {
      this.showDeleteModal = false;
      this.appointmentToDelete = null;
    },
    
    resetForm() {
      this.formData = {
        id: null,
        patient_name: '',
        doctor: '',
        datetime: '',
        status: ''
      };
    },
    
    submitForm() {
      if (this.isEditMode) {
        this.updateAppointment();
      } else {
        this.createAppointment();
      }
    },
    
    createAppointment() {
      this.saving = true;
      this.modalError = null;

      if (this.formData.patient_name.length < 2) {
        this.modalError = 'Patient name must be at least 2 characters long.';
        this.saving = false;
        return;
      }

      axios.post(`${API_URL}/appointments`, this.formData)
        .then(response => {
          this.appointments.unshift(response.data);
          this.closeModal();
        })
        .catch(error => {
          this.modalError = error.response?.data?.message || 'Failed to create appointment.';
          console.error('Create Error:', error.response || error);
        })
        .finally(() => {
          this.saving = false;
        });
    },
    
    updateAppointment() {
      this.saving = true;
      this.modalError = null;

      if (this.formData.patient_name.length < 2) {
        this.modalError = 'Patient name must be at least 2 characters long.';
        this.saving = false;
        return;
      }

      axios.put(`${API_URL}/appointments/${this.formData.id}`, this.formData)
        .then(response => {
          const index = this.appointments.findIndex(a => a.id === this.formData.id);
          if (index !== -1) this.appointments[index] = response.data;
          this.closeModal();
        })
        .catch(error => {
          this.modalError = error.response?.data?.message || 'Failed to update appointment.';
          console.error('Update Error:', error.response || error);
        })
        .finally(() => {
          this.saving = false;
        });
    },
    
    notify(message, type = 'success', timeout = 2500) {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.visible = true;
      clearTimeout(this._toastTimer);
      this._toastTimer = setTimeout(() => {
        this.toast.visible = false;
      }, timeout);
    },

    confirmDelete() {
      if (!this.appointmentToDelete) return;

      this.deleting = true;

      axios.delete(`${API_URL}/appointments/${this.appointmentToDelete.id}`)
        .then(() => {
          this.appointments = this.appointments.filter(a => a.id !== this.appointmentToDelete.id);
          this.closeDeleteModal();
          this.notify('Appointment deleted successfully.', 'success');
        })
        .catch(error => {
          console.error('Delete Error:', error.response || error);
          this.notify(error.response?.data?.message || 'Failed to delete appointment.', 'error');
        })
        .finally(() => {
          this.deleting = false;
        });
    },
    
    formatDateTime(datetime) {
      const date = new Date(datetime);
      return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    formatDateTimeForInput(datetime) {
      const date = new Date(datetime);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
  }
};
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.app-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.header {
  background: linear-gradient(135deg, #f48fb1 0%, #f06292 100%);
  padding: 1.5rem 2rem;
  box-shadow: 0 4px 12px rgba(136, 14, 79, 0.15);
}

.header-content {
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.logo {
  font-size: 1.75rem;
  font-weight: 300;
  color: white;
  letter-spacing: 1px;
  margin: 0;
}

.new-btn {
  padding: 0.65rem 1.5rem;
  background: white;
  color: #e91e63;
  border: none;
  border-radius: 25px;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.new-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
}

.new-btn:active {
  transform: translateY(0);
}

/* Main Content */
.main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 3rem 2rem;
}

.page-header {
  text-align: center;
  margin-bottom: 3rem;
}

.page-title {
  font-size: 2.5rem;
  color: #880e4f;
  font-weight: 300;
  letter-spacing: 1px;
  margin: 0 0 0.5rem 0;
}

.page-subtitle {
  font-size: 1.1rem;
  color: #ad1457;
  font-weight: 300;
  margin: 0;
}

.sort-label {
    font-size: 0.95rem;
    color: #880e4f;
    margin-right: 0.5rem;
}

/* Toolbar */
.toolbar {
  display: flex;
  gap: 1rem;
  align-items: center;
  justify-content: space-between;
  margin: 1rem 0 1.5rem;
  flex-wrap: wrap;
}

.search-input {
  flex: 1 1 320px;
  min-width: 220px;
  padding: 0.75rem 1rem;
  border: 2px solid #f8bbd0;
  border-radius: 12px;
  font-size: 1rem;
  outline: none;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  border-color: #f06292;
}

.sort {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sort-select {
  padding: 0.6rem 0.9rem;
  border: 2px solid #f8bbd0;
  border-radius: 12px;
  background: white;
  font-size: 0.95rem;
  outline: none;
}

.sort-select:focus {
  border-color: #f06292;
}

/* Loading */
.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem 0;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 3px solid #fce4ec;
  border-top: 3px solid #e91e63;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error */
.error-message {
  max-width: 600px;
  margin: 0 auto 2rem;
  padding: 1rem 1.5rem;
  background: white;
  color: #c2185b;
  border-left: 4px solid #c2185b;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(136, 14, 79, 0.1);
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(136, 14, 79, 0.08);
}

/* Table */
.appointments-table {
  width: 100%;
  border-collapse: collapse;
}

.appointments-table thead {
  background: linear-gradient(135deg, #f48fb1 0%, #f06292 100%);
}

.appointments-table th {
  padding: 1.25rem 1.5rem;
  text-align: left;
  font-weight: 500;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: white;
}

.appointments-table tbody tr {
  border-bottom: 1px solid #fce4ec;
  transition: background-color 0.2s ease;
}

.appointments-table tbody tr:hover {
  background-color: #fce4ec;
}

.appointments-table tbody tr:last-child {
  border-bottom: none;
}

.appointments-table td {
  padding: 1.25rem 1.5rem;
  color: #4a4a4a;
  font-size: 0.95rem;
}

/* Status Badge */
.status-badge {
  display: inline-block;
  padding: 0.35rem 0.85rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: capitalize;
}

.status-badge.status-scheduled {
  background: #e1bee7;
  color: #6a1b9a;
}

.status-badge.status-done {
  background: #c8e6c9;
  color: #2e7d32;
}

.status-badge.status-cancelled {
  background: #ffccbc;
  color: #d84315;
}

/* Action Buttons */
.edit-btn,
.delete-btn {
  padding: 0.4rem 1rem;
  border: 1.5px solid;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-right: 0.5rem;
}

.edit-btn {
  background: transparent;
  color: #7b1fa2;
  border-color: #7b1fa2;
}

.edit-btn:hover {
  background: #7b1fa2;
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(123, 31, 162, 0.3);
}

.delete-btn {
  background: transparent;
  color: #e91e63;
  border-color: #e91e63;
}

.delete-btn:hover {
  background: #e91e63;
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
}

.edit-btn:active,
.delete-btn:active {
  transform: translateY(0);
}

/* Empty State */
.empty-state {
  background: white;
  border-radius: 16px;
  padding: 3rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(136, 14, 79, 0.08);
}

.empty-text {
  color: #880e4f;
  font-size: 1.1rem;
  font-weight: 300;
  margin: 0;
}

/* Footer */
.footer {
  background: linear-gradient(135deg, #f48fb1 0%, #f06292 100%);
  padding: 2rem;
  text-align: center;
  margin-top: 4rem;
}

.footer-text {
  color: white;
  font-size: 0.9rem;
  font-weight: 300;
  margin: 0;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

.modal-small {
  max-width: 400px;
}

@keyframes slideUp {
  from {
    transform: translateY(50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #fce4ec;
  background: linear-gradient(135deg, #f48fb1 0%, #f06292 100%);
  border-radius: 16px 16px 0 0;
}

.modal-header h3 {
  color: white;
  font-weight: 400;
  font-size: 1.25rem;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  color: white;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  width: 30px;
  height: 30px;
  transition: transform 0.2s;
}

.close-btn:hover {
  transform: scale(1.1);
}

.modal-body {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #880e4f;
  font-weight: 500;
  font-size: 0.9rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #fce4ec;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #f06292;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #fce4ec;
}

.btn-primary,
.btn-secondary,
.btn-danger {
  padding: 0.65rem 1.5rem;
  border: none;
  border-radius: 25px;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #f48fb1 0%, #f06292 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(233, 30, 99, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(233, 30, 99, 0.4);
}

.btn-secondary {
  background: #f5f5f5;
  color: #666;
}

.btn-secondary:hover {
  background: #e0e0e0;
}

.btn-danger {
  background: #e91e63;
  color: white;
  box-shadow: 0 2px 8px rgba(233, 30, 99, 0.3);
}

.btn-danger:hover:not(:disabled) {
  background: #c2185b;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(233, 30, 99, 0.4);
}

.btn-primary:disabled,
.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.delete-message {
  font-size: 1rem;
  color: #666;
  margin-bottom: 1.5rem;
}

.delete-details {
  background: #fce4ec;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.delete-details p {
  margin: 0.5rem 0;
  color: #4a4a4a;
  font-size: 0.9rem;
}

.warning-text {
  color: #e91e63;
  font-weight: 500;
  font-size: 0.9rem;
}

/* Modal error styling */
.modal-error {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  background: #fde2e7;
  color: #b00020;
  border: 1px solid #f8bbd0;
  border-radius: 8px;
}

/* Toast */
.toast {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 1100;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  color: #fff;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  animation: toast-in 200ms ease-out;
  max-width: 80vw;
}

.toast-success {
  background: #2e7d32;
}

.toast-error {
  background: #c62828;
}

@keyframes toast-in {
  from { transform: translateY(10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .new-btn {
    width: 100%;
  }

  .main {
    padding: 2rem 1rem;
  }

  .page-title {
    font-size: 2rem;
  }

  .page-subtitle {
    font-size: 1rem;
  }

  .table-container {
    overflow-x: auto;
  }

  .appointments-table {
    font-size: 0.85rem;
  }

  .appointments-table th,
  .appointments-table td {
    padding: 1rem;
  }

  .modal {
    width: 95%;
  }

  .modal-body {
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .logo {
    font-size: 1.5rem;
  }

  .page-title {
    font-size: 1.75rem;
  }

  .appointments-table th,
  .appointments-table td {
    padding: 0.75rem;
  }

  .edit-btn,
  .delete-btn {
    padding: 0.35rem 0.85rem;
    font-size: 0.8rem;
    margin-right: 0.25rem;
  }
}

.load-more {
  text-align: center;
  margin-bottom: 3rem;
}
</style>