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

      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="error-message">
        {{ error }}
      </div>

      <!-- Appointments Table -->
      <div v-if="!loading && appointments.length > 0" class="table-container">
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
            <tr v-for="appointment in appointments" :key="appointment.id">
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
      <div v-if="!loading && appointments.length === 0" class="empty-state">
        <p class="empty-text">No appointments found</p>
      </div>
    </main>



    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Edit Appointment' : 'New Appointment' }}</h3>
          <button class="close-btn" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
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
</template>

<script>
import axios from 'axios';

export default {
  name: 'AppointmentsApp',
  data() {
    return {
      appointments: [],
      loading: false,
      error: null,
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
      }
    };
  },
  created() {
    this.fetchAppointments();
  },
  methods: {
    fetchAppointments() {
      this.loading = true;
      this.error = null;
      
      axios.get('http://127.0.0.1:8000/api/appointments?all=true')
        .then(response => {
          if (Array.isArray(response.data)) {
            this.appointments = response.data;
          } else if (response.data.data) {
            this.appointments = response.data.data;
          }
          console.log('Appointments loaded:', this.appointments);
        })
        .catch(error => {
          this.error = 'Failed to load appointments.';
          console.error('API Error:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    openCreateModal() {
      this.isEditMode = false;
      this.resetForm();
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
      this.showModal = true;
    },
    
    openDeleteModal(appointment) {
      this.appointmentToDelete = appointment;
      this.showDeleteModal = true;
    },
    
    closeModal() {
      this.showModal = false;
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
      this.error = null;
      
      axios.post('http://127.0.0.1:8000/api/appointments', this.formData)
        .then(response => {
          this.appointments.push(response.data);
          this.closeModal();
          console.log('Appointment created successfully');
        })
        .catch(error => {
          this.error = 'Failed to create appointment.';
          console.error('Create Error:', error.response || error);
        })
        .finally(() => {
          this.saving = false;
        });
    },
    
    updateAppointment() {
      this.saving = true;
      this.error = null;
      console.log(this.formData);
      axios.put(`http://127.0.0.1:8000/api/appointments/${this.formData.id}`, this.formData)
        .then(response => {
          const index = this.appointments.findIndex(a => a.id === this.formData.id);
          if (index !== -1) {
            this.appointments[index] = response.data;
          }
          this.closeModal();
          console.log('Appointment updated successfully');
        })
        .catch(error => {
          this.error = 'Failed to update appointment.';
          console.error('Update Error:', error.response || error);
        })
        .finally(() => {
          this.saving = false;
        });
    },
    
    confirmDelete() {
      if (!this.appointmentToDelete) return;
      
      this.deleting = true;
      this.error = null;
      
      axios.delete(`http://127.0.0.1:8000/api/appointments/${this.appointmentToDelete.id}`)
        .then(() => {
          this.appointments = this.appointments.filter(
            appointment => appointment.id !== this.appointmentToDelete.id
          );
          this.closeDeleteModal();
          console.log('Appointment deleted successfully');
        })
        .catch(error => {
          this.error = 'Failed to delete appointment.';
          console.error('Delete Error:', error.response || error);
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
</style>