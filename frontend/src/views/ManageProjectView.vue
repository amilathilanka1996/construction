<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'

const router = useRouter()
const loading = ref(true)
const errorText = ref('')
const projects = ref([])

const statusGroups = computed(() => {
  const byStatus = (status) => projects.value.filter((project) => project.status === status)

  return [
    { key: 'running', label: 'Running Projects', items: byStatus('running') },
    { key: 'retention', label: 'Retention Projects', items: byStatus('retention') },
    { key: 'closed', label: 'Closed Projects', items: byStatus('closed') },
  ]
})

async function loadProjects() {
  loading.value = true
  errorText.value = ''

  try {
    const { data } = await api.get('/projects')
    projects.value = data.projects
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load projects.'
  } finally {
    loading.value = false
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
        <span class="badge">Manage</span>
        <h2>Manage Projects</h2>
        <p>Review projects by status and open any project to manage its full details.</p>
      </div>
    </div>

    <p v-if="errorText" class="error-text">{{ errorText }}</p>

    <div v-if="loading" class="panel">Loading projects...</div>

    <section v-else class="status-manage-grid">
      <article v-for="group in statusGroups" :key="group.key" class="panel">
        <div class="section-head">
          <h3>{{ group.label }}</h3>
          <span>3 visible, scroll for more</span>
        </div>

        <div class="project-list manage-project-scroll">
          <button
            v-for="project in group.items"
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

          <article v-if="group.items.length === 0" class="entry-item">
            <strong>No projects</strong>
            <p>No projects in this status.</p>
          </article>
        </div>
      </article>
    </section>
  </AppShell>
</template>