import { ref } from 'vue'
import axios from 'axios'

const polygons = ref([])

export function usePolygons() {
  const fetchPolygons = async () => {
    try {
      const response = await axios.get('/polygons')
      polygons.value = response.data.polygons || []  // ✅ This extracts just the polygons array
    } catch (error) {
      console.error('Failed to fetch polygons:', error)
    }
  }

  const createPolygon = async (polygonData) => {
    try {
      const response = await axios.post('/polygons', polygonData)
      polygons.value.push(response.data)
      return response.data
    } catch (error) {
      console.error('Failed to create polygon:', error)
      throw error
    }
  }

  const updatePolygon = async (id, polygonData) => {
    try {
      const response = await axios.put(`/polygons/${id}`, polygonData)
      const index = polygons.value.findIndex(p => p.id === id)
      if (index !== -1) {
        polygons.value[index] = response.data
      }
      return response.data
    } catch (error) {
      console.error('Failed to update polygon:', error)
      throw error
    }
  }

  const deletePolygon = async (id) => {
    try {
      await axios.delete(`/polygons/${id}`)
      polygons.value = polygons.value.filter(p => p.id !== id)
    } catch (error) {
      console.error('Failed to delete polygon:', error)
      throw error
    }
  }

  return {
    polygons,
    fetchPolygons,
    createPolygon,
    updatePolygon,
    deletePolygon
  }
}
