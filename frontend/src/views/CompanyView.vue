<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'
import { authState } from '../stores/auth'
import { companyState, setCompanies, setSelectedCompany } from '../stores/company'
import { apiBaseUrl } from '../config'

const router = useRouter()
const loading = ref(true)
const saving = ref(false)
const errorText = ref('')
const successText = ref('')
const companies = ref([])
const companyForm = reactive({
  name: '',
  description: '',
})

async function requestCompanies() {
  try {
    const { data } = await api.get('/companies')
    return data
  } catch (_error) {
    const headers = {}
    if (authState.token) {
      headers.Authorization = `Bearer ${authState.token}`
    }
    if (companyState.selectedCompanyId) {
      headers['X-Company-Id'] = String(companyState.selectedCompanyId)
    }

    const response = await fetch(`${apiBaseUrl}/companies`, { headers })
    const payload = await response.json()
    if (!response.ok) {
      throw new Error(payload.message || 'Failed to load companies.')
    }
    return payload
  }
}

async function loadCompanies() {
  loading.value = true
  errorText.value = ''

  try {
    const data = await requestCompanies()
    companies.value = data.companies || []
    setCompanies(companies.value)

    if (!companyState.selectedCompanyId && companies.value.length) {
      setSelectedCompany(data.selected_company_id || companies.value[0].id)
    }
  } catch (error) {
    errorText.value = error.message || error.response?.data?.message || 'Failed to load companies.'
    companies.value = []
    setCompanies([])
  } finally {
    loading.value = false
  }
}

async function createCompany() {
  saving.value = true
  errorText.value = ''
  successText.value = ''

  try {
    let data
    try {
      const response = await api.post('/companies', companyForm)
      data = response.data
    } catch (_error) {
      const headers = {
        'Content-Type': 'application/json',
      }
      if (authState.token) {
        headers.Authorization = `Bearer ${authState.token}`
      }
      if (companyState.selectedCompanyId) {
        headers['X-Company-Id'] = String(companyState.selectedCompanyId)
      }

      const response = await fetch(`${apiBaseUrl}/companies`, {
        method: 'POST',
        headers,
        body: JSON.stringify(companyForm),
      })
      const payload = await response.json()
      if (!response.ok) {
        throw new Error(payload.message || 'Failed to create company.')
      }
      data = payload
    }

    successText.value = data.message
    companyForm.name = ''
    companyForm.description = ''
    await loadCompanies()
    setSelectedCompany(data.company.id)
    router.push('/dashboard')
  } catch (error) {
    errorText.value = error.message || error.response?.data?.message || 'Failed to create company.'
  } finally {
    saving.value = false
  }
}

function switchCompany(companyId) {
  setSelectedCompany(companyId)
  router.push('/dashboard')
}

onMounted(loadCompanies)
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <span class="badge">Company</span>
        <h2>Company Management</h2>
        <p>Create a company any time and switch the full system company-wise.</p>
      </div>
    </div>

    <p v-if="errorText" class="error-text">{{ errorText }}</p>
    <p v-if="successText" class="success-text">{{ successText }}</p>

    <div v-if="loading" class="panel">Loading companies...</div>

    <section v-else class="content-grid">
      <div class="panel form-panel">
        <div class="section-head">
          <h3>Create Company</h3>
          <span>Any user can create company</span>
        </div>

        <form class="form-grid" @submit.prevent="createCompany">
          <label>
            <span>Company Name</span>
            <input v-model="companyForm.name" type="text" placeholder="Enter company name" />
          </label>
          <label class="full-width">
            <span>Description</span>
            <textarea v-model="companyForm.description" rows="5" placeholder="Company description"></textarea>
          </label>
          <button class="primary-btn" :disabled="saving">
            {{ saving ? 'Saving...' : 'Create Company' }}
          </button>
        </form>
      </div>

      <div class="panel recent-projects-panel">
        <div class="section-head">
          <h3>My Companies</h3>
          <span>One user can use many companies</span>
        </div>

        <div class="project-list manage-project-scroll">
          <button
            v-for="company in companies"
            :key="company.id"
            class="project-item"
            @click="switchCompany(company.id)"
          >
            <div>
              <strong>{{ company.name }}</strong>
              <p>{{ company.description || 'No description' }}</p>
              <p>Projects: {{ company.project_count }}</p>
              <p>Tenders: {{ company.tender_count }}</p>
            </div>
            <div class="project-meta">
              <span :class="['status-pill', { running: Number(companyState.selectedCompanyId) === Number(company.id) }]">
                {{ Number(companyState.selectedCompanyId) === Number(company.id) ? 'Selected' : 'Select' }}
              </span>
              <small>Users: {{ company.user_count }}</small>
            </div>
          </button>

          <article v-if="companies.length === 0" class="entry-item">
            <strong>No companies yet</strong>
            <p>Create your first company from this page.</p>
          </article>
        </div>
      </div>
    </section>
  </AppShell>
</template>