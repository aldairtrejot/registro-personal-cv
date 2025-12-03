<template>
  <div class="card shadow-lg">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="card-title mb-0">Empleados</h5>
        <div v-if="canExport" class="d-flex gap-2"></div>
      </div>

      <div class="row">
        <div class="col-8">
          <div class="mb-3 row" style="margin-left: -8px;">
            <label class="col-3 col-form-label required" style="padding-right: 0 !important;">Buscador</label>
            <div class="col" style="padding-left: 0 !important;">
              <input type="text" class="form-control" id="table-search" placeholder="Buscar">
              <small class="form-hint">Recuerda buscar por RFC, CURP, nombre o apellidos.</small>
            </div>
          </div>
        </div>

        <div class="col-4" v-if="isAdmin">
          <inputSelectTemplate
            v-model="listSelectPeriod"
            :options="listOptionsPeriod"
            id="id_cat_estatus"
            label="Estatus"
            :multiple="false"
            grid="col-md-6 col-sm-12"
            :disabled="isDisableSelect"
            @onChange="handleStatusChange"
          />
        </div>
      </div>

      <tableSpinner ref="spinnerRef" />

      <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped" id="table-default">
          <thead>
            <tr>
              <tableRow value="Acciones" />
              <tableRow value="Nombre completo" />
              <tableRow value="RFC" />
              <tableRow value="CURP" />
              <tableRow value="Estatus" />
            </tr>
          </thead>
          <tbody>
            <tableEmpty v-if="item.length === 0" :colspan="5" />
            <tr v-for="row in item" :key="row.prof_id ?? row.id ?? row.rfc">
              <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <!-- 👇 usa prof_id para navegar al edit -->
<tableButtonEdit
  v-if="row.prof_id"
  :href="`${BASE_URL}/employee/edit/${row.prof_id}`"
  color="#479cc4"
  icon="ti ti-edit"
  tooltip="Modificar"
/>



                </div>
              </td>
              <td class="text-center text-secondary"><div>{{ row.nombre_completo }}</div></td>
              <tableItem :value="row.rfc" />
              <tableItem :value="row.curp" />
              <tableItem :value="row.estatus" />
            </tr>
          </tbody>
        </table>
      </div>

      <tableFooter :row="row" :rowsAll="rowsAll" />
    </div>
  </div>

  <modalTemplate
    modal-id="modal_download"
    :on-confirm="exportarXlsx"
    dialog-class="modal-dialog modal-dialog-centered modal-lg"
  >
    <h2>Generador de reportes</h2>
    <p>El reporte se descargará en formato <strong>Excel (.xlsx)</strong> con la <strong>misma visibilidad y filtros</strong> que ves en la tabla.</p>
    <div class="form-selectgroup-boxes row mb-3">
      <div class="col-lg-12">
        <label class="form-selectgroup-item">
          <input type="radio" name="report-type" value="xlsx" class="form-selectgroup-input" checked>
          <span class="form-selectgroup-label d-flex align-items-center p-3">
            <span class="me-3"><span class="form-selectgroup-check"></span></span>
            <span class="form-selectgroup-label-content">
              <span class="form-selectgroup-title strong mb-1">Excel (.xlsx)</span>
              <span class="d-block text-secondary">Descarga con los permisos y el estatus seleccionado (si aplica).</span>
            </span>
          </span>
        </label>
      </div>
    </div>
    <div class="col-12" v-if="isAdmin"></div>
  </modalTemplate>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { notyf } from '@components/notyf.js'
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableItem from '@helpers/table/table-item.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import tableButtonEdit from '@helpers/table/table-button-edit.vue'
import inputSelectTemplate from '@helpers/form/input-select-template.vue'
import modalTemplate from '@helpers/modal/modal-template.vue'
import axios from '@axios'
import { showSpinner, hideSpinner } from '@components/spinner.js'

const BASE_URL = (import.meta.env.VITE_BASE_URL || '').replace(/\/+$/, '')

function readRoles () {
  const raw = (typeof window !== 'undefined' && Array.isArray(window.appUserRoles)) ? window.appUserRoles : []
  return raw.map(v => Number(v)).filter(Number.isFinite)
}
const roles = ref(readRoles())
const isAdmin = ref(roles.value.includes(1))
const canExport = computed(() => roles.value.some(r => [1,2,3,4,5].includes(r)))

onMounted(() => {
  if (roles.value.length === 0 || !isAdmin.value) {
    const again = readRoles()
    roles.value = again
    if (again.includes(1)) isAdmin.value = true
  }
})

const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)
const listSelectPeriod = ref(null)
const listOptionsPeriod = ref([])
const isDisableSelect = ref(true)

function getStatusId(selected) {
  if (selected == null || selected === '') return ''
  if (typeof selected === 'number') return selected
  if (typeof selected === 'string') {
    const n = Number(selected); return Number.isFinite(n) ? n : ''
  }
  const raw = selected.id ?? selected.value ?? selected.id_cat_estatus ?? selected.ID ?? selected.key ?? ''
  const n = Number(raw); return Number.isFinite(n) ? n : ''
}

async function exportarXlsx () {
  showSpinner()
  await nextTick()
  await waitModalHidden('#modal_download', 600)

  try {
    const params = new URLSearchParams()
    params.set('format', 'xlsx')
    if (searchTerm.value?.trim()) params.set('search', searchTerm.value.trim())
    if (isAdmin.value) {
      const id = getStatusId(listSelectPeriod.value)
      if (id !== '') params.set('id_cat_estatus', String(id))
    }

    const response = await axios({
      method: 'get',
      url: `${BASE_URL}/employee/export`,
      params,
      responseType: 'blob',
      validateStatus: s => s >= 200 && s < 300
    })

    if (response.status === 204 || (response.data && response.data.size === 0)) {
      notyf.info('No hay resultados para exportar.')
      return
    }

    let filename = 'empleados.xlsx'
    const disposition = response.headers['content-disposition']
    if (disposition) {
      const m = /filename="(.+?)"/.exec(disposition)
      if (m && m[1]) filename = m[1]
    }

    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)

    notyf.success('Tu archivo se descargó.')
  } catch (error) {
    notyf.error('No pudimos completar la acción. Intenta de nuevo.')
  } finally {
    hideSpinner()
  }
}

function waitModalHidden(selector = '#modal_download', timeoutMs = 500) {
  return new Promise(resolve => {
    try {
      const $modal = typeof $ !== 'undefined' ? $(selector) : null
      if (!$modal || $modal.length === 0) return resolve()
      let settled = false
      const done = () => { if (!settled) { settled = true; resolve() } }
      const timer = setTimeout(done, timeoutMs)
      $modal.one('hidden.bs.modal', () => { clearTimeout(timer); done() })
      $modal.modal('hide')
    } catch {
      resolve()
    }
  })
}

const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()
  spinnerRef.value?.show()

  const offset = (currentPage.value - 1) * limit.value

  try {
    const selectEl = document.getElementById('footer-filter')
    const selectValue = parseInt(selectEl?.value ?? String(limit.value), 10)

    const payload = {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      select: selectValue
    }
    if (isAdmin.value) payload.id_cat_estatus = getStatusId(listSelectPeriod.value)

    const { data } = await axios.post(`${BASE_URL}/employee/table`, payload)
    item.value = data.list ?? []
    rowsAll.value = Number(data.allRow ?? 0)
    row.value = Number(data.row ?? 0)
  } catch {
    notyf.error('No pudimos mostrar los empleados. Intenta de nuevo.')
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0
    setTimeout(() => spinnerRef.value?.hide(), delay)
  }
}

async function getCollections () {
  try {
    if (!isAdmin.value) {
      listSelectPeriod.value = null
      listOptionsPeriod.value = []
      isDisableSelect.value  = true
      return
    }

    const result = await axios.post(`${BASE_URL}/employee/main`)
    if (!result.data?.status) {
      notyf.error(result.data?.message || 'No pudimos cargar los filtros.')
      return
    }

    const list = Array.isArray(result.data?.listOptionsPeriod) ? result.data.listOptionsPeriod : []
    listOptionsPeriod.value = list.slice()

    const hasTodos = listOptionsPeriod.value.some(op => {
      const id = Number(op.id ?? op.value ?? op.id_cat_estatus)
      return Number.isFinite(id) && id === 9999
    })
    if (!hasTodos) {
      listOptionsPeriod.value.unshift({
        id: 9999, value: 9999, label: 'TODOS', text: 'TODOS', descripcion: 'TODOS', name: 'TODOS'
      })
    }

    listSelectPeriod.value = null
    isDisableSelect.value  = false
  } catch {
    notyf.error('No pudimos cargar los filtros. Intenta de nuevo.')
  }
}

onMounted(async () => {
  await getCollections()
  await fetchTableData()
  setupTableEvents({ fetchTableData, searchTerm, currentPage, limit, handlePagination })
})

watch(listSelectPeriod, () => {
  if (!isAdmin.value) return
  currentPage.value = 1
  fetchTableData()
})

async function handleStatusChange() {
  if (!isAdmin.value) return
  currentPage.value = 1
  fetchTableData()
}
</script>