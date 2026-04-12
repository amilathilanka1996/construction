<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'

const route = useRoute()
const loading = ref(true)
const errorText = ref('')
const saveMessage = ref('')
const showEditModal = ref(false)
const project = ref(null)
const expenses = ref([])
const incomes = ref([])
const files = ref([])
const editFiles = ref([])
const projectForm = reactive({
  name: '',
  description: '',
  start_date: '',
  final_date: '',
  estimate_amount: '',
  valuation_amount: '',
})

function createExpenseRow() {
  return {
    local_id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
    title: '',
    quantity: '',
    unit_price: '',
  }
}

const expenseRows = ref([createExpenseRow()])
const expenseBatchForm = reactive({
  details: '',
  entry_date: new Date().toISOString().slice(0, 10),
})
const incomeForm = reactive({
  title: '',
  details: '',
  reference_no: '',
  amount: '',
  entry_date: new Date().toISOString().slice(0, 10),
})

const expenseGrandTotal = computed(() =>
  expenseRows.value.reduce((total, row) => total + expenseRowTotal(row), 0),
)

const groupedExpenses = computed(() => {
  const groups = new Map()

  ;[...expenses.value]
    .sort((a, b) => String(b.entry_date).localeCompare(String(a.entry_date)) || Number(b.id) - Number(a.id))
    .forEach((expense) => {
      const dateKey = String(expense.entry_date)
      if (!groups.has(dateKey)) {
        groups.set(dateKey, [])
      }
      groups.get(dateKey).push(expense)
    })

  return Array.from(groups.entries()).map(([date, items]) => ({ date, items }))
})

const groupedIncomes = computed(() => {
  const groups = new Map()

  ;[...incomes.value]
    .sort((a, b) => String(b.entry_date).localeCompare(String(a.entry_date)) || Number(b.id) - Number(a.id))
    .forEach((income) => {
      const dateKey = String(income.entry_date)
      if (!groups.has(dateKey)) {
        groups.set(dateKey, [])
      }
      groups.get(dateKey).push(income)
    })

  return Array.from(groups.entries()).map(([date, items]) => ({ date, items }))
})

function expenseRowTotal(row) {
  const quantity = Number(row.quantity || 0)
  const unitPrice = Number(row.unit_price || 0)
  return quantity * unitPrice
}

function formatMoney(value) {
  return Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

function expenseGroupTotal(items) {
  return items.reduce((total, item) => total + Number(item.amount || 0), 0)
}

function incomeGroupTotal(items) {
  return items.reduce((total, item) => total + Number(item.amount || 0), 0)
}

function normalizeIncomeAmount() {
  incomeForm.amount = incomeForm.amount === '' ? '' : String(Number(incomeForm.amount || 0))
}

function fillProjectForm(data) {
  projectForm.name = data.name
  projectForm.description = data.description
  projectForm.start_date = data.start_date
  projectForm.final_date = data.final_date
  projectForm.estimate_amount = data.estimate_amount
  projectForm.valuation_amount = data.valuation_amount
}

function resetExpenseRows() {
  expenseRows.value = [createExpenseRow()]
  expenseBatchForm.details = ''
  expenseBatchForm.entry_date = new Date().toISOString().slice(0, 10)
}

function addExpenseRow() {
  expenseRows.value.push(createExpenseRow())
}

function removeExpenseRow(index) {
  if (expenseRows.value.length === 1) {
    resetExpenseRows()
    return
  }

  expenseRows.value.splice(index, 1)
}

function openEditModal() {
  if (project.value) {
    fillProjectForm(project.value)
  }
  editFiles.value = []
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
}

function onEditFileChange(event) {
  editFiles.value = Array.from(event.target.files || [])
}

async function loadProject() {
  loading.value = true
  errorText.value = ''
  saveMessage.value = ''

  try {
    const { data } = await api.get(`/projects/${route.params.id}`)
    project.value = data.project
    fillProjectForm(data.project)
    expenses.value = data.expenses
    incomes.value = data.incomes
    files.value = data.files || []
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load project.'
  } finally {
    loading.value = false
  }
}

async function saveProjectDetails() {
  try {
    const formData = new FormData()
    formData.append('name', projectForm.name)
    formData.append('description', projectForm.description)
    formData.append('start_date', projectForm.start_date)
    formData.append('final_date', projectForm.final_date)
    formData.append('estimate_amount', Number(projectForm.estimate_amount))
    formData.append('valuation_amount', Number(projectForm.valuation_amount))
    formData.append('status', project.value.status)

    editFiles.value.forEach((file) => {
      formData.append('files[]', file)
    })

    const { data } = await api.patch(`/projects/${route.params.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    project.value = data.project
    files.value = data.files || files.value
    saveMessage.value = 'Project details updated successfully.'
    editFiles.value = []
    closeEditModal()
    await loadProject()
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to update project details.'
  }
}

async function addExpense() {
  const items = expenseRows.value
    .filter((row) => row.title || row.quantity || row.unit_price)
    .map((row) => ({
      title: row.title,
      details: expenseBatchForm.details,
      entry_date: expenseBatchForm.entry_date,
      quantity: Number(row.quantity),
      unit_price: Number(row.unit_price),
    }))

  if (!items.length) {
    errorText.value = 'Add at least one expense item.'
    return
  }

  try {
    const { data } = await api.post(`/projects/${route.params.id}/expenses`, { items })
    saveMessage.value = data.message || 'Expenses added successfully.'
    errorText.value = ''
    resetExpenseRows()
    await loadProject()
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to add expense.'
  }
}

async function addIncome() {
  try {
    await api.post(`/projects/${route.params.id}/incomes`, { ...incomeForm, amount: Number(incomeForm.amount) })
    Object.assign(incomeForm, {
      title: '',
      details: '',
      reference_no: '',
      amount: '',
      entry_date: new Date().toISOString().slice(0, 10),
    })
    await loadProject()
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to add income.'
  }
}

async function updateStatus(status) {
  try {
    await api.patch(`/projects/${route.params.id}/status`, { status })
    await loadProject()
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to update status.'
  }
}

function statusClass(status) {
  return `status-pill ${status}`
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

onMounted(loadProject)
</script>

<template>
  <AppShell>
    <div v-if="loading" class="panel">Loading project...</div>

    <template v-else-if="project">
      <div class="page-header">
        <div>
          <span class="badge">Project Detail</span>
          <h2>{{ project.name }}</h2>
          <p>{{ project.description }}</p>
          <p>
            Estimate Amount: {{ formatMoney(project.estimate_amount) }} | Valuation Amount: {{ formatMoney(project.valuation_amount) }}
          </p>
          <p>
            Created Date: {{ project.created_date }} | Status: <span :class="statusClass(project.status)">{{ project.status }}</span>
          </p>
        </div>

        <div>
          <div class="status-actions">
            <button class="ghost-btn running" @click="updateStatus('running')">Running</button>
            <button class="ghost-btn retention" @click="updateStatus('retention')">Retention</button>
            <button class="ghost-btn closed" @click="updateStatus('closed')">Close</button>
          </div>
          <div class="edit-row">
            <button class="ghost-btn" @click="openEditModal">Edit Project</button>
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

      <section class="stats-grid">
        <article class="stat-card">
          <span>Start Date</span>
          <strong>{{ project.start_date }}</strong>
          <span>Closing Date</span>
          <strong>{{ project.final_date }}</strong>
        </article>
        <article class="stat-card">
          <span>Total Expense</span>
          <strong>{{ formatMoney(project.expense_total) }}</strong>
        </article>
        <article class="stat-card">
          <span>Total Income</span>
          <strong>{{ formatMoney(project.income_total) }}</strong>
        </article>
        <article class="stat-card accent">
          <span>Balance</span>
          <strong>{{ formatMoney(project.balance) }}</strong>
        </article>
      </section>

      <section class="content-grid two-column">
        <div class="panel">
          <div class="section-head">
            <h3>Add Expense</h3>
            <span>Table entry for multiple expense rows</span>
          </div>
          <form class="form-grid" @submit.prevent="addExpense">
            <div class="expense-table-wrap">
              <div class="expense-table expense-table-head">
                <span>Title</span>
                <span>qty</span>
                <span>Unit Price</span>
                <span>Total</span>
                <span>Action</span>
              </div>
              <div v-for="(row, index) in expenseRows" :key="row.local_id" class="expense-table expense-table-row">
                <input v-model="row.title" type="text" placeholder="Expense title" />
                <input v-model="row.quantity" type="number" step="0.01" placeholder="10" />
                <input v-model="row.unit_price" type="number" step="0.01" placeholder="1500.00" />
                <input :value="formatMoney(expenseRowTotal(row))" type="text" readonly />
                <button type="button" class="ghost-btn small-btn" @click="removeExpenseRow(index)">x</button>
              </div>
            </div>

            <div class="stack-actions">
              <button type="button" class="ghost-btn" @click="addExpenseRow">Add More Expense</button>
              <span class="inline-note">Created date is filled automatically.</span>
            </div>

            <label>
              <span>Date</span>
              <input v-model="expenseBatchForm.entry_date" type="date" />
            </label>
            <label class="full-width">
              <span>Details</span>
              <textarea v-model="expenseBatchForm.details" rows="3" placeholder="Expense details"></textarea>
            </label>
            <label class="full-width">
              <span>All Expense Total</span>
              <input :value="formatMoney(expenseGrandTotal)" type="text" readonly />
            </label>

            <button class="primary-btn">Save Expenses</button>
          </form>
        </div>

        <div class="panel">
          <div class="section-head">
            <h3>Add Income</h3>
            <span>Client payment or project income</span>
          </div>
          <form class="form-grid" @submit.prevent="addIncome">
            <label>
              <span>Title</span>
              <input v-model="incomeForm.title" type="text" placeholder="Income title" />
            </label>
            <label>
              <span>Reference No</span>
              <input v-model="incomeForm.reference_no" type="text" placeholder="Invoice or receipt no" />
            </label>
            <label>
              <span>Amount</span>
              <input v-model="incomeForm.amount" type="number" step="0.01" placeholder="0.00" @blur="normalizeIncomeAmount" />
            </label>
            <label>
              <span>Date</span>
              <input v-model="incomeForm.entry_date" type="date" />
            </label>
            <label class="full-width">
              <span>Details</span>
              <textarea v-model="incomeForm.details" rows="4" placeholder="Income details"></textarea>
            </label>
            <button class="primary-btn">Save Income</button>
          </form>
        </div>
      </section>

      <section class="content-grid two-column">
        <div class="panel">
          <div class="section-head">
            <h3>Expense History</h3>
            <span>One table for each date</span>
          </div>
          <div class="history-date-groups">
            <section v-for="group in groupedExpenses" :key="group.date" class="history-date-block">
              <div class="history-date-title">{{ group.date }}</div>
              <div class="history-table-wrap">
                <table class="expense-history-table-grid">
                  <thead>
                    <tr>
                      <th>Title</th>
                      <th>qty</th>
                      <th>Unit Price</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="expense in group.items" :key="expense.id">
                      <td>{{ expense.title }}</td>
                      <td>{{ Number(expense.quantity || 0).toFixed(2) }}</td>
                      <td>{{ formatMoney(expense.unit_price) }}</td>
                      <td>{{ formatMoney(expense.amount) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="3"><strong>Total</strong></td>
                      <td><strong>{{ formatMoney(expenseGroupTotal(group.items)) }}</strong></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </section>
          </div>
        </div>

        <div class="panel">
          <div class="section-head">
            <h3>Income History</h3>
            <span>One table for each date</span>
          </div>
          <div class="history-date-groups">
            <section v-for="group in groupedIncomes" :key="group.date" class="history-date-block">
              <div class="history-date-title">{{ group.date }}</div>
              <div class="history-table-wrap">
                <table class="expense-history-table-grid income-history-table-grid">
                  <thead>
                    <tr>
                      <th>Title</th>
                      <th>Reference No</th>
                      <th>Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="income in group.items" :key="income.id">
                      <td>{{ income.title }}</td>
                      <td>{{ income.reference_no || '-' }}</td>
                      <td>{{ formatMoney(income.amount) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"><strong>Total</strong></td>
                      <td><strong>{{ formatMoney(incomeGroupTotal(group.items)) }}</strong></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </section>
          </div>
        </div>
      </section>

      <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
        <div class="modal-card" @click.stop>
          <div class="section-head">
            <h3>Edit Project</h3>
            <button class="ghost-btn" @click="closeEditModal">Close</button>
          </div>
          <form class="form-grid" @submit.prevent="saveProjectDetails">
            <label class="full-width">
              <span>Project Name</span>
              <input v-model="projectForm.name" type="text" />
            </label>
            <label class="full-width">
              <span>Description</span>
              <textarea v-model="projectForm.description" rows="4"></textarea>
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
              <span>Estimate Amount</span>
              <input v-model="projectForm.estimate_amount" type="number" step="0.01" placeholder="0.00" />
            </label>
            <label>
              <span>Valuation Amount</span>
              <input v-model="projectForm.valuation_amount" type="number" step="0.01" placeholder="0.00" />
            </label>
            <label class="full-width">
              <span>Upload Files</span>
              <input type="file" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar" @change="onEditFileChange" />
            </label>
            <div v-if="editFiles.length" class="upload-file-list full-width">
              <strong>Selected Files</strong>
              <div class="upload-file-item" v-for="file in editFiles" :key="`${file.name}-${file.size}`">
                <span>{{ file.name }}</span>
                <small>{{ (file.size / 1024 / 1024).toFixed(2) }} MB</small>
              </div>
            </div>
            <button class="primary-btn">Save Project Details</button>
          </form>
        </div>
      </div>
    </template>
  </AppShell>
</template>