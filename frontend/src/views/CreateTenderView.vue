<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'

const router = useRouter()
const errorText = ref('')
const saving = ref(false)
const tenders = ref([])
const selectedFiles = ref([])
const today = new Date().toISOString().slice(0, 10)
const tenderForm = reactive({
  name: '',
  description: '',
  start_date: today,
  end_date: today,
  bid_security_deposit: '',
  performance_security_deposit: '',
  status: 'open',
})

const openTenders = computed(() => tenders.value.filter((tender) => tender.status === 'open'))

const totalBidSecurityDeposit = computed(() =>
  openTenders.value.reduce((total, tender) => total + Number(tender.bid_security_deposit || 0), 0),
)

const totalPerformanceSecurityDeposit = computed(() =>
  openTenders.value.reduce((total, tender) => total + Number(tender.performance_security_deposit || 0), 0),
)

const totalTenderValue = computed(
  () => totalBidSecurityDeposit.value + totalPerformanceSecurityDeposit.value,
)

function formatMoney(value) {
  return Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function onFileChange(event) {
  selectedFiles.value = Array.from(event.target.files || [])
}

async function loadTenders() {
  try {
    const { data } = await api.get('/tenders')
    tenders.value = data.tenders
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load tenders.'
  }
}

async function createTender() {
  saving.value = true
  errorText.value = ''

  try {
    const formData = new FormData()
    formData.append('name', tenderForm.name)
    formData.append('description', tenderForm.description)
    formData.append('start_date', tenderForm.start_date)
    formData.append('end_date', tenderForm.end_date)
    formData.append('bid_security_deposit', Number(tenderForm.bid_security_deposit))
    formData.append('performance_security_deposit', Number(tenderForm.performance_security_deposit))
    formData.append('status', tenderForm.status)

    selectedFiles.value.forEach((file) => {
      formData.append('files[]', file)
    })

    const { data } = await api.post('/tenders', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    router.push(`/tenders/${data.tender.id}`)
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to create tender.'
  } finally {
    saving.value = false
  }
}

function openTender(tenderId) {
  router.push(`/tenders/${tenderId}`)
}

function statusClass(status) {
  return `status-pill tender-${status}`
}

onMounted(loadTenders)
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <span class="badge">Tender</span>
        <h2>Tender Deposit Money</h2>
        <p>Create a tender record and manage its open or closed status from the detail page. Summary totals include only open tenders.</p>
      </div>
    </div>

    <p v-if="errorText" class="error-text">{{ errorText }}</p>

    <section class="stats-grid tender-create-stats-grid">
      <article class="stat-card">
        <span>Total Bid Security Deposit</span>
        <strong>{{ formatMoney(totalBidSecurityDeposit) }}</strong>
      </article>
      <article class="stat-card">
        <span>Total Performance Security Deposit</span>
        <strong>{{ formatMoney(totalPerformanceSecurityDeposit) }}</strong>
      </article>
      <article class="stat-card accent">
        <span>Total Tender Value</span>
        <strong>{{ formatMoney(totalTenderValue) }}</strong>
      </article>
    </section>

    <section class="content-grid">
      <div class="panel form-panel">
        <div class="section-head">
          <h3>Tender Details</h3>
          <span>Tender setup</span>
        </div>

        <form class="form-grid" @submit.prevent="createTender">
          <label>
            <span>Tender Name</span>
            <input v-model="tenderForm.name" type="text" placeholder="Enter tender name" />
          </label>
          <label>
            <span>Start Date</span>
            <input v-model="tenderForm.start_date" type="date" />
          </label>
          <label>
            <span>End Date</span>
            <input v-model="tenderForm.end_date" type="date" />
          </label>
          <label>
            <span>Created Date</span>
            <input :value="today" type="date" readonly />
          </label>
          <label>
            <span>Status</span>
            <select v-model="tenderForm.status">
              <option value="open">Open</option>
              <option value="closed">Closed</option>
            </select>
          </label>
          <label>
            <span>Bid Security Deposit</span>
            <input v-model="tenderForm.bid_security_deposit" type="number" step="0.01" placeholder="0.00" />
          </label>
          <label>
            <span>Performance Security Deposit</span>
            <input v-model="tenderForm.performance_security_deposit" type="number" step="0.01" placeholder="0.00" />
          </label>
          <label class="full-width">
            <span>Description</span>
            <textarea v-model="tenderForm.description" rows="5" placeholder="Tender description"></textarea>
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
            {{ saving ? 'Saving...' : 'Create Tender' }}
          </button>
        </form>
      </div>

      <div class="panel recent-projects-panel">
        <div class="section-head">
          <h3>Recent Tenders</h3>
          <span>All recent tenders</span>
        </div>

        <div class="project-list manage-project-scroll">
          <button
            v-for="tender in tenders"
            :key="tender.id"
            class="project-item"
            @click="openTender(tender.id)"
          >
            <div>
              <strong>{{ tender.name }}</strong>
              <p>{{ tender.description }}</p>
              <p>Start Date: {{ tender.start_date }}</p>
              <p>End Date: {{ tender.end_date }}</p>
              <p>Bid Deposit: {{ formatMoney(tender.bid_security_deposit) }}</p>
              <p>Performance Deposit: {{ formatMoney(tender.performance_security_deposit) }}</p>
            </div>
            <div class="project-meta">
              <span :class="statusClass(tender.status)">{{ tender.status }}</span>
              <small>{{ tender.created_date }}</small>
            </div>
          </button>

          <article v-if="tenders.length === 0" class="entry-item">
            <strong>No tenders yet</strong>
            <p>Create your first tender from this page.</p>
          </article>
        </div>
      </div>
    </section>
  </AppShell>
</template>