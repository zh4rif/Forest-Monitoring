<template>
  <div class="polygon-form-overlay" @click.self="$emit('close')">
    <div class="polygon-form">
      <div class="form-header">
        <div class="form-title">Polygon Information</div>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="savePolygon">
        <div class="form-group">
          <label for="objectId">Object ID</label>
          <input
            type="number"
            id="objectId"
            v-model.number="form.objectId"
            required
            readonly
          />
        </div>

        <div class="form-group">
          <label for="licenseNo">License No. *</label>
          <input
            type="text"
            id="licenseNo"
            v-model="form.licenseNo"
            required
            placeholder="e.g., LIC-2024-001"
          />
        </div>

        <div class="form-group">
          <label for="smallholderName">Smallholder Name *</label>
          <input
            type="text"
            id="smallholderName"
            v-model="form.smallholderName"
            required
            placeholder="Full name of smallholder"
          />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="state">State *</label>
            <select id="state" v-model="form.state" required>
              <option value="">Select State</option>
              <option value="PAHANG">Pahang</option>
              <option value="PERAK">Perak</option>
              <option value="SELANGOR">Selangor</option>
              <option value="JOHOR">Johor</option>
              <option value="KEDAH">Kedah</option>
              <option value="KELANTAN">Kelantan</option>
              <option value="MELAKA">Melaka</option>
              <option value="SABAH">Sabah</option>
              <option value="SARAWAK">Sarawak</option>
              <option value="TERENGGANU">Terengganu</option>
            </select>
          </div>

          <div class="form-group">
            <label for="district">District *</label>
            <input
              type="text"
              id="district"
              v-model="form.district"
              required
              placeholder="District name"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="subdistrict">Subdistrict</label>
          <input
            type="text"
            id="subdistrict"
            v-model="form.subdistrict"
            placeholder="Subdistrict/Mukim"
          />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="spocName">SPOC Name</label>
            <input
              type="text"
              id="spocName"
              v-model="form.spocName"
              placeholder="Single Point of Contact"
            />
          </div>

          <div class="form-group">
            <label for="spocCode">SPOC Code</label>
            <input
              type="text"
              id="spocCode"
              v-model="form.spocCode"
              placeholder="SPOC identifier code"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="lotNo">Lot No.</label>
          <input
            type="text"
            id="lotNo"
            v-model="form.lotNo"
            placeholder="Lot number"
          />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="certifiedArea">Certified Area (HA) *</label>
            <input
              type="number"
              id="certifiedArea"
              v-model.number="form.certifiedArea"
              step="0.001"
              required
              placeholder="0.000"
            />
          </div>

          <div class="form-group">
            <label for="plantedArea">Planted Area (HA) *</label>
            <input
              type="number"
              id="plantedArea"
              v-model.number="form.plantedArea"
              step="0.001"
              required
              placeholder="0.000"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="mspoCertification">MSPO Certification</label>
          <input
            type="text"
            id="mspoCertification"
            v-model="form.mspoCertification"
            placeholder="MSPO certificate number"
          />
        </div>

        <div class="form-group">
          <label for="landTitle">Land Title</label>
          <input
            type="text"
            id="landTitle"
            v-model="form.landTitle"
            placeholder="Land title/grant number"
          />
        </div>

        <!-- Additional reforestation-specific fields -->
        <div class="form-section">
          <h4>Reforestation Data</h4>

          <div class="form-row">
            <div class="form-group">
              <label for="treeHeight">Tree Height (m)</label>
              <input
                type="number"
                id="treeHeight"
                v-model.number="form.treeHeight"
                step="0.1"
                min="0"
                placeholder="e.g., 5.5"
              />
            </div>

            <div class="form-group">
              <label for="canopyCover">Canopy Cover (%)</label>
              <input
                type="number"
                id="canopyCover"
                v-model.number="form.canopyCover"
                step="0.1"
                min="0"
                max="100"
                placeholder="e.g., 75.5"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="ndviValue">NDVI Value</label>
              <input
                type="number"
                id="ndviValue"
                v-model.number="form.ndviValue"
                step="0.001"
                min="-1"
                max="1"
                placeholder="e.g., 0.745"
              />
            </div>

            <div class="form-group">
              <label for="saviValue">SAVI Value</label>
              <input
                type="number"
                id="saviValue"
                v-model.number="form.saviValue"
                step="0.001"
                min="-1"
                max="1"
                placeholder="e.g., 0.625"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="classification">Classification</label>
            <select id="classification" v-model="form.classification">
              <option value="">Auto-classify</option>
              <option value="bare_land">Bare Land</option>
              <option value="new_growth">New Growth</option>
              <option value="regrowth">Regrowth</option>
              <option value="plantation">Plantation</option>
              <option value="low_vegetation">Low Vegetation</option>
              <option value="forest">Forest</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="notes">Notes</label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="3"
            placeholder="Additional observations or comments..."
          ></textarea>
        </div>

        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Polygon' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

const props = defineProps({
  polygons: Array,  // ✅ This is correct
  user: Object
})
const emit = defineEmits(['close', 'saved'])

const saving = ref(false)

const form = reactive({
  objectId: 0,
  licenseNo: '',
  smallholderName: '',
  state: '',
  district: '',
  subdistrict: '',
  spocName: '',
  spocCode: '',
  lotNo: '',
  certifiedArea: 0,
  plantedArea: 0,
  mspoCertification: '',
  landTitle: '',
  treeHeight: null,
  canopyCover: null,
  ndviValue: null,
  saviValue: null,
  classification: '',
  notes: '',
  geometry: null
})

// Auto-generate object ID
const generateObjectId = () => {
  return Date.now() % 1000000 // Simple ID generation
}

watch(() => props.polygon, (newPolygon) => {
  if (newPolygon) {
    Object.assign(form, {
      objectId: newPolygon.objectId || generateObjectId(),
      licenseNo: newPolygon.licenseNo || '',
      smallholderName: newPolygon.smallholderName || '',
      state: newPolygon.state || '',
      district: newPolygon.district || '',
      subdistrict: newPolygon.subdistrict || '',
      spocName: newPolygon.spocName || '',
      spocCode: newPolygon.spocCode || '',
      lotNo: newPolygon.lotNo || '',
      certifiedArea: newPolygon.certifiedArea || newPolygon.area_hectares || 0,
      plantedArea: newPolygon.plantedArea || newPolygon.area_hectares || 0,
      mspoCertification: newPolygon.mspoCertification || '',
      landTitle: newPolygon.landTitle || '',
      treeHeight: newPolygon.treeHeight || newPolygon.tree_height,
      canopyCover: newPolygon.canopyCover || newPolygon.canopy_cover_percentage,
      ndviValue: newPolygon.ndviValue || newPolygon.ndvi_value,
      saviValue: newPolygon.saviValue || newPolygon.savi_value,
      classification: newPolygon.classification || '',
      notes: newPolygon.notes || '',
      geometry: newPolygon.geometry
    })
  } else {
    // Reset form for new polygon
    form.objectId = generateObjectId()
  }
}, { immediate: true })

const savePolygon = async () => {
  saving.value = true

  try {
    // Validate required fields
    const requiredFields = ['licenseNo', 'smallholderName', 'state', 'district', 'certifiedArea', 'plantedArea']
    const missingFields = requiredFields.filter(field => !form[field])

    if (missingFields.length > 0) {
      alert(`Please fill in required fields: ${missingFields.join(', ')}`)
      return
    }

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))

    console.log('Saving polygon data:', form)

    emit('saved', { ...form })
  } catch (error) {
    alert('Error saving polygon: ' + error.message)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.polygon-form-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 3000;
}

.polygon-form {
  background: white;
  border-radius: 8px;
  width: 600px;
  max-width: 90vw;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  background: #f8f9fa;
  border-radius: 8px 8px 0 0;
}

.form-title {
  font-size: 18px;
  font-weight: 600;
  color: #374151;
}

.close-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #6b7280;
  padding: 4px;
  border-radius: 4px;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

form {
  padding: 20px;
}

.form-section {
  margin: 20px 0;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.form-section h4 {
  margin: 0 0 15px 0;
  color: #374151;
  font-size: 16px;
  font-weight: 600;
}

.form-group {
  margin-bottom: 15px;
}

.form-row {
  display: flex;
  gap: 15px;
}

.form-row .form-group {
  flex: 1;
}

label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

input, select, textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s ease;
}

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

input[readonly] {
  background: #f9fafb;
  color: #6b7280;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.btn-secondary {
  background: #6b7280;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s ease;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  padding: 8px 16px;
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
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}
</style>
