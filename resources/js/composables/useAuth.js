// resources/js/composables/useAuth.js
import { ref } from 'vue'
import axios from 'axios'

const user = ref(null)
const isLoading = ref(false)
axios.defaults.baseURL = 'http://localhost:8000/api' // ✅ Add this line
// Set up axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

// Add request interceptor to include token
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Add response interceptor to handle token expiration
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('token')
      user.value = null
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export function useAuth() {
  const login = async (credentials) => {
    isLoading.value = true

    try {
      const response = await axios.post('/login', credentials)

      const { user: userData, token } = response.data

      // Store token
      localStorage.setItem('token', token)

      // Set user
      user.value = userData

      // Redirect to main app
      window.location.href = '/'

      return response.data
    } catch (error) {
      console.error('Login error:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData) => {
    isLoading.value = true

    try {
      const response = await axios.post('/register', userData)

      const { user: newUser, token } = response.data

      // Store token
      localStorage.setItem('token', token)

      // Set user
      user.value = newUser

      // Redirect to main app
      window.location.href = '/'

      return response.data
    } catch (error) {
      console.error('Registration error:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    try {
      await axios.post('/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      // Always clear local data
      localStorage.removeItem('token')
      user.value = null
      window.location.href = '/login'
    }
  }

  const fetchUser = async () => {
    const token = localStorage.getItem('token')
    if (!token) {
      return null
    }

    try {
      const response = await axios.get('/user')
      user.value = response.data
      return response.data
    } catch (error) {
      console.error('Fetch user error:', error)
      localStorage.removeItem('token')
      return null
    }
  }

  const checkAuth = async () => {
    const token = localStorage.getItem('token')
    if (!token) {
      return false
    }

    try {
      await fetchUser()
      return !!user.value
    } catch (error) {
      return false
    }
  }

  return {
    user,
    isLoading,
    login,
    register,
    logout,
    fetchUser,
    checkAuth
  }
}
