<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'

const router = useRouter()
const errorText = ref('')
const saving = ref(false)
const recentProjects = ref([])
const selectedFiles = ref([])
const today = new Date().toISOString().slice(0, 10)
const projectForm = reactive({
  name: '',
  description: '',
  start_date: today,
  final_date: today,
  estimate_amount: '',
  valuation_amount: '',
  status: 'running',
})

function formatMoney(value) {
  return Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function onFileChange(event) {
  selectedFiles.value = Array.from(event.target.files || [])
}

async function loadProjects() {
  try {
    const { data } = await api.get('/projects')
    recentProjects.value = data.projects
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load recent projects.'
  }
}

async function createProject() {
  saving.value = true
  errorText.value = ''

  try {
    const formData = new FormData()
    formData.append('name', projectForm.name)
    formData.append('description', projectForm.description)
    formData.append('start_date', projectForm.start_date)
    formData.append('final_date', projectForm.final_date)
    formData.append('estimate_amount', Number(projectForm.estimate_amount))
    formData.append('valuation_amount', Number(projectForm.valuation_amount))
    formData.append('status', projectForm.status)

    selectedFiles.value.forEach((file) => {
      formData.append('files[]', file)
    })

    const { data } = await api.post('/projects', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    router.push(`/projects/${data.project.id}`)
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to create project.'
  } finally {
    saving.value = false
  }
}

function openProject(projectId) {
  router.push(`/projects/${projectId}`)
}

function statusClass(status) {
  return `status-pill ${status}`
}

onMounted(loadProjects)
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <span class="badge">Create</span>
        <h2>Create Project</h2>
        <p>Enter the project details once, then manage income, expenses and status from the project page.</p>
      </div>
    </div>

    <p v-if="errorText" class="error-text">{{ errorText }}</p>

    <section class="content-grid">
      <div class="panel form-panel">
        <div class="section-head">
          <h3>Project Details</h3>
          <span>Construction project setup</span>
        </div>

        <form class="form-grid" @submit.prevent="createProject">
          <label>
            <span>Project Name</span>
            <input v-model="projectForm.name" type="text" placeholder="Ex: Apartment Block A" />
          </label>
          <label>
            <span>Start Date</span>
            <input v-model="projectForm.start_date" type="date" />
          </label>
          <label>
            <span>Closing Date</span>
            <input v-model="projectForm.final_date" type="date" />
          </label>
          <label>
            <span>Created Date</span>
            <input :value="today" type="date" readonly />
          </label>
          <label>
            <span>Status</span>
            <select v-model="projectForm.status">
              <option value="running">Running</option>
              <option value="retention">Retention</option>
              <option value="closed">Closed</option>
            </select>
          </label>
          <label>
            <span>Estimate Amount</span>
            <input v-model="projectForm.estimate_amount" type="number" step="0.01" placeholder="0.00" />
          </label>
          <label>
            <span>Valuation Amount</span>
            <input v-model="projectForm.valuation_amount" type="number" step="0.01" placeholder="0.00" />
          </label>
          <label class="full-width">
            <span>Description</span>
            <textarea v-model="projectForm.description" rows="5" placeholder="Project description"></textarea>
          </label>
          <label class="full-width">
            <span>Upload Files</span>
            <input type="file" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar" @change="onFileChange" />
          </label>
          <div v-if="selectedFiles.length" class="upload-file-list full-width">
            <strong>Selected Files</strong>
            <div class="upload-file-item" v-for="file in selectedFiles" :key="`${file.name}-${file.size}`">
              <span>{{ file.name }}</span>
              <small>{{ (file.size / 1024 / 1024).toFixed(2) }} MB</small>
            </div>
          </div>
          <button class="primary-btn" :disabled="saving">
            {{ saving ? 'Saving...' : 'Create Project' }}
          </button>
        </form>
      </div>

      <div class="panel recent-projects-panel">
        <div class="section-head">
          <h3>Recent Projects</h3>
          <span>All recent projects</span>
        </div>

        <div class="project-list manage-project-scroll">
          <button
            v-for="project in recentProjects"
            :key="project.id"
            class="project-item"
            @click="openProject(project.id)"
          >
            <div>
              <strong>{{ project.name }}</strong>
              <p>{{ project.description }}</p>
              <p>Start Date: {{ project.start_date }}</p>
              <p>Closing Date: {{ project.final_date }}</p>
              <p>Estimate: {{ formatMoney(project.estimate_amount) }}</p>
              <p>Valuation: {{ formatMoney(project.valuation_amount) }}</p>
              <p>Files: {{ project.file_count || 0 }}</p>
            </div>
            <div class="project-meta">
              <span :class="statusClass(project.status)">{{ project.status }}</span>
              <small>{{ project.created_date }}</small>
            </div>
          </button>

          <article v-if="recentProjects.length === 0" class="entry-item">
            <strong>No projects yet</strong>
            <p>Create your first construction project from this page.</p>
          </article>
        </div>
      </div>
    </section>
  </AppShell>
</template>