import { createRouter, createWebHistory } from 'vue-router'
import AuthView from './views/AuthView.vue'
import DashboardView from './views/DashboardView.vue'
import CreateProjectView from './views/CreateProjectView.vue'
import ManageProjectView from './views/ManageProjectView.vue'
import ProjectDetailView from './views/ProjectDetailView.vue'
import CreateTenderView from './views/CreateTenderView.vue'
import TenderDetailView from './views/TenderDetailView.vue'
import CompanyView from './views/CompanyView.vue'
import SelectCompanyView from './views/SelectCompanyView.vue'
import { authState } from './stores/auth'
import { companyState } from './stores/company'
import { appBase } from './config'

const router = createRouter({
  history: createWebHistory(appBase),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/auth', component: AuthView },
    { path: '/select-company', component: SelectCompanyView, meta: { requiresAuth: true } },
    { path: '/dashboard', component: DashboardView, meta: { requiresAuth: true, requiresCompany: true } },
    { path: '/companies', component: CompanyView, meta: { requiresAuth: true } },
    { path: '/projects/create', component: CreateProjectView, meta: { requiresAuth: true, requiresCompany: true } },
    { path: '/projects/manage', component: ManageProjectView, meta: { requiresAuth: true, requiresCompany: true } },
    { path: '/projects/:id', component: ProjectDetailView, meta: { requiresAuth: true, requiresCompany: true } },
    { path: '/tenders/create', component: CreateTenderView, meta: { requiresAuth: true, requiresCompany: true } },
    { path: '/tenders/:id', component: TenderDetailView, meta: { requiresAuth: true, requiresCompany: true } },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth && !authState.token) {
    return '/auth'
  }

  if (to.path === '/auth' && authState.token) {
    return companyState.selectedCompanyId ? '/dashboard' : '/select-company'
  }

  if (authState.token && to.meta.requiresCompany && !companyState.selectedCompanyId) {
    return '/select-company'
  }

  if (authState.token && companyState.selectedCompanyId && to.path === '/select-company') {
    return '/dashboard'
  }

  return true
})

export default router