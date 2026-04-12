import { reactive } from 'vue'
import { clearCompanyState } from './company'

const stored = JSON.parse(localStorage.getItem('cm_auth') || 'null')

export const authState = reactive({
  token: stored?.token || '',
  user: stored?.user || null,
})

export function setAuthSession(payload) {
  authState.token = payload.token
  authState.user = payload.user
  localStorage.setItem('cm_auth', JSON.stringify(payload))
  clearCompanyState()
}

export function clearAuthSession() {
  authState.token = ''
  authState.user = null
  localStorage.removeItem('cm_auth')
  clearCompanyState()
}