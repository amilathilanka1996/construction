<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'

const router = useRouter()
const loading = ref(true)
const errorText = ref('')
const dashboard = reactive({
  summary: {
    project_count: 0,
    running_count: 0,
    retention_count: 0,
    closed_count: 0,
    expense_total: 0,
    income_total: 0,
    balance_total: 0,
    tender_total: 0,
  },
  recent_projects: [],
  selected_statuses: [],
})

const projects = ref([])
const selectedStatuses = ref(['running', 'retention'])
const statusOptions = [
  { label: 'Running', value: 'running' },
  { label: 'Retention', value: 'retention' },
  { label: 'Close', value: 'closed' },
]

async function loadData() {
  loading.value = true
  errorText.value = ''

  try {
    const params = selectedStatuses.value.length ? { statuses: selectedStatuses.value.join(',') } : {}
    const [{ data: dashboardData }, { data: projectData }] = await Promise.all([
      api.get('/dashboard', { params }),
      api.get('/projects'),
    ])
    Object.assign(dashboard, dashboardData)
    projects.value = projectData.projects
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load dashboard.'
  } finally {
    loading.value = false
  }
}

function openProject(projectId) {
  router.push(`/projects/${projectId}`)
}

function goToCreateProject() {
  router.push('/projects/create')
}

function statusClass(status) {
  return `status-pill ${status}`
}

function formatMoney(value) {
  return Number(value || 0).toFixed(2)
}

watch(selectedStatuses, loadData, { deep: true })
onMounted(loadData)
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <span class="badge">Overview</span>
        <h2>Project Dashboard</h2>
        <p>Track site progress, finance movement and user-owned work from one place.</p>
      </div>
      <button class="primary-btn" @click="goToCreateProject">Create Project</button>
    </div>

    <p v-if="errorText" class="error-text">{{ errorText }}</p>

    <div v-if="loading" class="panel">Loading dashboard...</div>

    <template v-else>
      <section class="panel dashboard-filter-panel">
        <div class="section-head">
          <h3>Status Filter</h3>
          <span>Tick project status to change dashboard values</span>
        </div>
        <div class="dashboard-status-filters">
          <label v-for="option in statusOptions" :key="option.value" class="dashboard-status-option">
            <input v-model="selectedStatuses" type="checkbox" :value="option.value" />
            <span>{{ option.label }}</span>
          </label>
        </div>
      </section>

      <section class="stats-grid">
        <article class="stat-card">
          <span>Total Projects</span>
          <strong>{{ dashboard.summary.project_count }}</strong>
        </article>
        <article class="stat-card">
          <span>Running</span>
          <strong>{{ dashboard.summary.running_count }}</strong>
        </article>
        <article class="stat-card">
          <span>Retention</span>
          <strong>{{ dashboard.summary.retention_count }}</strong>
        </article>
        <article class="stat-card">
          <span>Closed</span>
          <strong>{{ dashboard.summary.closed_count }}</strong>
        </article>
        <article class="stat-card">
          <span>Total Income</span>
          <strong>{{ formatMoney(dashboard.summary.income_total) }}</strong>
        </article>
        <article class="stat-card">
          <span>Total Expenses</span>
          <strong>{{ formatMoney(dashboard.summary.expense_total) }}</strong>
        </article>
        <article class="stat-card accent">
          <span>Balance</span>
          <strong>{{ formatMoney(dashboard.summary.balance_total) }}</strong>
        </article>
        <article class="stat-card">
          <span>Total Tender Value</span>
          <strong>{{ formatMoney(dashboard.summary.tender_total) }}</strong>
        </article>
      </section>

      <section class="content-grid">
        <div class="panel dashboard-projects-panel">
          <div class="section-head">
            <h3>Recent Projects</h3>
            <span>Click a project to manage details</span>
          </div>

          <div class="project-list dashboard-projects-scroll">
            <button
              v-for="project in projects"
              :key="project.id"
              class="project-item"
              @click="openProject(project.id)"
            >
              <div>
                <strong>{{ project.name }}</strong>
                <p>{{ project.description }}</p>
                <p>Start Date: {{ project.start_date }}</p>
                <p>Closing Date: {{ project.final_date }}</p>
              </div>
              <div class="project-meta">
                <span :class="statusClass(project.status)">{{ project.status }}</span>
                <small>{{ project.created_date }}</small>
              </div>
            </button>
          </div>
        </div>

        <div id="projects" class="panel">
          <div class="section-head">
            <h3>Quick Summary</h3>
            <span>Current business snapshot</span>
          </div>
          <div class="entry-list">
            <article class="entry-item">
              <strong>Running Projects</strong>
              <span>{{ dashboard.summary.running_count }}</span>
              <p>Projects actively moving on site.</p>
            </article>
            <article class="entry-item">
              <strong>Retention Projects</strong>
              <span>{{ dashboard.summary.retention_count }}</span>
              <p>Projects waiting on retained amounts or final release.</p>
            </article>
            <article class="entry-item">
              <strong>Closed Projects</strong>
              <span>{{ dashboard.summary.closed_count }}</span>
              <p>Projects completed and financially closed.</p>
            </article>
          </div>
        </div>
      </section>
    </template>
  </AppShell>
</template>