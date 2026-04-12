import { reactive } from 'vue'

const stored = JSON.parse(localStorage.getItem('cm_company') || 'null')

export const companyState = reactive({
  selectedCompanyId: stored?.selectedCompanyId || null,
  companies: [],
})

export function setSelectedCompany(companyId) {
  companyState.selectedCompanyId = companyId ? Number(companyId) : null
  localStorage.setItem('cm_company', JSON.stringify({ selectedCompanyId: companyState.selectedCompanyId }))
}

export function setCompanies(companies) {
  companyState.companies = companies || []
}

export function clearCompanyState() {
  companyState.selectedCompanyId = null
  companyState.companies = []
  localStorage.removeItem('cm_company')
}