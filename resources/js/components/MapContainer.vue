<template>
  <div class="map-container">
    <!-- Header Controls -->
    <div class="map-header">
      <div class="controls">
        <div class="control-group">
          <label>Search Location</label>
          <input
            type="text"
            class="search-box"
            v-model="searchQuery"
            @keypress.enter="searchLocation"
            placeholder="Enter city or address..."
          >
        </div>
        <div class="control-group">
          <label>Left Panel</label>
          <select class="layer-select" v-model="leftLayerType" @change="updateLeftLayer">
            <option value="osm">OpenStreetMap</option>
            <option value="satellite">Satellite</option>
            <option value="terrain">Terrain</option>
            <option value="sentinel2020">Sentinel-2 2020</option>
            <option value="sentinel2021">Sentinel-2 2021</option>
            <option value="sentinel2022">Sentinel-2 2022</option>
            <option value="forestWatch">ForestWatch 2020</option>
          </select>
        </div>
        <div class="control-group">
          <label>Right Panel</label>
          <select class="layer-select" v-model="rightLayerType" @change="updateRightLayer">
            <option value="satellite" selected>Satellite</option>
            <option value="osm">OpenStreetMap</option>
            <option value="terrain">Terrain</option>
            <option value="sentinel2020">Sentinel-2 2020</option>
            <option value="sentinel2021">Sentinel-2 2021</option>
            <option value="sentinel2022">Sentinel-2 2022</option>
            <option value="forestWatch">ForestWatch 2020</option>
          </select>
        </div>
        <button class="search-btn" @click="searchLocation" :disabled="loading">
          {{ loading ? 'Searching...' : 'Search' }}
        </button>
      </div>
    </div>

    <!-- Slider Toggle Button -->
    <button
      class="slider-toggle"
      :class="{ active: splitViewEnabled }"
      @click="toggleSplitView"
    >
      {{ splitViewEnabled ? 'Split View' : 'Single View' }}
    </button>

    <!-- Map Panels -->
    <div class="map-panels">
      <div
        class="map-panel left-panel"
        :class="{ 'single-panel': !splitViewEnabled }"
        :style="leftPanelStyle"
      >
        <div class="panel-label">{{ getLayerName(leftLayerType) }}</div>
        <div ref="leftMapElement" class="map"></div>
        <div class="coordinates">{{ leftCoordinates }}</div>
      </div>

      <div
        class="map-panel right-panel"
        :class="{ 'single-panel': !splitViewEnabled }"
        :style="rightPanelStyle"
        v-show="splitViewEnabled"
      >
        <div class="panel-label">{{ getLayerName(rightLayerType) }}</div>
        <div ref="rightMapElement" class="map"></div>
        <div class="coordinates">{{ rightCoordinates }}</div>
      </div>

      <!-- Slider Container -->
      <div
        v-if="splitViewEnabled"
        class="slider-container"
        :style="{ left: sliderPosition + '%' }"
        @mousedown="startSliderDrag"
      >
        <div class="slider-handle">⟷</div>
      </div>
    </div>

    <!-- Enhanced Overlay Control Panel -->
    <div class="overlay-control">
      <div class="overlay-header">
        <div class="overlay-title">Map Layers & Tools</div>
        <button class="overlay-toggle" @click="toggleOverlay">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 10l5 5 5-5z"/>
          </svg>
        </button>
      </div>
      <div class="overlay-content" :class="{ open: overlayOpen }">
        <!-- Forest Classification Layers -->
        <div class="overlay-section">
          <div class="section-title">Forest Classification</div>
          <div v-for="layer in forestLayers" :key="layer.key" class="layer-item">
            <div class="layer-info">
              <div class="layer-color" :style="{ backgroundColor: layer.color }"></div>
              <div class="layer-name">{{ layer.name }}</div>
            </div>
            <div class="layer-controls">
              <input
                type="range"
                class="opacity-slider"
                min="0"
                max="100"
                v-model="layer.opacity"
                @input="updateLayerOpacity(layer.key, layer.opacity)"
              >
              <div
                class="layer-toggle"
                :class="{ active: layer.visible }"
                @click="toggleForestLayer(layer.key)"
              ></div>
            </div>
          </div>
        </div>

        <!-- Enhanced Drawing Tools -->
        <div class="overlay-section">
          <div class="section-title">Drawing Tools</div>
          <div class="drawing-tools">
            <button
              class="tool-btn"
              :class="{ active: drawingMode === 'polygon' }"
              @click="toggleDrawingMode('polygon')"
            >
              🔺 Draw Polygon
            </button>
            <button
              class="tool-btn"
              :class="{ active: drawingMode === 'rectangle' }"
              @click="toggleDrawingMode('rectangle')"
            >
              ⬜ Draw Rectangle
            </button>
            <button class="tool-btn" @click="clearAllDrawings">
              🗑️ Clear All
            </button>
            <button class="tool-btn export-btn" @click="exportDataWithAPI">
              💾 Export GeoJSON
            </button>
            <button class="tool-btn import-btn" @click="importData">
              📁 Import GeoJSON
            </button>
            <button class="tool-btn analysis-btn" @click="loadSavedPolygons" v-if="user">
             🔄 Reload Data
            </button>
          </div>
        </div>

        <!-- Analysis Tools -->
        <div class="overlay-section">
          <div class="section-title">Analysis</div>
          <div class="drawing-tools">
            <button class="tool-btn analysis-btn" @click="measureArea">
              📏 Measure Area
            </button>
            <button class="tool-btn analysis-btn" @click="generateReport">
              📊 Generate Report
            </button>
            <button class="tool-btn analysis-btn" @click="exportToDatabase" v-if="user">
              💽 Save to Database
            </button>
          </div>
        </div>

        <!-- Data Summary -->
        <div class="overlay-section" v-if="drawLayers.length > 0 || importedLayers.length > 0">
          <div class="section-title">Data Summary</div>
          <div class="data-summary">
            <div class="summary-item">
              <span class="summary-label">Drawn Polygons:</span>
              <span class="summary-value">{{ drawLayers.length }}</span>
            </div>
            <div class="summary-item" v-if="totalDrawnArea > 0">
              <span class="summary-label">Total Area:</span>
              <span class="summary-value">{{ totalDrawnArea.toFixed(2) }} ha</span>
            </div>
            <div class="summary-item" v-if="importedLayers.length > 0">
              <span class="summary-label">Imported Features:</span>
              <span class="summary-value">{{ importedLayers.length }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sync Toggle -->
    <button
      class="sync-toggle"
      :class="{ active: mapsSynced }"
      @click="toggleMapSync"
      title="Toggle map synchronization"
    >
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
      </svg>
    </button>

    <!-- Hidden file input for imports -->
    <input
      ref="fileInput"
      type="file"
      accept=".geojson,.json"
      @change="handleFileImportWithAPI"
      style="display: none"
    >

    <!-- Loading overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <div class="loading-text">{{ loadingMessage }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  polygons: Array,  // ✅ This is correct
  user: Object
})
const emit = defineEmits([
  'polygon-created',
  'polygon-updated',
  'polygon-deleted',
  'drawings-cleared',
  'data-imported',
  'data-exported',
  'layer-toggled',
  'save-to-database'
])

// Refs
const leftMapElement = ref(null)
const rightMapElement = ref(null)
const fileInput = ref(null)

// Reactive data
const searchQuery = ref('')
const loading = ref(false)
const loadingMessage = ref('Loading...')
const splitViewEnabled = ref(true)
const sliderPosition = ref(50)
const overlayOpen = ref(true)
const mapsSynced = ref(true)
const drawingMode = ref(null)
const leftLayerType = ref('osm')
const rightLayerType = ref('satellite')
const leftCoordinates = ref('Lat: 0.0000, Lng: 0.0000')
const rightCoordinates = ref('Lat: 0.0000, Lng: 0.0000')
const apiLoading = ref(false)
const apiError = ref(null)
const savedPolygons = ref([])

// Map instances and variables
let leftMap = null
let rightMap = null
let leftLayer = null
let rightLayer = null
let searchMarkers = []
let drawLayers = []
let isDrawing = false
let polygonPoints = []
let currentPolygon = null
let tempDrawingMarkers = []
let importedLayers = []

// Computed properties
const totalDrawnArea = computed(() => {
  return drawLayers.reduce((sum, layer) => sum + parseFloat(layer.area || 0), 0)
})

const leftPanelStyle = computed(() => {
  if (!splitViewEnabled.value) return {}
  return {
    clipPath: `polygon(0 0, ${sliderPosition.value}% 0, ${sliderPosition.value}% 100%, 0 100%)`
  }
})

const rightPanelStyle = computed(() => {
  if (!splitViewEnabled.value) return {}
  return {
    clipPath: `polygon(${sliderPosition.value}% 0, 100% 0, 100% 100%, ${sliderPosition.value}% 100%)`
  }
})

// Forest classification layers
const forestLayers = ref([
  {
    key: 'deforestation',
    name: 'Deforestation Areas',
    color: '#dc2626',
    visible: false,
    opacity: 70,
    layerGroup: null
  },
  {
    key: 'regrowth',
    name: 'Regrowth Areas',
    color: '#16a34a',
    visible: false,
    opacity: 70,
    layerGroup: null
  },
  {
    key: 'primaryForest',
    name: 'Primary Forest',
    color: '#065f46',
    visible: false,
    opacity: 70,
    layerGroup: null
  },
  {
    key: 'disturbedForest',
    name: 'Disturbed Forest',
    color: '#fbbf24',
    visible: false,
    opacity: 70,
    layerGroup: null
  }
])

// API service
const apiService = {
  baseURL: '/api/forest-polygons',

async request(method, endpoint = '', data = null) {
  try {
    apiError.value = null
    apiLoading.value = true

    const config = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        // Add authorization header if you have a token
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        // Or if using session-based auth, make sure credentials are included
      },
      credentials: 'include', // Important for session-based auth
    }

    if (data) {
      config.body = JSON.stringify(data)
    }

    const response = await fetch(`${this.baseURL}${endpoint}`, config)
    const result = await response.json()

    if (!response.ok) {
      throw new Error(result.message || `HTTP ${response.status}`)
    }

    return result
  } catch (error) {
    apiError.value = error.message
    console.error('API request failed:', error)
    throw error
  } finally {
    apiLoading.value = false
  }
},

  async getPolygons(filters = {}) {
    const params = new URLSearchParams(filters).toString()
    return this.request('GET', params ? `?${params}` : '')
  },

  async createPolygon(polygonData) {
    return this.request('POST', '', polygonData)
  },

  async updatePolygon(uuid, polygonData) {
    return this.request('PUT', `/${uuid}`, polygonData)
  },

  async deletePolygon(uuid) {
    return this.request('DELETE', `/${uuid}`)
  },

  async bulkImport(geoJsonData) {
    return this.request('POST', '/bulk-import', { geojson: geoJsonData })
  },

  async exportPolygons(filters = {}) {
    const params = new URLSearchParams(filters).toString()
    return this.request('GET', `/export${params ? `?${params}` : ''}`)
  }
}

// Tile layer configurations
const tileLayers = {
  osm: {
    url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '© OpenStreetMap contributors',
    name: 'OpenStreetMap'
  },
  satellite: {
    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    attribution: 'Tiles © Esri',
    name: 'Satellite'
  },
  terrain: {
    url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
    attribution: '© OpenTopoMap contributors',
    name: 'Terrain'
  },
  sentinel2020: {
    url: 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless-2020_3857/default/g/{z}/{y}/{x}.jpg',
    attribution: '© SENTINEL-2 Cloudless 2020',
    name: 'Sentinel-2 2020'
  },
  sentinel2021: {
    url: 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless-2021_3857/default/g/{z}/{y}/{x}.jpg',
    attribution: '© SENTINEL-2 Cloudless 2021',
    name: 'Sentinel-2 2021'
  },
  sentinel2022: {
    url: 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless-2022_3857/default/g/{z}/{y}/{x}.jpg',
    attribution: '© SENTINEL-2 Cloudless 2022',
    name: 'Sentinel-2 2022'
  },
   ForestWatch: {
    url: 'https://tiles.globalforestwatch.org/nasa_viirs_fire_alerts/{version}/dynamic/{z}/{x}/{y}.pbf',
    attribution: 'ForestWatch',
    name: 'ForestWatch 2020'
  }
}

// Default location (Malaysia)
const DEFAULT_LOCATION = [4.4286, 102.0581]
const DEFAULT_ZOOM = 10

// Utility functions
const showTemporaryMessage = (message, type = 'info', duration = 3000) => {
  const messageEl = document.createElement('div')
  messageEl.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
    color: white;
    padding: 12px 16px;
    border-radius: 6px;
    z-index: 10000;
    font-family: sans-serif;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease;
    max-width: 350px;
    word-wrap: break-word;
  `
  messageEl.textContent = message
  document.body.appendChild(messageEl)

  setTimeout(() => {
    messageEl.style.animation = 'slideOut 0.3s ease'
    setTimeout(() => messageEl.remove(), 300)
  }, duration)
}

const isValidCoordinate = (lat, lng) => {
  return typeof lat === 'number' && typeof lng === 'number' &&
         lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180 &&
         !isNaN(lat) && !isNaN(lng)
}

const calculatePolygonAreaEnhanced = (points) => {
  if (!points || points.length < 3) return 0

  try {
    const validPoints = points.filter(point =>
      Array.isArray(point) && point.length >= 2 &&
      isValidCoordinate(point[0], point[1])
    )

    if (validPoints.length < 3) return 0

    const earthRadius = 6378137
    const toRadians = (degrees) => degrees * Math.PI / 180

    let area = 0
    const n = validPoints.length

    for (let i = 0; i < n; i++) {
      const j = (i + 1) % n
      const lat1 = toRadians(validPoints[i][0])
      const lng1 = toRadians(validPoints[i][1])
      const lat2 = toRadians(validPoints[j][0])
      const lng2 = toRadians(validPoints[j][1])

      area += (lng2 - lng1) * (2 + Math.sin(lat1) + Math.sin(lat2))
    }

    return Math.abs(area * earthRadius * earthRadius / 2)

  } catch (error) {
    console.error('Error calculating polygon area:', error)
    return 0
  }
}

const convertToGeoJSON = (polygon) => {
  try {
    if (!polygon || !polygon.points || polygon.points.length < 3) {
      throw new Error('Invalid polygon data')
    }

    const coordinates = polygon.points.map(point => {
      if (Array.isArray(point) && point.length >= 2) {
        return [point[1], point[0]]
      }
      throw new Error('Invalid point format')
    })

    if (coordinates.length > 0) {
      const first = coordinates[0]
      const last = coordinates[coordinates.length - 1]
      if (first[0] !== last[0] || first[1] !== last[1]) {
        coordinates.push([first[0], first[1]])
      }
    }

    return {
      type: 'Feature',
      properties: {
        id: polygon.id,
        area_hectares: polygon.area,
        area_square_meters: polygon.areaSquareMeters,
        created_at: polygon.createdAt || new Date().toISOString(),
        type: polygon.type || 'drawn_polygon',
        source: 'manual'
      },
      geometry: {
        type: 'Polygon',
        coordinates: [coordinates]
      }
    }
  } catch (error) {
    console.error('Error converting to GeoJSON:', error)
    return null
  }
}

const calculateCentroid = (points) => {
  if (!points || points.length === 0) return [0, 0]

  const n = points.length
  let lat = 0, lng = 0

  points.forEach(point => {
    lat += point[0]
    lng += point[1]
  })

  return [lat / n, lng / n]
}

const isValidGeoJSON = (geojson) => {
  try {
    if (!geojson || typeof geojson !== 'object') return false
    if (geojson.type !== 'FeatureCollection' && geojson.type !== 'Feature') return false

    const features = geojson.type === 'FeatureCollection' ? geojson.features : [geojson]
    if (!Array.isArray(features)) return false

    return features.every(feature => {
      if (!feature || typeof feature !== 'object') return false
      if (feature.type !== 'Feature') return false
      if (!feature.geometry || !feature.geometry.type) return false
      if (!Array.isArray(feature.geometry.coordinates)) return false
      return true
    })
  } catch (error) {
    console.error('GeoJSON validation error:', error)
    return false
  }
}

const getStyleForForestType = (forestType) => {
  const styles = {
    primary: { color: '#065f46', fillColor: '#065f46', fillOpacity: 0.3 },
    secondary: { color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.3 },
    plantation: { color: '#84cc16', fillColor: '#84cc16', fillOpacity: 0.3 },
    degraded: { color: '#dc2626', fillColor: '#dc2626', fillOpacity: 0.3 },
    regrowth: { color: '#10b981', fillColor: '#10b981', fillOpacity: 0.3 },
    mangrove: { color: '#0891b2', fillColor: '#0891b2', fillOpacity: 0.3 },
    unknown: { color: '#6b7280', fillColor: '#6b7280', fillOpacity: 0.3 }
  }

  return styles[forestType] || styles.unknown
}

// Map initialization
const initializeMaps = () => {
  if (!leftMapElement.value) return

  leftMap = L.map(leftMapElement.value, {
    zoomControl: false,
    attributionControl: true
  }).setView(DEFAULT_LOCATION, DEFAULT_ZOOM)

  L.control.zoom({ position: 'topright' }).addTo(leftMap)

  leftLayer = L.tileLayer(tileLayers.osm.url, {
    attribution: tileLayers.osm.attribution,
    maxZoom: 18
  }).addTo(leftMap)

  if (splitViewEnabled.value && rightMapElement.value) {
    rightMap = L.map(rightMapElement.value, {
      zoomControl: false,
      attributionControl: true
    }).setView(DEFAULT_LOCATION, DEFAULT_ZOOM)

    L.control.zoom({ position: 'topright' }).addTo(rightMap)

    rightLayer = L.tileLayer(tileLayers.satellite.url, {
      attribution: tileLayers.satellite.attribution,
      maxZoom: 18
    }).addTo(rightMap)
  }

  setupMapEvents()
}

const setupMapEvents = () => {
  leftMap.on('moveend', () => {
    updateCoordinates(leftMap, 'left')
    if (mapsSynced.value && rightMap) {
      syncMaps(leftMap, rightMap)
    }
  })

  leftMap.on('click', onMapClick)
  leftMap.on('dblclick', onMapDoubleClick)

  if (rightMap) {
    rightMap.on('moveend', () => {
      updateCoordinates(rightMap, 'right')
      if (mapsSynced.value) {
        syncMaps(rightMap, leftMap)
      }
    })

    rightMap.on('click', onMapClick)
    rightMap.on('dblclick', onMapDoubleClick)
  }
}

const updateCoordinates = (map, side) => {
  const center = map.getCenter()
  const zoom = map.getZoom()
  const coordString = `Lat: ${center.lat.toFixed(4)}, Lng: ${center.lng.toFixed(4)}, Zoom: ${zoom}`

  if (side === 'left') {
    leftCoordinates.value = coordString
  } else {
    rightCoordinates.value = coordString
  }
}

const syncMaps = (sourceMap, targetMap) => {
  if (!mapsSynced.value) return
  const center = sourceMap.getCenter()
  const zoom = sourceMap.getZoom()
  targetMap.setView(center, zoom, { animate: false })
}

// Map interaction handlers
const onMapClick = (e) => {
  if (!isDrawing || drawingMode.value !== 'polygon') return

  const latlng = e.latlng
  polygonPoints.push([latlng.lat, latlng.lng])

  const markerStyle = {
    radius: 5,
    color: '#3b82f6',
    weight: 2,
    fillColor: '#ffffff',
    fillOpacity: 1
  }

  const leftMarker = L.circleMarker(latlng, markerStyle).addTo(leftMap)
  let rightMarker = null
  if (rightMap) {
    rightMarker = L.circleMarker(latlng, markerStyle).addTo(rightMap)
  }

  tempDrawingMarkers.push({ left: leftMarker, right: rightMarker })
  updateTempPolygon()

  if (polygonPoints.length >= 3) {
    console.log(`Point ${polygonPoints.length} added. Press ENTER or double-click to finish polygon.`)
  }
}

const onMapDoubleClick = (e) => {
  if (isDrawing && drawingMode.value === 'polygon' && polygonPoints.length >= 3) {
    e.originalEvent.preventDefault()
    finishPolygon()
  }
}

const updateTempPolygon = () => {
  if (currentPolygon) {
    leftMap.removeLayer(currentPolygon)
    if (currentPolygon.rightCopy && rightMap) {
      rightMap.removeLayer(currentPolygon.rightCopy)
    }
    currentPolygon = null
  }

  if (polygonPoints.length >= 2) {
    const polylineStyle = {
      color: '#3b82f6',
      weight: 3,
      opacity: 0.8,
      dashArray: '8, 4'
    }

    currentPolygon = L.polyline(polygonPoints, polylineStyle)
    leftMap.addLayer(currentPolygon)

    if (rightMap) {
      const rightPolygon = L.polyline(polygonPoints, polylineStyle)
      rightMap.addLayer(rightPolygon)
      currentPolygon.rightCopy = rightPolygon
    }
  }
}

const finishPolygon = async () => {
  if (polygonPoints.length < 3) {
    showTemporaryMessage('Please add at least 3 points to create a polygon.', 'error')
    return
  }

  try {
    console.log(`🔧 Creating polygon with ${polygonPoints.length} points...`)

    const polygonStyle = {
      color: '#3b82f6',
      weight: 2,
      opacity: 0.9,
      fillColor: '#3b82f6',
      fillOpacity: 0.2,
      className: 'drawn-polygon'
    }

    const leftPolygon = L.polygon(polygonPoints, polygonStyle)
    leftMap.addLayer(leftPolygon)

    let rightPolygon = null
    if (rightMap) {
      rightPolygon = L.polygon(polygonPoints, polygonStyle)
      rightMap.addLayer(rightPolygon)
    }

    const areaSquareMeters = calculatePolygonAreaEnhanced(polygonPoints)
    const areaHectares = (areaSquareMeters / 10000).toFixed(2)
    const centroid = calculateCentroid(polygonPoints)

    if (props.user?.id) {
      const coordinates = polygonPoints.map(point => [point[1], point[0]])

      if (coordinates.length > 0) {
        const first = coordinates[0]
        const last = coordinates[coordinates.length - 1]
        if (first[0] !== last[0] || first[1] !== last[1]) {
          coordinates.push([first[0], first[1]])
        }
      }

      const polygonData = {
        geometry: {
          type: 'Polygon',
          coordinates: [coordinates]
        },
        type: 'primary',
        source: 'manual',
        name: `Polygon ${new Date().toLocaleDateString()}`,
        confidence_level: 'medium',
        is_public: false
      }

      showTemporaryMessage('Saving polygon to database...', 'info')

      try {
        const response = await apiService.createPolygon(polygonData)

        if (response.status === 'success') {
          const savedPolygon = response.data
          const polygonId = savedPolygon.properties.id

          const popupContent = createSavedPolygonPopup(savedPolygon, areaHectares, polygonPoints.length)

          leftPolygon.bindPopup(popupContent, { maxWidth: 320, className: 'custom-polygon-popup' })
          if (rightPolygon) {
            rightPolygon.bindPopup(popupContent, { maxWidth: 320, className: 'custom-polygon-popup' })
          }

          const layerData = {
            id: polygonId,
            uuid: savedPolygon.properties.id,
            left: leftPolygon,
            right: rightPolygon,
            type: 'polygon',
            area: parseFloat(areaHectares),
            areaSquareMeters: areaSquareMeters,
            points: [...polygonPoints],
            centroid: centroid,
            createdAt: savedPolygon.properties.created_at,
            forestType: savedPolygon.properties.type,
            heightCategory: savedPolygon.properties.height_category,
            canopyDensity: savedPolygon.properties.canopy_density,
            source: savedPolygon.properties.source,
            savedToDatabase: true
          }

          drawLayers.push(layerData)
          savedPolygons.value.push(savedPolygon)

          showTemporaryMessage(`Polygon saved! ${areaHectares} hectares`, 'success')
          console.log(`✅ Polygon saved to database with ID: ${polygonId}`)
        }
      } catch (apiError) {
        console.error('API save error:', apiError)
        showTemporaryMessage(`Database save failed: ${apiError.message}. Polygon created locally.`, 'error')

        const localPopupContent = createLocalPolygonPopup(polygonPoints.length, areaHectares)
        leftPolygon.bindPopup(localPopupContent, { maxWidth: 280, className: 'custom-polygon-popup' })
        if (rightPolygon) {
          rightPolygon.bindPopup(localPopupContent, { maxWidth: 280, className: 'custom-polygon-popup' })
        }

        const localLayerData = {
          id: `local_${Date.now()}`,
          left: leftPolygon,
          right: rightPolygon,
          type: 'polygon',
          area: parseFloat(areaHectares),
          areaSquareMeters: areaSquareMeters,
          points: [...polygonPoints],
          centroid: centroid,
          createdAt: new Date().toISOString(),
          savedToDatabase: false
        }

        drawLayers.push(localLayerData)
      }
    } else {
      const localPopupContent = createLocalPolygonPopup(polygonPoints.length, areaHectares)
      leftPolygon.bindPopup(localPopupContent, { maxWidth: 280, className: 'custom-polygon-popup' })
      if (rightPolygon) {
        rightPolygon.bindPopup(localPopupContent, { maxWidth: 280, className: 'custom-polygon-popup' })
      }

      const localLayerData = {
        id: `local_${Date.now()}`,
        left: leftPolygon,
        right: rightPolygon,
        type: 'polygon',
        area: parseFloat(areaHectares),
        areaSquareMeters: areaSquareMeters,
        points: [...polygonPoints],
        centroid: centroid,
        createdAt: new Date().toISOString(),
        savedToDatabase: false
      }

      drawLayers.push(localLayerData)
      showTemporaryMessage(`Polygon created! ${areaHectares} hectares (local only)`, 'success')
    }

    resetDrawing()
    drawingMode.value = null
    isDrawing = false

  } catch (error) {
    console.error('❌ Error creating polygon:', error)
    showTemporaryMessage(`Failed to create polygon: ${error.message}`, 'error')
    resetDrawing()
  }
}

const resetDrawing = () => {
  console.log('🧹 Resetting drawing state...')

  try {
    polygonPoints = []

    tempDrawingMarkers.forEach((marker, index) => {
      try {
        if (marker.left && leftMap && leftMap.hasLayer(marker.left)) {
          leftMap.removeLayer(marker.left)
        }
        if (marker.right && rightMap && rightMap.hasLayer(marker.right)) {
          rightMap.removeLayer(marker.right)
        }
      } catch (markerError) {
        console.warn(`Failed to remove temp marker ${index}:`, markerError)
      }
    })
    tempDrawingMarkers = []

    if (currentPolygon) {
      try {
        if (leftMap && leftMap.hasLayer(currentPolygon)) {
          leftMap.removeLayer(currentPolygon)
        }
        if (currentPolygon.rightCopy && rightMap && rightMap.hasLayer(currentPolygon.rightCopy)) {
          rightMap.removeLayer(currentPolygon.rightCopy)
        }
      } catch (polygonError) {
        console.warn('Failed to remove current polygon:', polygonError)
      }
      currentPolygon = null
    }

    console.log('✅ Drawing state reset successfully')

  } catch (error) {
    console.error('Error during drawing reset:', error)
  }
}

// Window functions for popup interactions
if (typeof window !== 'undefined') {
  window.updateSavedPolygon = async function(polygonUuid) {
    try {
      const forestType = document.getElementById(`updateForestType-${polygonUuid}`)?.value
      const heightCategory = document.getElementById(`updateHeight-${polygonUuid}`)?.value || null
      const canopyDensity = document.getElementById(`updateCanopy-${polygonUuid}`)?.value || null

      showTemporaryMessage('Updating polygon...', 'info')

      const updateData = {
        type: forestType,
        height_category: heightCategory,
        canopy_density: canopyDensity
      }

      const response = await apiService.updatePolygon(polygonUuid, updateData)

      if (response.status === 'success') {
        const layerIndex = drawLayers.findIndex(layer => layer.uuid === polygonUuid)
        if (layerIndex !== -1) {
          const layer = drawLayers[layerIndex]
          layer.forestType = forestType
          layer.heightCategory = heightCategory
          layer.canopyDensity = canopyDensity
        }

        const savedIndex = savedPolygons.value.findIndex(p => p.properties.id === polygonUuid)
        if (savedIndex !== -1) {
          savedPolygons.value[savedIndex] = response.data
        }

        showTemporaryMessage('Polygon updated successfully', 'success')
        console.log(`✅ Updated polygon ${polygonUuid} in database`)
      }
    } catch (error) {
      console.error('Error updating polygon:', error)
      showTemporaryMessage(`Update failed: ${error.message}`, 'error')
    }
  }

  window.deleteSavedPolygon = async function(polygonUuid) {
    try {
      if (!confirm('Are you sure you want to delete this polygon? This action cannot be undone.')) {
        return
      }

      showTemporaryMessage('Deleting polygon...', 'info')

      const response = await apiService.deletePolygon(polygonUuid)

      if (response.status === 'success') {
        const layerIndex = drawLayers.findIndex(layer => layer.uuid === polygonUuid)
        if (layerIndex !== -1) {
          const layer = drawLayers[layerIndex]

          if (layer.left && leftMap && leftMap.hasLayer(layer.left)) {
            leftMap.removeLayer(layer.left)
          }
          if (layer.right && rightMap && rightMap.hasLayer(layer.right)) {
            rightMap.removeLayer(layer.right)
          }

          drawLayers.splice(layerIndex, 1)
        }

        const savedIndex = savedPolygons.value.findIndex(p => p.properties.id === polygonUuid)
        if (savedIndex !== -1) {
          savedPolygons.value.splice(savedIndex, 1)
        }

        showTemporaryMessage('Polygon deleted successfully', 'success')
        console.log(`✅ Deleted polygon ${polygonUuid} from database`)
      }
    } catch (error) {
      console.error('Error deleting polygon:', error)
      showTemporaryMessage(`Delete failed: ${error.message}`, 'error')
    }
  }
}

// Popup creation functions
const createSavedPolygonPopup = (savedPolygon, areaHectares, pointCount) => {
  const props = savedPolygon.properties || savedPolygon

  return `
    <div style="text-align: center; font-family: sans-serif; min-width: 280px;">
      <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 8px; margin: -9px -9px 8px -9px; border-radius: 4px 4px 0 0;">
        <strong>💾 Saved Forest Polygon</strong>
      </div>

      <div style="padding: 8px 0;">
        <div style="color: #059669; font-weight: 600; font-size: 16px;">
          ${areaHectares} hectares
        </div>
        <div style="color: #6b7280; font-size: 12px; margin-top: 2px;">
          ID: ${props.id} • ${pointCount} points
        </div>
      </div>

      <div style="margin: 12px 0; text-align: left; border-top: 1px solid #e5e7eb; padding-top: 12px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
          <div>
            <span style="font-size: 11px; color: #6b7280;">Type:</span><br>
            <span style="font-size: 12px; font-weight: 500; color: #059669;">${props.type || 'unknown'}</span>
          </div>
          <div>
            <span style="font-size: 11px; color: #6b7280;">Source:</span><br>
            <span style="font-size: 12px;">${props.source || 'manual'}</span>
          </div>
        </div>

        <div style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 12px;">
          <div style="margin-bottom: 8px;">
            <select id="updateForestType-${props.id}" style="width: 100%; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
              <option value="primary" ${props.type === 'primary' ? 'selected' : ''}>Primary Forest</option>
              <option value="secondary" ${props.type === 'secondary' ? 'selected' : ''}>Secondary Forest</option>
              <option value="plantation" ${props.type === 'plantation' ? 'selected' : ''}>Plantation</option>
              <option value="degraded" ${props.type === 'degraded' ? 'selected' : ''}>Degraded Forest</option>
              <option value="regrowth" ${props.type === 'regrowth' ? 'selected' : ''}>Regrowth</option>
              <option value="mangrove" ${props.type === 'mangrove' ? 'selected' : ''}>Mangrove</option>
            </select>
          </div>

          <div style="margin-bottom: 8px;">
            <select id="updateHeight-${props.id}" style="width: 100%; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
              <option value="">Select height...</option>
              <option value="tall" ${props.height_category === 'tall' ? 'selected' : ''}>Tall (>20m)</option>
              <option value="medium" ${props.height_category === 'medium' ? 'selected' : ''}>Medium (10-20m)</option>
              <option value="short" ${props.height_category === 'short' ? 'selected' : ''}>Short (5-10m)</option>
              <option value="low" ${props.height_category === 'low' ? 'selected' : ''}>Low (<5m)</option>
            </select>
          </div>

          <div style="margin-bottom: 8px;">
            <select id="updateCanopy-${props.id}" style="width: 100%; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
              <option value="">Select density...</option>
              <option value="dense" ${props.canopy_density === 'dense' ? 'selected' : ''}>Dense (>70%)</option>
              <option value="medium" ${props.canopy_density === 'medium' ? 'selected' : ''}>Medium (40-70%)</option>
              <option value="sparse" ${props.canopy_density === 'sparse' ? 'selected' : ''}>Sparse (10-40%)</option>
              <option value="very_sparse" ${props.canopy_density === 'very_sparse' ? 'selected' : ''}>Very Sparse (<10%)</option>
            </select>
          </div>
        </div>
      </div>

      <div style="display: flex; gap: 8px; margin-top: 12px;">
        <button onclick="updateSavedPolygon('${props.id}')"
                style="flex: 1; padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
          💾 Update
        </button>
        <button onclick="deleteSavedPolygon('${props.id}')"
                style="flex: 1; padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
          🗑️ Delete
        </button>
      </div>

      <div style="margin-top: 8px; color: #6b7280; font-size: 11px;">
        Saved: ${new Date(props.created_at).toLocaleString()}
      </div>
    </div>
  `
}

const createLocalPolygonPopup = (pointCount, areaHectares) => {
  return `
    <div style="text-align: center; font-family: sans-serif; min-width: 200px;">
      <div style="background: linear-gradient(135deg, #3b82f6, #1e40af); color: white; padding: 8px; margin: -9px -9px 8px -9px; border-radius: 4px 4px 0 0;">
        <strong>📍 Local Polygon</strong>
      </div>

      <div style="padding: 8px 0;">
        <div style="color: #059669; font-weight: 600; font-size: 16px;">
          ${areaHectares} hectares
        </div>
        <div style="color: #6b7280; font-size: 12px; margin-top: 2px;">
          ${pointCount} points • Not saved to database
        </div>
      </div>

      <div style="margin-top: 8px; color: #6b7280; font-size: 11px;">
        Created: ${new Date().toLocaleTimeString()}
      </div>

      <div style="margin-top: 8px; padding: 8px; background: #fef3c7; border-radius: 4px;">
        <div style="color: #92400e; font-size: 11px;">
          💡 Login to save polygons to database
        </div>
      </div>
    </div>
  `
}

// Search functionality
const searchLocation = async () => {
  if (!searchQuery.value.trim()) return

  loading.value = true
  loadingMessage.value = 'Searching location...'

  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=1`,
      {
        headers: {
          'User-Agent': 'Reforestation Monitor App'
        }
      }
    )

    const data = await response.json()

    if (data.length > 0) {
      const lat = parseFloat(data[0].lat)
      const lng = parseFloat(data[0].lon)

      clearSearchMarkers()

      const searchIcon = L.icon({
        iconUrl: 'data:image/svg+xml;base64,' + btoa(`
          <svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
            <path d="M12.5 0C5.596 0 0 5.596 0 12.5c0 12.5 12.5 28.5 12.5 28.5s12.5-16 12.5-28.5C25 5.596 19.404 0 12.5 0z" fill="#ef4444"/>
            <circle cx="12.5" cy="12.5" r="6" fill="white"/>
          </svg>
        `),
        iconSize: [25, 41],
        iconAnchor: [12.5, 41]
      })

      const markerLeft = L.marker([lat, lng], { icon: searchIcon }).addTo(leftMap)
      if (rightMap) {
        const markerRight = L.marker([lat, lng], { icon: searchIcon }).addTo(rightMap)
        searchMarkers.push({ left: markerLeft, right: markerRight })
      } else {
        searchMarkers.push({ left: markerLeft })
      }

      leftMap.setView([lat, lng], 12)
      if (rightMap && mapsSynced.value) {
        rightMap.setView([lat, lng], 12)
      }

      showTemporaryMessage(`Found: ${data[0].display_name}`, 'success')
    } else {
      showTemporaryMessage('Location not found', 'error')
    }
  } catch (error) {
    console.error('Search error:', error)
    showTemporaryMessage('Search failed', 'error')
  } finally {
    loading.value = false
  }
}

const clearSearchMarkers = () => {
  searchMarkers.forEach(marker => {
    if (marker.left && leftMap && leftMap.hasLayer(marker.left)) {
      leftMap.removeLayer(marker.left)
    }
    if (marker.right && rightMap && rightMap.hasLayer(marker.right)) {
      rightMap.removeLayer(marker.right)
    }
  })
  searchMarkers = []
}

// Layer management
const getLayerName = (layerType) => {
  return tileLayers[layerType]?.name || layerType
}

const updateLeftLayer = () => {
  if (leftLayer && leftMap) {
    leftMap.removeLayer(leftLayer)
    leftLayer = L.tileLayer(tileLayers[leftLayerType.value].url, {
      attribution: tileLayers[leftLayerType.value].attribution,
      maxZoom: 18
    }).addTo(leftMap)
  }
}

const updateRightLayer = () => {
  if (rightLayer && rightMap) {
    rightMap.removeLayer(rightLayer)
    rightLayer = L.tileLayer(tileLayers[rightLayerType.value].url, {
      attribution: tileLayers[rightLayerType.value].attribution,
      maxZoom: 18
    }).addTo(rightMap)
  }
}

const clearImportedLayers = () => {
  if (importedLayers.length > 0) {
    console.log(`🧹 Clearing ${importedLayers.length} imported layers...`)

    importedLayers.forEach((layer, index) => {
      try {
        if (layer.left && leftMap && leftMap.hasLayer(layer.left)) {
          leftMap.removeLayer(layer.left)
        }
        if (layer.right && rightMap && rightMap.hasLayer(layer.right)) {
          rightMap.removeLayer(layer.right)
        }
      } catch (error) {
        console.warn(`Failed to remove imported layer ${index}:`, error)
      }
    })

    importedLayers.length = 0
    console.log('✅ All imported layers cleared')
  }
}

// Split view controls
const toggleSplitView = () => {
  splitViewEnabled.value = !splitViewEnabled.value

  if (splitViewEnabled.value) {
    nextTick(() => {
      if (!rightMap && rightMapElement.value) {
        rightMap = L.map(rightMapElement.value, {
          zoomControl: false,
          attributionControl: true
        }).setView(leftMap.getCenter(), leftMap.getZoom())

        L.control.zoom({ position: 'topright' }).addTo(rightMap)

        rightLayer = L.tileLayer(tileLayers[rightLayerType.value].url, {
          attribution: tileLayers[rightLayerType.value].attribution,
          maxZoom: 18
        }).addTo(rightMap)

        rightMap.on('moveend', () => {
          updateCoordinates(rightMap, 'right')
          if (mapsSynced.value) {
            syncMaps(rightMap, leftMap)
          }
        })

        rightMap.on('click', onMapClick)
        rightMap.on('dblclick', onMapDoubleClick)
      }
    })
  }

  setTimeout(() => {
    if (leftMap) leftMap.invalidateSize()
    if (rightMap) rightMap.invalidateSize()
  }, 300)
}

// Slider functionality
const startSliderDrag = (e) => {
  e.preventDefault()

  const containerRect = e.currentTarget.parentElement.getBoundingClientRect()
  const startX = e.clientX
  const startPosition = sliderPosition.value

  const onMouseMove = (moveEvent) => {
    const deltaX = moveEvent.clientX - startX
    const deltaPercentage = (deltaX / containerRect.width) * 100
    const newPosition = Math.max(15, Math.min(85, startPosition + deltaPercentage))
    sliderPosition.value = newPosition
  }

  const onMouseUp = () => {
    document.removeEventListener('mousemove', onMouseMove)
    document.removeEventListener('mouseup', onMouseUp)
  }

  document.addEventListener('mousemove', onMouseMove)
  document.addEventListener('mouseup', onMouseUp)
}

// Drawing functionality
const toggleDrawingMode = (mode) => {
  if (drawingMode.value === mode) {
    drawingMode.value = null
    isDrawing = false
    resetDrawing()
    showTemporaryMessage('Drawing mode disabled', 'info')
  } else {
    drawingMode.value = mode
    isDrawing = true
    resetDrawing()

    if (mode === 'polygon') {
      showTemporaryMessage('Polygon drawing enabled. Click to add points, double-click or press Enter to finish.', 'info')
    } else if (mode === 'rectangle') {
      showTemporaryMessage('Rectangle drawing enabled. Click and drag to draw.', 'info')
    }
  }
}

const clearAllDrawings = () => {
  try {
    console.log('🧹 Clearing all drawings...')

    let clearedCount = 0

    drawLayers.forEach((layer, index) => {
      try {
        if (layer.left && leftMap && leftMap.hasLayer(layer.left)) {
          leftMap.removeLayer(layer.left)
          clearedCount++
        }
        if (layer.right && rightMap && rightMap.hasLayer(layer.right)) {
          rightMap.removeLayer(layer.right)
        }
      } catch (err) {
        console.warn(`Failed to remove layer ${index}:`, err)
      }
    })
    drawLayers.length = 0

    clearSearchMarkers()

    tempDrawingMarkers.forEach((marker, index) => {
      try {
        if (marker.left && leftMap && leftMap.hasLayer(marker.left)) {
          leftMap.removeLayer(marker.left)
        }
        if (marker.right && rightMap && rightMap.hasLayer(marker.right)) {
          rightMap.removeLayer(marker.right)
        }
      } catch (err) {
        console.warn(`Failed to remove temp marker ${index}:`, err)
      }
    })
    tempDrawingMarkers = []

    clearImportedLayers()
    resetDrawing()

    drawingMode.value = null
    isDrawing = false
    searchQuery.value = ''

    emit('drawings-cleared', {
      clearedCount: clearedCount,
      timestamp: new Date().toISOString()
    })

    console.log(`✅ Successfully cleared ${clearedCount} drawings`)
    showTemporaryMessage(`All drawings cleared (${clearedCount} items)`, 'success')

  } catch (error) {
    console.error('Error clearing drawings:', error)
    showTemporaryMessage('Failed to clear some items', 'error')
  }
}

// Import/Export functionality
const handleFileImportWithAPI = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  loading.value = true
  loadingMessage.value = 'Importing and saving to database...'

  try {
    const allowedExtensions = ['.geojson', '.json']
    const isValidExtension = allowedExtensions.some(ext =>
      file.name.toLowerCase().endsWith(ext)
    )

    if (!isValidExtension) {
      throw new Error('Please select a valid GeoJSON file (.geojson or .json)')
    }

    if (file.size > 50 * 1024 * 1024) {
      throw new Error('File too large. Please select a file smaller than 50MB.')
    }

    const text = await file.text()
    let geojson

    try {
      geojson = JSON.parse(text)
    } catch (parseError) {
      throw new Error('Invalid JSON format. Please check your file syntax.')
    }

    if (!isValidGeoJSON(geojson)) {
      throw new Error('Invalid GeoJSON structure.')
    }

    const features = geojson.type === 'FeatureCollection' ? geojson.features : [geojson]
    clearImportedLayers()

    if (props.user?.id) {
      const cleanedFeatures = features
        .filter(feature => feature.geometry && feature.geometry.coordinates)
        .map(feature => {
          const props = feature.properties || {}
          return {
            ...feature,
            properties: {
              ...props,
              type: props.type || props.forest_type || 'unknown',
              source: props.source || 'imported',
              confidence_level: props.confidence_level || 'medium'
            }
          }
        })

      if (cleanedFeatures.length > 100) {
        throw new Error('Too many features. Please limit imports to 100 features at a time.')
      }

      const response = await apiService.bulkImport({
        type: 'FeatureCollection',
        features: cleanedFeatures
      })

      if (response.status === 'success') {
        const { imported, imported_count, error_count } = response.data

        imported.forEach((savedPolygon, index) => {
          try {
            const forestType = savedPolygon.properties.type || 'unknown'
            const layerStyle = getStyleForForestType(forestType)

            const leftLayer = L.geoJSON(savedPolygon, {
              style: layerStyle,
              onEachFeature: (feat, layer) => {
                const popupContent = createSavedPolygonPopup(feat, feat.properties.area_hectares || 0, 'imported')
                layer.bindPopup(popupContent, { maxWidth: 320 })
              }
            })

            leftMap.addLayer(leftLayer)

            let rightLayer = null
            if (rightMap) {
              rightLayer = L.geoJSON(savedPolygon, {
                style: layerStyle,
                onEachFeature: (feat, layer) => {
                  const popupContent = createSavedPolygonPopup(feat, feat.properties.area_hectares || 0, 'imported')
                  layer.bindPopup(popupContent, { maxWidth: 320 })
                }
              })
              rightMap.addLayer(rightLayer)
            }

            importedLayers.push({
              left: leftLayer,
              right: rightLayer,
              feature: savedPolygon,
              type: 'imported',
              filename: file.name,
              savedToDatabase: true
            })

            savedPolygons.value.push(savedPolygon)

          } catch (layerError) {
            console.warn(`Failed to add imported feature ${index + 1}:`, layerError)
          }
        })

        showTemporaryMessage(`Successfully imported ${imported_count} features`, 'success', 4000)
        if (error_count > 0) {
          showTemporaryMessage(`${error_count} features failed to import`, 'error', 3000)
        }
      }
    } else {
      let importedCount = 0

      features.forEach((feature, index) => {
        try {
          const forestType = feature.properties?.type || 'unknown'
          const layerStyle = getStyleForForestType(forestType)

          const leftLayer = L.geoJSON(feature, {
            style: layerStyle,
            onEachFeature: (feat, layer) => {
              const popupContent = createLocalPolygonPopup('imported', 0)
              layer.bindPopup(popupContent, { maxWidth: 300 })
            }
          })

          leftMap.addLayer(leftLayer)

          let rightLayer = null
          if (rightMap) {
            rightLayer = L.geoJSON(feature, {
              style: layerStyle,
              onEachFeature: (feat, layer) => {
                const popupContent = createLocalPolygonPopup('imported', 0)
                layer.bindPopup(popupContent, { maxWidth: 300 })
              }
            })
            rightMap.addLayer(rightLayer)
          }

          importedLayers.push({
            left: leftLayer,
            right: rightLayer,
            feature: feature,
            type: 'imported',
            filename: file.name,
            savedToDatabase: false
          })

          importedCount++

        } catch (featureError) {
          console.warn(`Failed to import feature ${index + 1}:`, featureError)
        }
      })

      showTemporaryMessage(`Successfully imported ${importedCount} features (local only)`, 'success', 4000)
    }

  } catch (error) {
    console.error('Import error:', error)
    showTemporaryMessage(`Import failed: ${error.message}`, 'error', 5000)
  } finally {
    loading.value = false
    event.target.value = ''
  }
}

const loadSavedPolygons = async () => {
  if (!props.user?.id) {
    showTemporaryMessage('Please login to load saved polygons', 'error')
    return
  }

  try {
    console.log('🔄 Loading saved polygons from database...')

    const response = await apiService.getPolygons({ user_id: props.user.id })

    if (response.status === 'success') {
      const polygons = response.data

      savedPolygons.value.forEach((polygon, index) => {
        try {
          const layerIndex = drawLayers.findIndex(layer => layer.uuid === polygon.properties.id)
          if (layerIndex !== -1) {
            const layer = drawLayers[layerIndex]
            if (layer.left && leftMap && leftMap.hasLayer(layer.left)) {
              leftMap.removeLayer(layer.left)
            }
            if (layer.right && rightMap && rightMap.hasLayer(layer.right)) {
              rightMap.removeLayer(layer.right)
            }
            drawLayers.splice(layerIndex, 1)
          }
        } catch (err) {
          console.warn(`Failed to remove saved polygon ${index}:`, err)
        }
      })

      savedPolygons.value = []

      polygons.forEach((polygon, index) => {
        try {
          const forestType = polygon.properties.type || 'unknown'
          const layerStyle = getStyleForForestType(forestType)

          const leftLayer = L.geoJSON(polygon, {
            style: layerStyle,
            onEachFeature: (feat, layer) => {
              const popupContent = createSavedPolygonPopup(feat, feat.properties.area_hectares, 'loaded')
              layer.bindPopup(popupContent, { maxWidth: 320 })
            }
          })

          leftMap.addLayer(leftLayer)

          let rightLayer = null
          if (rightMap) {
            rightLayer = L.geoJSON(polygon, {
              style: layerStyle,
              onEachFeature: (feat, layer) => {
                const popupContent = createSavedPolygonPopup(feat, feat.properties.area_hectares, 'loaded')
                layer.bindPopup(popupContent, { maxWidth: 320 })
              }
            })
            rightMap.addLayer(rightLayer)
          }

          savedPolygons.value.push(polygon)

          drawLayers.push({
            id: polygon.properties.id,
            uuid: polygon.properties.id,
            left: leftLayer,
            right: rightLayer,
            type: 'polygon',
            area: polygon.properties.area_hectares || 0,
            forestType: polygon.properties.type,
            savedToDatabase: true,
            createdAt: polygon.properties.created_at
          })

        } catch (layerError) {
          console.warn(`Failed to load polygon ${index + 1}:`, layerError)
        }
      })

      if (polygons.length > 0) {
        showTemporaryMessage(`Loaded ${polygons.length} saved polygons`, 'success')
      } else {
        showTemporaryMessage('No saved polygons found', 'info')
      }
    }
  } catch (error) {
    console.error('Error loading saved polygons:', error)
    showTemporaryMessage('Failed to load saved polygons', 'error')
  }
}

const exportDataWithAPI = async () => {
  try {
    console.log('💾 Starting enhanced export...')

    if (props.user?.id) {
      showTemporaryMessage('Preparing database export...', 'info')

      const response = await apiService.exportPolygons({ user_id: props.user.id })

      if (response.status === 'success') {
        const exportGeoJSON = response.data

        if (!exportGeoJSON.features || exportGeoJSON.features.length === 0) {
          showTemporaryMessage('No data to export from database', 'error')
          return
        }

        const dataStr = JSON.stringify(exportGeoJSON, null, 2)
        const dataBlob = new Blob([dataStr], { type: 'application/geo+json' })
        const url = URL.createObjectURL(dataBlob)

        const timestamp = new Date().toISOString().split('T')[0]
        const filename = `forest_monitoring_db_export_${timestamp}_${exportGeoJSON.features.length}features.geojson`

        const link = document.createElement('a')
        link.href = url
        link.download = filename
        link.style.display = 'none'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)

        emit('data-exported', {
          filename: filename,
          totalFeatures: exportGeoJSON.features.length,
          totalAreaHectares: parseFloat(exportGeoJSON.metadata.total_area_hectares),
          source: 'database',
          timestamp: new Date().toISOString()
        })

        const successMsg = `Database export completed: ${exportGeoJSON.features.length} features (${exportGeoJSON.metadata.total_area_hectares} ha)`
        showTemporaryMessage(successMsg, 'success', 4000)

      } else {
        throw new Error('Export failed on server')
      }
    } else {
      exportLocalData()
    }

  } catch (error) {
    console.error('Export error:', error)
    showTemporaryMessage(`Export failed: ${error.message}`, 'error')

    if (props.user?.id) {
      showTemporaryMessage('Falling back to local export...', 'info')
      exportLocalData()
    }
  }
}

const exportLocalData = () => {
  try {
    console.log('💾 Starting local data export...')

    const polygonFeatures = drawLayers.map((layer, index) => {
      try {
        const geoJsonFeature = convertToGeoJSON(layer)
        if (!geoJsonFeature) {
          throw new Error(`Failed to convert polygon ${index + 1} to GeoJSON`)
        }
        return geoJsonFeature
      } catch (error) {
        console.warn(`Skipping invalid polygon ${index + 1}:`, error)
        return null
      }
    }).filter(feature => feature !== null)

    const importedFeatures = importedLayers.map((layer, index) => {
      try {
        const feature = { ...layer.feature }
        if (!feature.properties) feature.properties = {}

        feature.properties._imported = true
        feature.properties._import_filename = layer.filename || 'unknown'
        feature.properties._import_timestamp = new Date().toISOString()

        return feature
      } catch (error) {
        console.warn(`Skipping invalid imported feature ${index + 1}:`, error)
        return null
      }
    }).filter(feature => feature !== null)

    const allFeatures = [...polygonFeatures, ...importedFeatures]

    if (allFeatures.length === 0) {
      showTemporaryMessage('No data to export', 'error')
      return
    }

    const exportGeoJSON = {
      type: 'FeatureCollection',
      metadata: {
        title: 'Forest Monitoring Data Export',
        description: 'Exported from Forest Monitoring Application',
        export_timestamp: new Date().toISOString(),
        total_features: allFeatures.length,
        drawn_polygons: polygonFeatures.length,
        imported_features: importedFeatures.length,
        total_area_hectares: polygonFeatures.reduce((sum, feature) =>
          sum + (feature.properties.area_hectares || 0), 0
        ).toFixed(2),
        coordinate_system: 'WGS84 (EPSG:4326)',
        application: 'Forest Monitoring Vue.js Application'
      },
      features: allFeatures
    }

    const dataStr = JSON.stringify(exportGeoJSON, null, 2)
    const dataBlob = new Blob([dataStr], { type: 'application/json' })
    const url = URL.createObjectURL(dataBlob)

    const timestamp = new Date().toISOString().split('T')[0]
    const filename = `forest_monitoring_export_${timestamp}_${allFeatures.length}features.geojson`

    const link = document.createElement('a')
    link.href = url
    link.download = filename
    link.style.display = 'none'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)

    emit('data-exported', {
      filename: filename,
      totalFeatures: allFeatures.length,
      drawnPolygons: polygonFeatures.length,
      importedFeatures: importedFeatures.length,
      totalAreaHectares: parseFloat(exportGeoJSON.metadata.total_area_hectares),
      timestamp: new Date().toISOString()
    })

    const successMsg = `Data exported: ${allFeatures.length} features (${exportGeoJSON.metadata.total_area_hectares} ha)`
    console.log(`✅ ${successMsg}`)
    showTemporaryMessage(successMsg, 'success', 4000)

  } catch (error) {
    console.error('Export error:', error)
    showTemporaryMessage(`Export failed: ${error.message}`, 'error')
  }
}

// Forest layer functionality
const toggleForestLayer = (layerKey) => {
  const layer = forestLayers.value.find(l => l.key === layerKey)
  if (!layer) {
    console.error(`Layer ${layerKey} not found`)
    return
  }

  try {
    layer.visible = !layer.visible

    if (layer.visible) {
      if (layer.layerGroup) {
        if (leftMap) leftMap.addLayer(layer.layerGroup)
        if (rightMap) rightMap.addLayer(layer.layerGroup)
        console.log(`✅ Layer "${layer.name}" enabled`)
        showTemporaryMessage(`${layer.name} layer enabled`, 'success')
      } else {
        initializeSpecificForestLayer(layer)
        console.log(`🔄 Initializing layer "${layer.name}"`)
      }
    } else {
      if (layer.layerGroup) {
        if (leftMap && leftMap.hasLayer(layer.layerGroup)) {
          leftMap.removeLayer(layer.layerGroup)
        }
        if (rightMap && rightMap.hasLayer(layer.layerGroup)) {
          rightMap.removeLayer(layer.layerGroup)
        }
        console.log(`❌ Layer "${layer.name}" disabled`)
        showTemporaryMessage(`${layer.name} layer disabled`, 'info')
      }
    }

    if (layer.visible && layer.layerGroup) {
      updateLayerOpacity(layerKey, layer.opacity)
    }

    emit('layer-toggled', {
      layerKey,
      layerName: layer.name,
      visible: layer.visible,
      timestamp: new Date().toISOString()
    })

  } catch (error) {
    console.error(`Error toggling layer ${layerKey}:`, error)
    showTemporaryMessage(`Failed to toggle ${layer.name}`, 'error')
    layer.visible = !layer.visible
  }
}

const updateLayerOpacity = (layerKey, opacity) => {
  const layer = forestLayers.value.find(l => l.key === layerKey)
  if (!layer || !layer.layerGroup) return

  try {
    const opacityValue = parseInt(opacity) / 100

    layer.layerGroup.eachLayer(geoLayer => {
      if (geoLayer.setStyle) {
        geoLayer.setStyle({
          fillOpacity: opacityValue * 0.6,
          opacity: Math.max(0.3, opacityValue)
        })
      }
    })

    layer.opacity = opacity
    console.log(`🎨 Updated ${layer.name} opacity to ${opacity}%`)

  } catch (error) {
    console.error(`Error updating opacity for ${layerKey}:`, error)
  }
}

const initializeSpecificForestLayer = (layer) => {
  try {
    const layerGroup = L.layerGroup()
    layer.layerGroup = layerGroup

    const sampleData = generateSampleForestData(layer.key)

    sampleData.forEach((feature, index) => {
      try {
        const geoLayer = L.geoJSON(feature, {
          style: {
            color: layer.color,
            weight: 2,
            opacity: 0.8,
            fillColor: layer.color,
            fillOpacity: (layer.opacity / 100) * 0.6
          },
          onEachFeature: (feat, geoLayerInstance) => {
            const popupContent = `
              <div style="font-family: sans-serif; text-align: center;">
                <strong>${layer.name}</strong><br>
                <span style="color: ${layer.color};">●</span> Sample Area ${index + 1}
              </div>
            `
            geoLayerInstance.bindPopup(popupContent)
          }
        })
        layerGroup.addLayer(geoLayer)
      } catch (featureError) {
        console.warn(`Failed to add feature ${index} to ${layer.key}:`, featureError)
      }
    })

    if (layer.visible) {
      if (leftMap) leftMap.addLayer(layerGroup)
      if (rightMap) rightMap.addLayer(layerGroup)
    }

    console.log(`✅ Initialized layer "${layer.name}" with ${sampleData.length} features`)

  } catch (error) {
    console.error(`Failed to initialize layer ${layer.key}:`, error)
    layer.visible = false
  }
}

const initializeForestLayers = () => {
  forestLayers.value.forEach(layer => {
    initializeSpecificForestLayer(layer)
  })
}

const generateSampleForestData = (layerKey) => {
  const baseCoords = [102.0, 4.4]
  const offsetMap = {
    'deforestation': [0, 0],
    'regrowth': [0.1, 0],
    'primaryForest': [0, 0.1],
    'disturbedForest': [0.1, 0.1]
  }

  const offset = offsetMap[layerKey] || [0, 0]

  return [{
    type: "Feature",
    properties: {
      type: layerKey,
      name: `Sample ${layerKey} area`,
      area: "50 hectares"
    },
    geometry: {
      type: "Polygon",
      coordinates: [[
        [baseCoords[0] + offset[0], baseCoords[1] + offset[1]],
        [baseCoords[0] + offset[0] + 0.05, baseCoords[1] + offset[1]],
        [baseCoords[0] + offset[0] + 0.05, baseCoords[1] + offset[1] + 0.05],
        [baseCoords[0] + offset[0], baseCoords[1] + offset[1] + 0.05],
        [baseCoords[0] + offset[0], baseCoords[1] + offset[1]]
      ]]
    }
  }]
}

// Control functions
const toggleOverlay = () => {
  overlayOpen.value = !overlayOpen.value
}

const toggleMapSync = () => {
  mapsSynced.value = !mapsSynced.value

  if (mapsSynced.value && leftMap && rightMap) {
    syncMaps(leftMap, rightMap)
  }

  showTemporaryMessage(
    mapsSynced.value ? 'Map synchronization enabled' : 'Map synchronization disabled',
    'info'
  )
}

const importData = () => {
  fileInput.value.click()
}

const measureArea = () => {
  if (drawLayers.length === 0) {
    showTemporaryMessage('No polygons to measure', 'error')
    return
  }

  const totalArea = drawLayers.reduce((sum, layer) => sum + parseFloat(layer.area), 0)
  const largestPolygon = drawLayers.reduce((max, layer) =>
    layer.area > max.area ? layer : max, drawLayers[0])

  showTemporaryMessage(
    `Total area: ${totalArea.toFixed(2)} ha • Largest: ${largestPolygon.area.toFixed(2)} ha • Count: ${drawLayers.length}`,
    'info',
    5000
  )
}

const generateReport = () => {
  const report = {
    total_polygons: drawLayers.length,
    total_area_hectares: drawLayers.reduce((sum, layer) => sum + parseFloat(layer.area), 0),
    largest_polygon_hectares: drawLayers.length > 0 ? Math.max(...drawLayers.map(l => l.area)) : 0,
    smallest_polygon_hectares: drawLayers.length > 0 ? Math.min(...drawLayers.map(l => l.area)) : 0,
    average_polygon_hectares: drawLayers.length > 0 ?
      drawLayers.reduce((sum, layer) => sum + parseFloat(layer.area), 0) / drawLayers.length : 0,
    imported_features: importedLayers.length,
    active_forest_layers: forestLayers.value.filter(l => l.visible).length,
    generated_at: new Date().toISOString()
  }

  console.log('📊 Forest Analysis Report:', report)

  const reportMsg = `Report: ${report.total_polygons} polygons, ${report.total_area_hectares.toFixed(2)} ha total, avg ${report.average_polygon_hectares.toFixed(2)} ha`
  showTemporaryMessage(reportMsg, 'info', 6000)

  emit('report-generated', report)
}

const exportToDatabase = async () => {
  if (!props.user) {
    showTemporaryMessage('User authentication required for database save', 'error')
    return
  }

  if (drawLayers.length === 0) {
    showTemporaryMessage('No polygons to save to database', 'error')
    return
  }

  try {
    loading.value = true
    loadingMessage.value = 'Saving to database...'

    const polygonsForDatabase = drawLayers.map(layer => ({
      geometry: convertToGeoJSON(layer)?.geometry,
      type: 'primary',
      height_category: null,
      canopy_density: null,
      source: 'manual',
      name: `Polygon ${new Date().toLocaleDateString()}`,
      area_hectares: layer.area,
      additional_properties: {
        created_via: 'web_interface',
        points_count: layer.points?.length,
        centroid: layer.centroid
      }
    })).filter(polygon => polygon.geometry)

    if (polygonsForDatabase.length === 0) {
      throw new Error('No valid polygons to save')
    }

    emit('save-to-database', {
      polygons: polygonsForDatabase,
      user_id: props.user.id,
      total_count: polygonsForDatabase.length,
      total_area: polygonsForDatabase.reduce((sum, p) => sum + p.area_hectares, 0)
    })

    showTemporaryMessage(`Preparing to save ${polygonsForDatabase.length} polygons to database...`, 'info')

  } catch (error) {
    console.error('Database export error:', error)
    showTemporaryMessage(`Database save failed: ${error.message}`, 'error')
  } finally {
    loading.value = false
  }
}
const debugAuth = () => {
  console.log('🔍 Auth Debug Info:');
  console.log('localStorage auth_token:', localStorage.getItem('auth_token'));
  console.log('localStorage sanctum_token:', localStorage.getItem('sanctum_token'));
  console.log('User prop:', props.user);
  console.log('API Service authenticated:', apiService.isAuthenticated());

  // Check token format
  const token = apiService.getAuthToken();
  if (token) {
    console.log('Token length:', token.length);
    console.log('Token starts with:', token.substring(0, 20) + '...');

    // Decode JWT payload if it's a JWT token
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      console.log('Token payload:', payload);
      console.log('Token expires:', new Date(payload.exp * 1000));
    } catch (e) {
      console.log('Not a JWT token or invalid format');
    }
  }
};
// Keyboard shortcuts and lifecycle
onMounted(() => {
  debugAuth();
  nextTick(() => {
    initializeMaps()
    initializeForestLayers()
  })

  const handleKeydown = (e) => {
    if (e.key === 'Escape') {
      if (isDrawing) {
        resetDrawing()
        drawingMode.value = null
        isDrawing = false
        showTemporaryMessage('Drawing cancelled', 'info')
      }
    }
    else if (e.key === 'Enter' && isDrawing && polygonPoints.length >= 3) {
      e.preventDefault()
      finishPolygon()
    }
    else if ((e.key === 'Delete' || e.key === 'Backspace') && isDrawing && polygonPoints.length > 0) {
      e.preventDefault()

      polygonPoints.pop()

      if (tempDrawingMarkers.length > 0) {
        const lastMarker = tempDrawingMarkers.pop()
        if (lastMarker.left && leftMap && leftMap.hasLayer(lastMarker.left)) {
          leftMap.removeLayer(lastMarker.left)
        }
        if (lastMarker.right && rightMap && rightMap.hasLayer(lastMarker.right)) {
          rightMap.removeLayer(lastMarker.right)
        }
      }

      updateTempPolygon()
      showTemporaryMessage(`Point removed. ${polygonPoints.length} points remaining`, 'info')
    }
    else if (e.ctrlKey && e.key === 'e') {
      e.preventDefault()
      exportDataWithAPI()
    }
    else if (e.ctrlKey && e.key === 'i') {
      e.preventDefault()
      importData()
    }
    else if (e.ctrlKey && e.key === 'Delete') {
      e.preventDefault()
      clearAllDrawings()
    }
  }

  document.addEventListener('keydown', handleKeydown)

  if (props.user?.id) {
    setTimeout(() => {
      loadSavedPolygons()
    }, 1000)
  }

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
    if (typeof window !== 'undefined') {
      delete window.updateSavedPolygon
      delete window.deleteSavedPolygon
    }
    if (leftMap) {
      leftMap.remove()
      leftMap = null
    }
    if (rightMap) {
      rightMap.remove()
      rightMap = null
    }
  })
})
</script>

<style scoped>
@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
  from { transform: translateX(0); opacity: 1; }
  to { transform: translateX(100%); opacity: 0; }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.map-container {
  position: relative;
  width: 100%;
  height: 100%;
  background: #f5f7fa;
}

.map-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(15px);
  padding: 12px 30px;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  z-index: 1000;
  position: relative;
}

.controls {
  display: flex;
  gap: 20px;
  align-items: center;
  flex-wrap: wrap;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.control-group label {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.search-box, .layer-select {
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  transition: all 0.2s ease;
  background: white;
  min-width: 140px;
}

.search-box:focus, .layer-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-btn {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  padding: 9px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
  margin-top: 16px;
  font-size: 13px;
}

.search-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.search-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.map-panels {
  position: relative;
  height: calc(100vh - 82px);
  overflow: hidden;
}

.map-panel {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 100%;
  transition: clip-path 0.3s ease;
}

.left-panel {
  left: 0;
  z-index: 1;
}

.right-panel {
  right: 0;
  z-index: 2;
}

.single-panel {
  clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%) !important;
}

.map {
  width: 100%;
  height: 100%;
}

.panel-label {
  position: absolute;
  top: 15px;
  left: 15px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 6px 14px;
  border-radius: 16px;
  font-weight: 500;
  color: #374151;
  z-index: 1000;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  font-size: 13px;
}

.coordinates {
  position: absolute;
  bottom: 15px;
  right: 15px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 6px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-family: 'Courier New', monospace;
  z-index: 1000;
}

.slider-container {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 3px;
  background: rgba(255, 255, 255, 0.8);
  z-index: 1002;
  cursor: ew-resize;
  transition: all 0.3s ease;
  transform: translateX(-50%);
}

.slider-container:hover {
  width: 5px;
  background: rgba(59, 130, 246, 0.8);
}

.slider-handle {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: 2px solid white;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  cursor: ew-resize;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0;
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.slider-toggle {
  position: absolute;
  top: 15px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 2px solid #e2e8f0;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  z-index: 1003;
  transition: all 0.3s ease;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  font-size: 12px;
  font-weight: 500;
  color: #374151;
}

.slider-toggle.active {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border-color: #3b82f6;
}

.overlay-control {
  position: absolute;
  top: 15px;
  right: 80px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(15px);
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  z-index: 1001;
  min-width: 300px;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
}

.overlay-header {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.overlay-title {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.overlay-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  color: #6b7280;
  transition: all 0.2s ease;
}

.overlay-content {
  padding: 16px;
  display: none;
}

.overlay-content.open {
  display: block;
}

.overlay-section {
  margin-bottom: 20px;
}

.section-title {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  padding-bottom: 4px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.layer-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.layer-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.layer-color {
  width: 16px;
  height: 16px;
  border-radius: 3px;
  border: 1px solid rgba(0, 0, 0, 0.2);
}

.layer-name {
  font-size: 13px;
  color: #374151;
  font-weight: 500;
}

.layer-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.opacity-slider {
  width: 60px;
  height: 4px;
  border-radius: 2px;
  background: #e5e7eb;
  outline: none;
}

.layer-toggle {
  position: relative;
  width: 36px;
  height: 20px;
  background: #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.layer-toggle.active {
  background: #3b82f6;
}

.layer-toggle::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  background: white;
  border-radius: 50%;
  transition: transform 0.2s ease;
}

.layer-toggle.active::after {
  transform: translateX(16px);
}

.drawing-tools {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tool-btn {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
  text-align: left;
}

.tool-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3);
}

.tool-btn.active {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.export-btn {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important;
}

.export-btn:hover {
  box-shadow: 0 3px 10px rgba(139, 92, 246, 0.3) !important;
}

.import-btn {
  background: linear-gradient(135deg, #f59e0b, #d97706) !important;
}

.import-btn:hover {
  box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3) !important;
}

.analysis-btn {
  background: linear-gradient(135deg, #06b6d4, #0891b2) !important;
}

.analysis-btn:hover {
  box-shadow: 0 3px 10px rgba(6, 182, 212, 0.3) !important;
}

.data-summary {
  background: #f8fafc;
  border-radius: 6px;
  padding: 12px;
  border: 1px solid #e2e8f0;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
  border-bottom: 1px solid #e2e8f0;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.summary-value {
  font-size: 12px;
  color: #374151;
  font-weight: 600;
}

.sync-toggle {
  position: absolute;
  top: 15px;
  right: 15px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 2px solid #e2e8f0;
  padding: 10px;
  border-radius: 50%;
  cursor: pointer;
  z-index: 1002;
  transition: all 0.3s ease;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.sync-toggle.active {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border-color: #3b82f6;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-text {
  color: white;
  margin-top: 16px;
  font-size: 14px;
  font-weight: 500;
}

.drawn-polygon {
  cursor: pointer;
}

.imported-geojson {
  cursor: pointer;
}

.imported-geojson:hover {
  opacity: 0.8;
}

/* Enhanced popup styles */
:global(.custom-polygon-popup .leaflet-popup-content-wrapper) {
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

:global(.custom-polygon-popup .leaflet-popup-tip) {
  background: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.api-status {
  background: #f8fafc;
  border-radius: 6px;
  padding: 12px;
  border: 1px solid #e2e8f0;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
}

.status-item.loading .status-indicator {
  background: #f59e0b;
  animation: pulse 2s infinite;
}

.status-item.error .status-indicator {
  background: #ef4444;
}

.status-text {
  font-size: 12px;
  color: #374151;
  font-weight: 500;
}

.error-message {
  font-size: 11px;
  color: #ef4444;
  margin-top: 4px;
  font-style: italic;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

</style>
