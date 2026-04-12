<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { authState, clearAuthSession } from '../stores/auth'
import { companyState, setCompanies, setSelectedCompany } from '../stores/company'
import { themeMode, toggleTheme } from '../stores/theme'

const router = useRouter()
const loading = ref(true)
const errorText = ref('')
const companies = ref([])

async function loadCompanies() {
  loading.value = true
  errorText.value = ''

  try {
    const { data } = await api.get('/companies')
    companies.value = data.companies || []
    setCompanies(companies.value)
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load companies.'
  } finally {
    loading.value = false
  }
}

function selectCompany(companyId) {
  setSelectedCompany(companyId)
  router.push('/dashboard')
}

function goToCompanyPage() {
  router.push('/companies')
}

function logout() {
  clearAuthSession()
  router.push('/auth')
}

onMounted(loadCompanies)
</script>

<template>
  <div class="auth-page auth-theme-page company-select-page">
    <button class="ghost-btn auth-theme-toggle" @click="toggleTheme">
      {{ themeMode === 'dark' ? 'Light Mode' : 'Dark Mode' }}
    </button>

    <section class="auth-hero">
      <span class="badge">Select Company</span>
      <h1>Choose company before loading the system.</h1>
      <p>
        Select the company you want to work with. After selection, dashboard, projects, tenders,
        and reports will load company-wise.
      </p>
    </section>

    <section class="auth-card company-select-card">
      <div class="section-head">
        <h3>My Companies</h3>
        <span>{{ authState.user?.name }}</span>
      </div>

      <p v-if="errorText" class="error-text">{{ errorText }}</p>
      <div v-if="loading" class="panel">Loading companies...</div>

      <div v-else class="entry-list company-select-list">
        <button
          v-for="company in companies"
          :key="company.id"
          class="project-item company-select-item"
          @click="selectCompany(company.id)"
        >
          <div>
            <strong>{{ company.name }}</strong>
            <p>{{ company.description || 'No description' }}</p>
            <p>Projects: {{ company.project_count }}</p>
            <p>Tenders: {{ company.tender_count }}</p>
          </div>
          <div class="project-meta">
            <span class="status-pill running">Choose</span>
            <!-- <small>Users: {{ company.user_count }}</small> -->
          </div>
        </button>

        <article v-if="companies.length === 0" class="entry-item">
          <strong>No companies available</strong>
          <p>Create a company first, then return here to select it.</p>
        </article>
      </div>

      <div class="company-select-actions">
        <button class="ghost-btn" @click="goToCompanyPage">Manage Company</button>
        <button class="ghost-btn" @click="logout">Logout</button>
      </div>
    </section>
  </div>
</template>