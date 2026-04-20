<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../services/api'
import { setAuthSession } from '../stores/auth'
import { themeMode, toggleTheme } from '../stores/theme'

const router = useRouter()
const tab = ref('login')
const loading = ref(false)
const errorText = ref('')

const loginForm = reactive({
  username: '',
  password: '',
})

const signupForm = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
})

const themeLabel = computed(() => (themeMode.value === 'dark' ? 'Light Mode' : 'Dark Mode'))

async function submitLogin() {
  loading.value = true
  errorText.value = ''

  try {
    const { data } = await api.post('/login', loginForm)
    setAuthSession({ token: data.token, user: data.user })
    router.push('/select-company')
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Login failed.'
  } finally {
    loading.value = false
  }
}

async function submitSignup() {
  loading.value = true
  errorText.value = ''

  try {
    const { data } = await api.post('/signup', signupForm)
    setAuthSession({ token: data.token, user: data.user })
    router.push('/select-company')
  } catch (error) {
    errorText.value = error.response?.data?.message || 'Signup failed.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page auth-theme-page">
    <button class="ghost-btn auth-theme-toggle" @click="toggleTheme">{{ themeLabel }}</button>

    <section class="auth-hero">
      <span class="badge">Construction project control</span>
      <h1>Manage every site with one dashboard.</h1>
      <p>
        Create projects, record expenses and income, monitor running or retention jobs, and keep
        company-wise visibility across the full business.
      </p>
    </section>

    <section class="auth-card">
      <div class="tab-row">
        <button :class="['tab-btn', { active: tab === 'login' }]" @click="tab = 'login'">Login</button>
        <!-- <button :class="['tab-btn', { active: tab === 'signup' }]" @click="tab = 'signup'">Sign Up</button> -->
      </div>

      <p v-if="errorText" class="error-text">{{ errorText }}</p>

      <form v-if="tab === 'login'" class="form-grid" @submit.prevent="submitLogin">
        <label>
          <span>Username</span>
          <input v-model="loginForm.username" type="text" placeholder="Enter username" />
        </label>
        <label>
          <span>Password</span>
          <input v-model="loginForm.password" type="password" placeholder="Enter password" />
        </label>
        <button class="primary-btn" :disabled="loading">
          {{ loading ? 'Please wait...' : 'Login and Select Company' }}
        </button>
      </form>

      <form v-else class="form-grid" @submit.prevent="submitSignup">
        <label>
          <span>Name</span>
          <input v-model="signupForm.name" type="text" placeholder="Enter full name" />
        </label>
        <label>
          <span>Username</span>
          <input v-model="signupForm.username" type="text" placeholder="Choose username" />
        </label>
        <label>
          <span>Email</span>
          <input v-model="signupForm.email" type="email" placeholder="Enter email" />
        </label>
        <label>
          <span>Password</span>
          <input v-model="signupForm.password" type="password" placeholder="Choose password" />
        </label>
        <button class="primary-btn" :disabled="loading">
          {{ loading ? 'Please wait...' : 'Create Account and Select Company' }}
        </button>
      </form>
    </section>
  </div>
</template>