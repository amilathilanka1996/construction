import { ref } from 'vue'

const STORAGE_KEY = 'construction-manager-theme'
export const themeMode = ref('dark')

function applyTheme(mode) {
  themeMode.value = mode

  if (typeof document === 'undefined') {
    return
  }

  document.body.dataset.theme = mode
  document.documentElement.style.colorScheme = mode
}

export function initializeTheme() {
  if (typeof window === 'undefined') {
    return
  }

  const savedTheme = window.localStorage.getItem(STORAGE_KEY)
  applyTheme(savedTheme === 'light' ? 'light' : 'dark')
}

export function toggleTheme() {
  const nextTheme = themeMode.value === 'dark' ? 'light' : 'dark'
  applyTheme(nextTheme)

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(STORAGE_KEY, nextTheme)
  }
}