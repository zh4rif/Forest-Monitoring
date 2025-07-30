<!-- resources/js/components/RegisterForm.vue -->
<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Create Account</h2>
        <button @click="$emit('close')" class="close-button">&times;</button>
      </div>

      <form @submit.prevent="handleRegister" class="register-form">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input
            id="name"
            type="text"
            v-model="form.name"
            required
            class="form-input"
            :class="{ 'error': errors.name }"
            placeholder="Enter your full name"
          >
          <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input
            id="email"
            type="email"
            v-model="form.email"
            required
            class="form-input"
            :class="{ 'error': errors.email }"
            placeholder="Enter your email"
          >
          <span v-if="errors.email" class="error-text">{{ errors.email[0] }}</span>
        </div>

        <div class="form-group">
          <label for="role">Role</label>
          <select
            id="role"
            v-model="form.role"
            required
            class="form-input"
            :class="{ 'error': errors.role }"
          >
            <option value="">Select your role</option>
            <option value="MAPO">MAPO Officer</option>
            <option value="FGV">FGV Staff</option>
            <option value="PLANTATION_OWNER">Plantation Owner</option>
          </select>
          <span v-if="errors.role" class="error-text">{{ errors.role[0] }}</span>
        </div>

        <div class="form-group">
          <label for="organization">Organization</label>
          <input
            id="organization"
            type="text"
            v-model="form.organization"
            class="form-input"
            :class="{ 'error': errors.organization }"
            placeholder="Your organization name"
          >
          <span v-if="errors.organization" class="error-text">{{ errors.organization[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            type="password"
            v-model="form.password"
            required
            class="form-input"
            :class="{ 'error': errors.password }"
            placeholder="Choose a strong password"
          >
          <span v-if="errors.password" class="error-text">{{ errors.password[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirm Password</label>
          <input
            id="password_confirmation"
            type="password"
            v-model="form.password_confirmation"
            required
            class="form-input"
            placeholder="Confirm your password"
          >
        </div>

        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? 'Creating Account...' : 'Create Account' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useAuth } from '../composables/useAuth'

const emit = defineEmits(['close', 'registered'])

const { register } = useAuth()

const loading = ref(false)
const errors = ref({})

const form = reactive({
  name: '',
  email: '',
  role: '',
  organization: '',
  password: '',
  password_confirmation: ''
})

const handleRegister = async () => {
  loading.value = true
  errors.value = {}

  try {
    await register(form)
    emit('registered')
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = {
        email: [error.response?.data?.message || 'Registration failed. Please try again.']
      }
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 500px;
  max-width: 90vw;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 30px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  color: #374151;
}

.close-button {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background 0.2s;
}

.close-button:hover {
  background: #f3f4f6;
}

.register-form {
  padding: 30px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.error {
  border-color: #ef4444;
}

.error-text {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
  display: block;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 30px;
}

.btn-secondary {
  flex: 1;
  background: #6b7280;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-primary {
  flex: 1;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}
</style>
