// resources/js/AppLogic.js
import { ref, onMounted } from 'vue'
import { useAuth } from './composables/useAuth'
import { usePolygons } from './composables/usePolygons'

const { user, isLoading, logout, checkAuth } = useAuth()
const { polygons, fetchPolygons, createPolygon } = usePolygons()

const showPolygonForm = ref(false)
const selectedPolygon = ref(null)

onMounted(async () => {
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

export {
  user,
  isLoading,
  polygons,
  showPolygonForm,
  selectedPolygon,
  handleLogout,
  handlePolygonCreated,
  handlePolygonUpdated,
  handlePolygonDeleted,
  closePolygonForm,
  handlePolygonSaved
}
