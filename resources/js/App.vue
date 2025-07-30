<!-- resources/js/App.vue -->
<template>
  <div id="app">
    <!-- Show login form if not authenticated -->
    <LoginForm v-if="!user && !isLoading" />

    <!-- Show loading screen while checking auth -->
    <div v-else-if="isLoading" class="loading-screen">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Loading...</p>
      </div>
    </div>

    <!-- Show main app if authenticated -->
    <template v-else>
      <Header :user="user" @logout="handleLogout" />

      <div class="main-container">
        <MapContainer
          :polygons="polygons"
          :user="user"
          @polygon-created="handlePolygonCreated"
          @polygon-updated="handlePolygonUpdated"
          @polygon-deleted="handlePolygonDeleted"
        />

        <PolygonForm
          v-if="showPolygonForm"
          :polygon="selectedPolygon"
          :user="user"
          @close="closePolygonForm"
          @saved="handlePolygonSaved"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuth } from './composables/useAuth'
import { usePolygons } from './composables/usePolygons'
import LoginForm from './components/LoginForm.vue'
import Header from './components/Header.vue'
import MapContainer from './components/MapContainer.vue'
import PolygonForm from './components/PolygonForm.vue'

const { user, isLoading, logout, checkAuth } = useAuth()
const { polygons, fetchPolygons, createPolygon } = usePolygons()

const showPolygonForm = ref(false)
const selectedPolygon = ref(null)

onMounted(async () => {
  // Check if user is already authenticated
  const isAuthenticated = await checkAuth()
  if (isAuthenticated) {
    await fetchPolygons()
  }
})

const handleLogout = async () => {
  await logout()
}

const handlePolygonCreated = (polygonData) => {
  selectedPolygon.value = polygonData
  showPolygonForm.value = true
}

const handlePolygonUpdated = (polygon) => {
  selectedPolygon.value = polygon
  showPolygonForm.value = true
}

const handlePolygonDeleted = async (polygon) => {
  if (confirm('Are you sure you want to delete this polygon?')) {
    console.log('Delete polygon:', polygon)
    await fetchPolygons()
  }
}

const closePolygonForm = () => {
  showPolygonForm.value = false
  selectedPolygon.value = null
}

const handlePolygonSaved = async (polygonData) => {
  console.log('Polygon saved:', polygonData)
  await createPolygon(polygonData)
  closePolygonForm()
  await fetchPolygons()
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

#app {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-container {
  flex: 1;
  position: relative;
}

.loading-screen {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.loading-content {
  text-align: center;
  color: white;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
