<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'

const route = useRoute()
const loading = ref(true)
const errorText = ref('')
const saveMessage = ref('')
const tender = ref(null)
const files = ref([])

const totalDeposit = computed(() => {
  if (!tender.value) {
    return 0
  }

  return Number(tender.value.bid_security_deposit || 0) + Number(tender.value.performance_security_deposit || 0)
})

function formatMoney(value) {
  return Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function statusClass(status) {
  return `status-pill tender-${status}`
}

function isImageFile(file) {
  const type = String(file.file_type || '').toLowerCase()
  const name = String(file.original_name || '').toLowerCase()
  return type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/.test(name)
}

function fileIconLabel(file) {
  const name = String(file.original_name || '').toLowerCase()
  const type = String(file.file_type || '').toLowerCase()

  if (type.includes('pdf') || name.endsWith('.pdf')) {
    return 'PDF'
  }

  if (type.includes('sheet') || type.includes('excel') || name.endsWith('.xls') || name.endsWith('.xlsx') || name.endsWith('.csv')) {
    return 'XLS'
  }

  if (name.endsWith('.doc') || name.endsWith('.docx') || type.includes('word')) {
    return 'DOC'
  }

  return 'FILE'
}

async function loadTender() {
  loading.value = true
  errorText.value = ''
  saveMessage.value = ''

  try {
    const { data } = await api.get(`/tenders/${route.params.id}`)
    tender.value = data.tender
    files.value = data.files || []
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load tender.'
  } finally {
    loading.value = false
  }
}

async function updateStatus(status) {
  try {
    const { data } = await api.patch(`/tenders/${route.params.id}/status`, { status })
    tender.value = data.tender
    saveMessage.value = 'Tender status updated successfully.'
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to update tender status.'
  }
}

onMounted(loadTender)
watch(() => route.params.id, loadTender)
</script>

<template>
  <AppShell>
    <div v-if="loading" class="panel">Loading tender...</div>

    <template v-else-if="tender">
      <div class="page-header">
        <div>
          <span class="badge">Tender Detail</span>
          <h2>{{ tender.name }}</h2>
          <p>{{ tender.description }}</p>
          <p>Created Date: {{ tender.created_date }}</p>
        </div>

        <div>
          <div class="status-actions">
            <button class="ghost-btn tender-open" @click="updateStatus('open')">Open</button>
            <button class="ghost-btn tender-closed" @click="updateStatus('closed')">Close</button>
          </div>
          <div class="project-file-links project-file-links-side">
            <div v-if="files.length" class="project-file-list project-file-preview-list">
              <a
                v-for="file in files"
                :key="file.id"
                :href="file.file_url"
                class="project-file-link project-file-preview"
                target="_blank"
                rel="noopener noreferrer"
              >
                <img
                  v-if="isImageFile(file)"
                  :src="file.file_url"
                  :alt="file.original_name"
                  class="project-file-thumb"
                />
                <div v-else class="project-file-icon">{{ fileIconLabel(file) }}</div>
                <span class="project-file-name">{{ file.original_name }}</span>
              </a>
            </div>
            <p v-else class="file-empty-text">No files uploaded</p>
          </div>
        </div>
      </div>

      <p v-if="errorText" class="error-text">{{ errorText }}</p>
      <p v-if="saveMessage" class="success-text">{{ saveMessage }}</p>

      <section class="stats-grid tender-stats-grid">
        <article class="stat-card">
          <span>Start Date</span>
          <strong>{{ tender.start_date }}</strong>
          <span>End Date</span>
          <strong>{{ tender.end_date }}</strong>
        </article>
        <article class="stat-card">
          <span>Bid Security Deposit</span>
          <strong>{{ formatMoney(tender.bid_security_deposit) }}</strong>
        </article>
        <article class="stat-card">
          <span>Performance Security Deposit</span>
          <strong>{{ formatMoney(tender.performance_security_deposit) }}</strong>
        </article>
        <article class="stat-card accent">
          <span>Total Value</span>
          <strong>{{ formatMoney(totalDeposit) }}</strong>
        </article>
      </section>

      <section class="content-grid two-column">
        <div class="panel">
          <div class="section-head">
            <h3>Tender Status</h3>
            <span :class="statusClass(tender.status)">{{ tender.status }}</span>
          </div>
          <div class="entry-item">
            <strong>Status Change</strong>
            <p>Use the status buttons above to switch this tender between open and closed.</p>
          </div>
        </div>

        <div class="panel">
          <div class="section-head">
            <h3>Tender Summary</h3>
            <span>Key tender values</span>
          </div>
          <div class="entry-list">
            <article class="entry-item">
              <strong>Bid Security Deposit</strong>
              <p>{{ formatMoney(tender.bid_security_deposit) }}</p>
            </article>
            <article class="entry-item">
              <strong>Performance Security Deposit</strong>
              <p>{{ formatMoney(tender.performance_security_deposit) }}</p>
            </article>
            <article class="entry-item">
              <strong>Total Value</strong>
              <p>{{ formatMoney(totalDeposit) }}</p>
            </article>
          </div>
        </div>
      </section>
    </template>
  </AppShell>
</template>