<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../services/api'
import { authState, clearAuthSession } from '../stores/auth'
import { companyState, setCompanies, setSelectedCompany } from '../stores/company'
import { themeMode, toggleTheme } from '../stores/theme'
import { apiBaseUrl } from '../config'

const router = useRouter()
const route = useRoute()
const isCollapsed = ref(false)
const mobileOpen = ref(false)
const companyLoading = ref(false)

const mainNavItems = computed(() => [
  { label: 'Dashboard', path: '/dashboard' },
  { label: 'Company', path: '/companies' },
  { label: 'Create Project', path: '/projects/create' },
  { label: 'Manage Project', path: '/projects/manage' },
  { label: 'Tender Deposit Money', path: '/tenders/create' },
])

const settingsNavItems = computed(() => {
  if (authState.user?.role !== 'superadmin') {
    return []
  }

  return [
    { label: 'Manage User', path: '/settings/users' },
  ]
})

const themeLabel = computed(() => (themeMode.value === 'dark' ? 'Light Mode' : 'Dark Mode'))
const companyOptions = computed(() => companyState.companies || [])

async function fetchCompaniesFallback() {
  const headers = {}
  if (authState.token) {
    headers.Authorization = `Bearer ${authState.token}`
  }
  if (companyState.selectedCompanyId) {
    headers['X-Company-Id'] = String(companyState.selectedCompanyId)
  }

  const response = await fetch(`${apiBaseUrl}/companies`, {
    headers,
  })

  const payload = await response.json()
  if (!response.ok) {
    throw new Error(payload.message || 'Failed to load companies.')
  }

  return payload
}

async function loadCompanies() {
  if (!authState.token) {
    return
  }

  companyLoading.value = true

  try {
    let data
    try {
      const response = await api.get('/companies')
      data = response.data
    } catch (_error) {
      data = await fetchCompaniesFallback()
    }

    setCompanies(data.companies || [])

    if (!companyState.selectedCompanyId && data.companies?.length) {
      setSelectedCompany(data.selected_company_id || data.companies[0].id)
    }
  } catch (_error) {
    setCompanies([])
  } finally {
    companyLoading.value = false
  }
}

async function logout() {
  try {
    await api.post('/logout')
  } catch (_error) {
  } finally {
    clearAuthSession()
    router.push('/auth')
  }
}

function toggleSidebar() {
  if (window.innerWidth <= 1100) {
    mobileOpen.value = !mobileOpen.value
    return
  }

  isCollapsed.value = !isCollapsed.value
}

function closeMobileSidebar() {
  mobileOpen.value = false
}

function isActive(path) {
  if (path.includes('#')) {
    return route.path === path.split('#')[0]
  }

  return route.path === path
}

function onCompanyChange(event) {
  setSelectedCompany(event.target.value)
  if (route.path !== '/dashboard') {
    router.push('/dashboard')
    return
  }
  window.location.reload()
}

onMounted(loadCompanies)
</script>

<template>
  <div :class="['shell', { collapsed: isCollapsed, 'mobile-open': mobileOpen }]">
    <button v-if="mobileOpen" class="mobile-backdrop" @click="closeMobileSidebar"></button>

    <aside class="sidebar">
      <div class="sidebar-top">
        <div class="brand-row">
          <div class="brand-mark">CM</div>
          <button class="ghost-btn icon-btn sidebar-close" @click="closeMobileSidebar">X</button>
        </div>

        <div class="brand-copy" v-show="!isCollapsed || mobileOpen">
          <h1>Construction Manager</h1>
          <p>Projects, tenders, finance and site control in one workspace.</p>
        </div>

        <div v-show="!isCollapsed || mobileOpen" class="company-switcher">
          <label>
            <span>Company</span>
            <select :value="companyState.selectedCompanyId || ''" @change="onCompanyChange">
              <option value="" disabled>{{ companyLoading ? 'Loading companies...' : 'Select company' }}</option>
              <option v-for="company in companyOptions" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </label>
        </div>

        <nav class="nav-list">
          <RouterLink
            v-for="item in mainNavItems"
            :key="item.path"
            :to="item.path"
            :class="['nav-link', { active: isActive(item.path) }]"
            @click="closeMobileSidebar"
          >
            <span class="nav-dot"></span>
            <span v-show="!isCollapsed || mobileOpen">{{ item.label }}</span>
          </RouterLink>
        </nav>

        <div v-if="settingsNavItems.length && (!isCollapsed || mobileOpen)" class="nav-section-title">
          Settings
        </div>

        <nav v-if="settingsNavItems.length" class="nav-list settings-nav-list">
          <RouterLink
            v-for="item in settingsNavItems"
            :key="item.path"
            :to="item.path"
            :class="['nav-link', 'sub-nav-link', { active: isActive(item.path) }]"
            @click="closeMobileSidebar"
          >
            <span class="nav-dot"></span>
            <span v-show="!isCollapsed || mobileOpen">{{ item.label }}</span>
          </RouterLink>
        </nav>
      </div>

      <div class="sidebar-footer">
        <button class="ghost-btn" @click="toggleTheme">
          <span v-if="isCollapsed && !mobileOpen">{{ themeMode === 'dark' ? 'L' : 'D' }}</span>
          <span v-else>{{ themeLabel }}</span>
        </button>
        <div class="user-chip" v-show="!isCollapsed || mobileOpen">
          <strong>{{ authState.user?.name }}</strong>
          <span>{{ authState.user?.role }}</span>
          <small>{{ authState.user?.company_name || 'No company' }}</small>
        </div>
        <button class="ghost-btn" @click="logout">
          <span v-if="isCollapsed && !mobileOpen">Back</span>
          <span v-else>Logout</span>
        </button>
      </div>
    </aside>

    <main class="content">
      <div class="content-topbar">
        <button class="ghost-btn icon-btn" @click="toggleSidebar">|||</button>
        <div class="topbar-copy">
          <strong>{{ authState.user?.role === 'superadmin' ? 'Superadmin View' : 'My Workspace' }}</strong>
          <span>{{ authState.user?.name }}</span>
        </div>
        <button class="ghost-btn topbar-theme-btn" @click="toggleTheme">{{ themeLabel }}</button>
      </div>
      <slot />
    </main>
  </div>
</template>