<template>
  <header class="app-header">
    <div class="header-content">
      <div class="header-left">
        <h1 class="app-title">🌱 MSPO Forest Monitor</h1>
      </div>

      <div class="header-right" v-if="user">
        <div class="user-info">
          <span class="user-name">{{ user.name }}</span>
          <span class="user-role" :class="roleClass">{{ formatRole(user.role) }}</span>
        </div>

        <div class="header-actions">
          <button @click="showReports" class="btn btn-secondary">
            📊 Reports
          </button>
          <button @click="$emit('logout')" class="btn btn-outline">
            🚪 Logout
          </button>
        </div>
      </div>

      <div class="header-right" v-else>
        <div class="login-prompt">
          <span>Please login to access the system</span>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  polygons: Array,  // ✅ This is correct
  user: Object
})

const emit = defineEmits(['logout'])

const roleClass = computed(() => {
  if (!props.user?.role) return ''
  return `role-${props.user.role.toLowerCase()}`
})

const formatRole = (role) => {
  const roleMap = {
    'MAPO': 'MAPO Officer',
    'FGV': 'FGV Staff',
    'PLANTATION_OWNER': 'Plantation Owner'
  }
  return roleMap[role] || role
}

const showReports = () => {
  // This will be handled by parent component
  window.dispatchEvent(new CustomEvent('show-reports'))
}
</script>

<style scoped>
.app-header {
  background: linear-gradient(135deg, #2d5016 0%, #4a7c59 100%);
  color: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
}

.app-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.user-name {
  font-weight: 500;
  font-size: 0.9rem;
}

.user-role {
  font-size: 0.75rem;
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
  margin-top: 0.2rem;
}

.role-mapo {
  background: rgba(52, 152, 219, 0.3);
  border: 1px solid rgba(52, 152, 219, 0.5);
}

.role-fgv {
  background: rgba(46, 204, 113, 0.3);
  border: 1px solid rgba(46, 204, 113, 0.5);
}

.role-plantation_owner {
  background: rgba(241, 196, 15, 0.3);
  border: 1px solid rgba(241, 196, 15, 0.5);
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.btn-secondary {
  background: rgba(255,255,255,0.1);
  color: white;
  border: 1px solid rgba(255,255,255,0.2);
}

.btn-outline {
  background: transparent;
  color: white;
  border: 1px solid rgba(255,255,255,0.3);
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.login-prompt {
  color: rgba(255,255,255,0.8);
  font-style: italic;
}
</style>
