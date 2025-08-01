<template>
  <div class="report-panel-overlay" @click.self="$emit('close')">
    <div class="report-panel">
      <div class="panel-header">
        <h3>Reforestation Report</h3>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <div class="panel-content">
        <div class="filters">
          <div class="filter-group">
            <label for="start_date">Start Date</label>
            <input
              id="start_date"
              v-model="filters.start_date"
              type="date"
              class="form-control"
            />
          </div>

          <div class="filter-group">
            <label for="end_date">End Date</label>
            <input
              id="end_date"
              v-model="filters.end_date"
              type="date"
              class="form-control"
            />
          </div>

          <button @click="generateReport" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Generating...' : 'Generate Report' }}
          </button>
        </div>

        <div v-if="report" class="report-content">
          <div class="report-summary">
            <h4>Summary</h4>
            <div class="summary-stats">
              <div class="stat-card">
                <div class="stat-value">{{ report.total_area_hectares }}</div>
                <div class="stat-label">Total Area (HA)</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ report.total_polygons }}</div>
                <div class="stat-label">Total Polygons</div>
              </div>
            </div>
          </div>

          <div class="classifications">
            <h4>Land Classification Breakdown</h4>
            <div class="classification-stats">
              <div
                v-for="(data, classification) in report.classifications"
                :key="classification"
                class="classification-card"
              >
                <div class="classification-header">
                  <span
                    class="color-indicator"
                    :style="{ backgroundColor: getClassificationColor(classification) }"
                  ></span>
                  <span class="classification-name">
                    {{ formatClassificationName(classification) }}
                  </span>
                </div>
                <div class="classification-data">
                  <div class="percentage">{{ data.percentage }}%</div>
                  <div class="area">{{ data.area_hectares }} HA</div>
                  <div class="count">{{ data.count }} polygons</div>
                </div>
              </div>
            </div>
          </div>

          <div class="report-meta">
            <p><strong>Generated:</strong> {{ formatDate(report.generated_at) }}</p>
            <p><strong>Generated by:</strong> {{ report.generated_by }}</p>
          </div>

          <div class="report-actions">
            <button @click="exportReport" class="btn btn-secondary">
              Export PDF
            </button>
            <button @click="exportCSV" class="btn btn-secondary">
              Export CSV
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'

const props = defineProps({
  polygons: Array,  // ✅ This is correct
  user: Object
})
const emit = defineEmits(['close'])

const loading = ref(false)
const report = ref(null)

const filters = reactive({
  start_date: '',
  end_date: ''
})

const classificationColors = {
  bare_land: '#8B4513',
  new_growth: '#90EE90',
  regrowth: '#32CD32',
  plantation: '#228B22',
  low_vegetation: '#ADFF2F',
  forest: '#006400'
}

const generateReport = async () => {
  loading.value = true

  try {
    const params = {}
    if (filters.start_date) params.start_date = filters.start_date
    if (filters.end_date) params.end_date = filters.end_date

    const response = await axios.get('/api/reports', { params })
    report.value = response.data
  } catch (error) {
    alert('Error generating report: ' + error.message)
  } finally {
    loading.value = false
  }
}

const getClassificationColor = (classification) => {
  return classificationColors[classification] || '#ccc'
}

const formatClassificationName = (classification) => {
  return classification.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const exportReport = () => {
  // Implementation for PDF export
  alert('PDF export feature to be implemented')
}

const exportCSV = () => {
  if (!report.value) return

  const csvData = []
  csvData.push(['Classification', 'Area (HA)', 'Percentage', 'Count'])

  Object.entries(report.value.classifications).forEach(([classification, data]) => {
    csvData.push([
      formatClassificationName(classification),
      data.area_hectares,
      data.percentage,
      data.count
    ])
  })

  const csvContent = csvData.map(row => row.join(',')).join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)

  const link = document.createElement('a')
  link.href = url
  link.download = `reforestation_report_${new Date().toISOString().split('T')[0]}.csv`
  link.click()

  URL.revokeObjectURL(url)
}
</script>

<style scoped>
.report-panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
}

.report-panel {
  background: white;
  border-radius: 8px;
  width: 800px;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.panel-content {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.filters {
  display: flex;
  gap: 15px;
  align-items: end;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #eee;
}

.filter-group {
  flex: 1;
}

.filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary {
  background: #007cba;
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.summary-stats {
  display: flex;
  gap: 20px;
  margin: 15px 0;
}

.stat-card {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  flex: 1;
}

.stat-value {
  font-size: 2em;
  font-weight: bold;
  color: #007cba;
}

.stat-label {
  color: #6c757d;
  margin-top: 5px;
}

.classification-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 15px;
  margin: 15px 0;
}

.classification-card {
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 15px;
}

.classification-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.color-indicator {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 1px solid #ccc;
}

.classification-name {
  font-weight: 500;
}

.classification-data {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.percentage {
  font-size: 1.2em;
  font-weight: bold;
  color: #007cba;
}

.area, .count {
  font-size: 0.9em;
  color: #6c757d;
}

.report-meta {
  margin: 30px 0;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
  font-size: 0.9em;
}

.report-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}
</style>
