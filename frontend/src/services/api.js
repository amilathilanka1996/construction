import axios from 'axios'
import { authState, clearAuthSession } from '../stores/auth'
import { companyState } from '../stores/company'
import { apiBaseUrl, appBase } from '../config'

export const api = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  if (authState.token) {
    config.headers.Authorization = `Bearer ${authState.token}`
  }

  if (companyState.selectedCompanyId) {
    config.headers['X-Company-Id'] = companyState.selectedCompanyId
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearAuthSession()
      window.location.href = `${appBase}auth`
    }

    return Promise.reject(error)
  },
)