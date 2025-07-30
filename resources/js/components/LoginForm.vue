<!-- resources/js/components/LoginForm.vue -->
<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <img src="https://images.squarespace-cdn.com/content/v1/604db3a6dad32a12b2415387/1636475927545-PK57PVLIB7AEKX1AJQJ8/Logo_MSPO_2020.png" alt="MSPO Logo" class="auth-logo">
        <h1 class="auth-title">MSPO Reforestation Monitor</h1>
        <p class="auth-subtitle">Login to access the monitoring system</p>
      </div>

      <form @submit.prevent="handleLogin" class="auth-form">
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
          <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-input">
            <input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              v-model="form.password"
              required
              class="form-input"
              :class="{ 'error': errors.password }"
              placeholder="Enter your password"
            >
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="password-toggle"
            >
              {{ showPassword ? '👁️' : '👁️‍🗨️' }}
            </button>
          </div>
          <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
        </div>

        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" v-model="form.remember">
            <span class="checkbox-text">Remember me</span>
          </label>
        </div>

        <button
          type="submit"
          class="auth-button"
          :disabled="loading"
        >
          <span v-if="loading" class="loading-spinner"></span>
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>

        <div class="auth-links">
          <button type="button" @click="showRegister = true" class="link-button">
            Don't have an account? Register here
          </button>
        </div>
      </form>

      <!-- Demo Accounts Info -->
      <div class="demo-accounts">
        <h3>Demo Accounts</h3>
        <div class="demo-account" v-for="account in demoAccounts" :key="account.email">
          <div class="demo-info">
            <strong>{{ account.role }}</strong>
            <div>{{ account.email }}</div>
            <div class="demo-password">Password: password</div>
          </div>
          <button @click="fillDemoAccount(account)" class="demo-button">
            Use Account
          </button>
        </div>
      </div>
    </div>

    <!-- Register Modal -->
    <RegisterForm
      v-if="showRegister"
      @close="showRegister = false"
      @registered="handleRegistered"
    />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useAuth } from '../composables/useAuth'
import RegisterForm from './RegisterForm.vue'

const { login } = useAuth()

const loading = ref(false)
const showPassword = ref(false)
const showRegister = ref(false)

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const errors = ref({})

const demoAccounts = [
  {
    role: 'MAPO Officer',
    email: 'mapo@example.com',
    password: 'password'
  },
  {
    role: 'FGV Staff',
    email: 'fgv@example.com',
    password: 'password'
  },
  {
    role: 'Plantation Owner',
    email: 'owner@example.com',
    password: 'password'
  }
]

const fillDemoAccount = (account) => {
  form.email = account.email
  form.password = account.password
}

const handleLogin = async () => {
  loading.value = true
  errors.value = {}

  try {
    await login(form)
    // Redirect will be handled by the auth composable
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = {
        email: [error.response?.data?.message || 'Login failed. Please try again.']
      }
    }
  } finally {
    loading.value = false
  }
}

const handleRegistered = () => {
  showRegister.value = false
  // User will be automatically logged in after registration
}
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.auth-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  width: 100%;
  max-width: 480px;
}

.auth-header {
  background: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%);
  color: white;
  padding: 40px 30px;
  text-align: center;
}

.auth-logo {
  height: 60px;
  margin-bottom: 20px;
  filter: brightness(0) invert(1);
}

.auth-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 8px;
}

.auth-subtitle {
  opacity: 0.9;
  font-size: 14px;
}

.auth-form {
  padding: 40px 30px 30px;
}

.form-group {
  margin-bottom: 24px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.2s ease;
  background: #f9fafb;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.error {
  border-color: #ef4444;
  background: #fef2f2;
}

.password-input {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.password-toggle:hover {
  opacity: 1;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 14px;
  color: #6b7280;
}

.auth-button {
  width: 100%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  padding: 14px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.auth-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
}

.auth-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.auth-links {
  text-align: center;
  margin-top: 24px;
}

.link-button {
  background: none;
  border: none;
  color: #3b82f6;
  cursor: pointer;
  font-size: 14px;
  text-decoration: underline;
}

.error-text {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
  display: block;
}

.demo-accounts {
  background: #f8f9fa;
  padding: 20px 30px;
  border-top: 1px solid #e5e7eb;
}

.demo-accounts h3 {
  margin-bottom: 16px;
  color: #374151;
  font-size: 16px;
}

.demo-account {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: white;
  border-radius: 8px;
  margin-bottom: 8px;
  border: 1px solid #e5e7eb;
}

.demo-info {
  flex: 1;
}

.demo-info strong {
  display: block;
  color: #374151;
  font-size: 14px;
}

.demo-info div {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.demo-password {
  font-family: monospace;
  background: #f3f4f6;
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-block !important;
  margin-top: 4px !important;
}

.demo-button {
  background: #10b981;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.demo-button:hover {
  background: #059669;
}
</style>
