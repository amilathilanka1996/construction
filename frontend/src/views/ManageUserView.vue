<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import AppShell from '../components/AppShell.vue'
import { api } from '../services/api'

const loading = ref(true)
const saving = ref(false)
const errorText = ref('')
const successText = ref('')
const users = ref([])
const companies = ref([])
const selectedUser = ref(null)
const showEditModal = ref(false)

const editForm = reactive({
  id: null,
  name: '',
  username: '',
  email: '',
  role: 'user',
  status: 1,
  company_id: '',
  company_ids: [],
  password: '',
})

const activeCompanies = computed(() => companies.value || [])

async function loadUsers() {
  loading.value = true
  errorText.value = ''

  try {
    const { data } = await api.get('/users')
    users.value = data.users || []
    companies.value = data.companies || []
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to load users.'
  } finally {
    loading.value = false
  }
}

function openEdit(user) {
  selectedUser.value = user
  editForm.id = user.id
  editForm.name = user.name || ''
  editForm.username = user.username || ''
  editForm.email = user.email || ''
  editForm.role = user.role || 'user'
  editForm.status = Number(user.status ?? 1)
  editForm.company_id = user.company_id || ''
  editForm.company_ids = Array.isArray(user.company_ids) ? [...user.company_ids] : []
  editForm.password = ''
  successText.value = ''
  errorText.value = ''
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  selectedUser.value = null
}

function toggleCompany(companyId) {
  const numericId = Number(companyId)
  if (editForm.company_ids.includes(numericId)) {
    editForm.company_ids = editForm.company_ids.filter((id) => id !== numericId)
    if (Number(editForm.company_id) === numericId) {
      editForm.company_id = editForm.company_ids[0] || ''
    }
    return
  }

  editForm.company_ids = [...editForm.company_ids, numericId]
}

async function saveUser() {
  saving.value = true
  errorText.value = ''
  successText.value = ''

  try {
    const payload = {
      name: editForm.name,
      username: editForm.username,
      email: editForm.email,
      role: editForm.role,
      status: Number(editForm.status),
      company_id: editForm.company_id || null,
      company_ids: editForm.company_ids,
    }

    if (editForm.password.trim()) {
      payload.password = editForm.password.trim()
    }

    const { data } = await api.patch(`/users/${editForm.id}`, payload)
    successText.value = data.message
    await loadUsers()
    closeEdit()
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Failed to update user.'
  } finally {
    saving.value = false
  }
}

onMounted(loadUsers)
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <span class="badge">Settings</span>
        <h2>Manage User</h2>
        <p>Superadmin can review users, change user details, role, status, and company access.</p>
      </div>
    </div>

    <p v-if="errorText" class="error-text">{{ errorText }}</p>
    <p v-if="successText" class="success-text">{{ successText }}</p>

    <div v-if="loading" class="panel">Loading users...</div>

    <section v-else class="panel">
      <div class="section-head">
        <h3>User List</h3>
        <span>Total users: {{ users.length }}</span>
      </div>

      <div class="entry-list">
        <article v-for="user in users" :key="user.id" class="entry-item user-entry-card">
          <div class="user-entry-top">
            <div>
              <strong>{{ user.name }}</strong>
              <p>{{ user.email }}</p>
              <p>Username: {{ user.username }}</p>
              <p>Role: {{ user.role }}</p>
              <p>Status: {{ Number(user.status) === 1 ? 'Active' : 'Inactive' }}</p>
              <p>Companies: {{ (user.company_names || []).join(', ') || 'No company assigned' }}</p>
            </div>
            <button class="ghost-btn" @click="openEdit(user)">Edit User</button>
          </div>
        </article>
      </div>
    </section>

    <div v-if="showEditModal" class="modal-overlay" @click.self="closeEdit">
      <div class="modal-card">
        <div class="section-head">
          <h3>Edit User</h3>
          <button class="ghost-btn" @click="closeEdit">Close</button>
        </div>

        <form class="form-grid" @submit.prevent="saveUser">
          <label>
            <span>Name</span>
            <input v-model="editForm.name" type="text" />
          </label>
          <label>
            <span>Username</span>
            <input v-model="editForm.username" type="text" />
          </label>
          <label>
            <span>Email</span>
            <input v-model="editForm.email" type="email" />
          </label>
          <label>
            <span>Role</span>
            <select v-model="editForm.role">
              <option value="user">User</option>
              <option value="superadmin">Superadmin</option>
            </select>
          </label>
          <label>
            <span>Status</span>
            <select v-model="editForm.status">
              <option :value="1">Active</option>
              <option :value="0">Inactive</option>
            </select>
          </label>
          <label>
            <span>Primary Company</span>
            <select v-model="editForm.company_id">
              <option value="">No primary company</option>
              <option v-for="company in activeCompanies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </label>
          <label class="full-width">
            <span>New Password</span>
            <input v-model="editForm.password" type="password" placeholder="Leave blank to keep current password" />
          </label>

          <div class="full-width company-membership-box">
            <span class="field-label">Company Access</span>
            <div class="company-membership-list">
              <label v-for="company in activeCompanies" :key="company.id" class="company-membership-item">
                <input
                  :checked="editForm.company_ids.includes(company.id)"
                  type="checkbox"
                  @change="toggleCompany(company.id)"
                />
                <span>{{ company.name }}</span>
              </label>
            </div>
          </div>

          <button class="primary-btn" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save User Changes' }}
          </button>
        </form>
      </div>
    </div>
  </AppShell>
</template>