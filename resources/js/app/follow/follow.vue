<!-- resources/js/app/follow/follow.vue -->
<template>
  <!-- Layout: columnas con alturas iguales -->
  <div class="row align-items-stretch">
    <!-- IZQUIERDA: Pasos del proceso -->
    <div class="col-lg-4">
      <div class="card h-100 d-flex flex-column shadow-lg">
        <div class="card-body" v-if="employeeStatus !== 6">
          <h3 class="card-title">Proceso</h3>
          <ul class="steps steps-vertical">
            <li
              v-for="step in processSteps"
              :key="step.id"
              class="step-item"
              :class="{ active: isStepActive(step.id) }"
            >
              <div class="h4 m-0">{{ step.title }}</div>
              <div class="text-secondary">{{ step.description }}</div>
            </li>
          </ul>
        </div>

        <div class="card-body" v-else>
          <h3 class="card-title">Proceso</h3>
          <div class="alert alert-minor alert-danger alert-dismissible" role="alert">
            <i class="ti ti-alert-circle" style="font-size: 1.3rem; color:red"></i>
            <div>
              <h4 class="alert-heading">Tu solicitud ha sido rechazada</h4>
              <div class="alert-description">
                <p>Por favor, revisa los siguientes puntos:</p>
                <ul class="alert-list">
                  <li>Observaciones realizadas por validadores</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body" v-if="employeeStatus !== 6">
          <ul class="steps steps-counter steps-vertical">
            <li
              v-for="step in processSteps"
              :key="'counter-' + step.id"
              class="step-item"
              :class="{ active: isStepActive(step.id) }"
            >
              {{ step.title }}
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- DERECHA: Carga de Documentos -->
    <div class="col-lg-8">
      <div class="card h-100 d-flex flex-column shadow-lg">
        <div class="card-body d-flex flex-column h-100">
          <!-- 🔴 Nota importante HASTA ARRIBA -->
          <div v-if="showReuploadNote" class="alert alert-danger mb-3" role="alert">
            <i class="ti ti-alert-triangle me-2"></i>
            Tu expediente fue rechazado. Por favor verifica que los documentos sean legibles y correspondan a lo solicitado antes de volver a cargarlos.
          </div>

          <!-- Mensaje si no hay documentos -->
          <div v-if="documents.length === 0" class="alert alert-warning" role="alert">
            <i class="ti ti-alert-triangle me-2"></i>
            Aún no se han establecido documentos requeridos para este puesto
          </div>

          <!-- Grid de documentos -->
          <div v-else class="row g-3 flex-grow-1 overflow-auto">
            <div
              v-for="doc in documents"
              :key="doc.key"
              class="col-12 col-sm-6 col-lg-3"
            >
              <div class="card shadow-sm h-100">
                <div class="card-header py-2 d-flex align-items-center">
                  <i :class="(doc.icon || 'ti ti-file-description') + ' me-2'" style="color:#D48232"></i>
                  <h4 class="card-title mb-0 fs-5">
                    {{ doc.title || doc.descripcion || 'Documento' }}
                    <span class="text-danger">*</span>
                  </h4>
                </div>

                <div class="card-body p-2">
                  <!-- Estado visual SIEMPRE -->
                  <div class="mb-2">
                    <span class="badge" :class="statusBadgeClass(doc.id_cat_estatus_documento)">
                      {{ statusBadgeText(doc.id_cat_estatus_documento) }}
                    </span>
                  </div>

                  <!-- Vacío (no local ni servidor) -->
                  <label
                    :for="`file_${doc.key}`"
                    v-if="!files[doc.key] && !serverUploaded[doc.key]"
                    class="dropzone dz-clickable border-2 border-dashed rounded-2 p-2 text-center cursor-pointer w-100"
                    style="min-height: 220px; display:flex; align-items:center; justify-content:center; font-size: 0.95rem;"
                  >
                    <div>
                      <i class="ti ti-cloud-upload text-secondary fs-2"></i>
                      <div class="small mt-1">Seleccionar archivo</div>
                      <div class="text-muted small mt-1">PDF, máx. 2 MB</div>
                    </div>
                  </label>

                  <!-- Cargado (local o servidor) -->
                  <div v-else>
                    <div
                      class="p-2 rounded d-flex align-items-center justify-content-between"
                      :class="serverUploaded[doc.key] ? 'bg-success-lt' : 'bg-muted-lt'"
                    >
                      <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                        <i
                          :class="serverUploaded[doc.key] ? 'ti ti-folder-check text-success' : 'ti ti-file text-secondary'"
                          class="fs-5 me-2"
                        ></i>
                        <div class="min-w-0" style="min-width:0;">
                          <div
                            class="fw-bold small"
                            :class="serverUploaded[doc.key] ? 'text-success' : 'text-secondary'"
                          >
                            {{ serverUploaded[doc.key] ? 'Archivo cargado' : 'Archivo seleccionado' }}
                          </div>
                          <div class="text-secondary small text-truncate" style="max-width:100%;">
                            {{ serverUploaded[doc.key]?.displayName || files[doc.key]?.name || '—' }}
                          </div>
                        </div>
                      </div>
                      <div class="btn-group btn-group-sm flex-shrink-0 ms-2">
                        <!-- Ver (si existe en servidor) -->
                        <a
                          v-if="serverUploaded[doc.key]?.viewUrl"
                          class="btn btn-link text-secondary px-1"
                          :href="serverUploaded[doc.key].viewUrl"
                          target="_blank"
                          rel="noopener"
                          title="Ver"
                        >
                          <i class="ti ti-eye"></i>
                        </a>
                        <!-- Quitar selección local -->
                        <button
                          v-if="!serverUploaded[doc.key]"
                          type="button"
                          class="btn btn-link text-secondary px-1"
                          title="Quitar"
                          @click="removeFile(doc.key)"
                        >
                          <i class="ti ti-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Input (oculto si ya está en servidor) -->
                  <input
                    v-if="!serverUploaded[doc.key]"
                    type="file"
                    class="d-none"
                    :id="`file_${doc.key}`"
                    :accept="accept"
                    @change="onFileChange($event, doc.key)"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Barra acciones inferior -->
          <div class="card shadow-sm mt-3">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap" style="min-height: 90px;">
              <div class="d-flex align-items-center">
                <i class="ti ti-folder-check me-2"></i>
                <div>
                  <div class="small text-secondary">Progreso</div>
                  <div class="fw-semibold">
                    {{ selectedOrServerCount }} / {{ requiredCount }} seleccionados
                  </div>
                </div>
              </div>

              <div class="vr mx-2 d-none d-lg-block"></div>

              <div class="d-flex align-items-center small text-secondary">
                <i class="ti ti-info-circle me-2"></i>
                <template v-if="everyoneUploaded">
                  Archivos cargados correctamente.
                </template>
                <template v-else>
                  Sube los {{ requiredCount }} documentos requeridos para habilitar el envío.
                </template>
              </div>

              <div class="ms-auto d-flex align-items-center gap-2">
                <button
                  v-if="!everyoneUploaded"
                  type="button"
                  :class="['btn', 'btn-primary', { disabled: !allSelected }]"
                  :aria-disabled="!allSelected ? 'true' : null"
                  style="background-color:#55585A; border-color:#55585A;"
                  :title="allSelected ? 'Validar y Enviar' : `Selecciona los {{ requiredCount }} archivos`"
                  @click="submit"
                >
                  <i class="ti ti-upload me-1"></i> Enviar
                </button>
              </div>
            </div>
          </div>
          <!-- /Barra acciones -->
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: Validación de archivos -->
  <div class="modal modal-blur fade" id="modalValidacion" tabindex="-1" aria-hidden="true" ref="modalEl">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content border-0">
        <div class="modal-header" style="background:#f8f9fa;">
          <h3 class="modal-title">
            <i class="ti ti-shield-check me-2"></i> Verificación de archivos
          </h3>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p class="text-secondary mb-3">
  Antes de enviar tus documentos, se verificará que cada archivo tenga un 
  <strong>peso máximo de 2&nbsp;MB</strong> y esté en formato <strong>PDF</strong>.  
  También checaremos que la <strong>fecha establecida</strong> para subir tus archivos 
  aún esté vigente.
</p>


        <!-- Lista de resultados -->
          <div class="list-group mb-3">
            <div
              v-for="item in validationRows"
              :key="item.key"
              class="list-group-item d-flex align-items-center justify-content-between"
            >
              <div class="me-3 text-truncate" style="max-width:60%;">
                <div class="fw-semibold text-truncate">{{ item.name }}</div>
                <div class="small text-secondary">
                  {{ item.sizeMB.toFixed(2) }} MB
                  <span v-if="item.reason" class="text-danger ms-2">• {{ item.reason }}</span>
                </div>
              </div>

              <div>
                <span v-if="item.state === 'idle'" class="badge bg-secondary text-white">Pendiente</span>
                <span v-else-if="item.state === 'checking'" class="badge bg-warning text-white">Verificando…</span>
                <span v-else-if="item.state === 'ok'" class="badge bg-success text-white">OK</span>
                <span v-else-if="item.state === 'fail'" class="badge bg-danger text-white">Error</span>
              </div>
            </div>
          </div>

          <!-- Barra de progreso -->
          <div class="mb-2">
            <div class="small text-secondary d-flex justify-content-between mb-1">
              <span>Progreso</span>
              <span>{{ progressPercent }}%</span>
            </div>
            <div class="progress">
              <div
                class="progress-bar"
                role="progressbar"
                :style="{ width: progressPercent + '%' }"
                :aria-valuenow="progressPercent"
                aria-valuemin="0"
                aria-valuemax="100"
              ></div>
            </div>
          </div>

          <!-- Mensaje general -->
          <div v-if="validationDone" class="mt-3">
            <div v-if="allValid" class="alert alert-success mb-0">
              <i class="ti ti-circle-check me-2"></i> Todos los archivos son válidos. Puedes enviar ahora.
            </div>
            <div v-else class="alert alert-danger mb-0">
              <i class="ti ti-alert-triangle me-2"></i> Corrige los archivos marcados con error y vuelve a intentar.
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button
            class="btn btn-white text-secondary border"
            @click="closeModal"
            :disabled="isValidating || isSubmitting"
          >
            Cancelar
          </button>

          <button
            class="btn btn-secondary"
            @click="validateAndSend"
            :disabled="isValidating || isSubmitting"
            title="Validar y enviar"
          >
            <span
              v-if="isValidating || isSubmitting"
              class="spinner-border spinner-border-sm me-2"
              role="status"
              aria-hidden="true"
            ></span>
            Enviar
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- /MODAL -->
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '@axios'
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { notyf } from '@components/notyf.js'

/* ========= BASE URL y helpers de rutas ========= */
const BASE_URL = (import.meta.env.VITE_BASE_URL || window.APP_BASE_URL || '').replace(/\/+$/, '')

function absolutizeUrl(u) {
  if (!u) return ''
  if (/^https?:\/\//i.test(u)) return u
  if (u.startsWith('/')) return `${BASE_URL}${u}`
  return `${BASE_URL}/${u}`
}
function joinUrl(base, path) {
  const b = (base || '').replace(/\/+$/, '')
  const p = (path || '').replace(/^\/+/, '')
  return p ? `${b}/${p}` : b
}

/* Props y configuración */
const props = defineProps({
  hasPosition: { type: [Boolean, String], default: false },
  positionId:  { type: [Number, String],  default: 0 },
  routes:      { type: Object,            default: () => ({}) },
  csrf:        { type: String,            default: '' }
})
defineOptions({ inheritAttrs: false })

const GLOBAL = window.FOLLOW_PROPS || {}
const PROPS = {
  hasPosition: props.hasPosition ?? GLOBAL.hasPosition ?? false,
  positionId : Number(props.positionId ?? GLOBAL.positionId ?? 0),
  routes     : Object.keys(props.routes || {}).length ? props.routes : (GLOBAL.routes || {}),
  csrf       : props.csrf || GLOBAL.csrf || ''
}

/* Axios */
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
if (PROPS.csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = PROPS.csrf
axios.defaults.withCredentials = true

/* Pasos del proceso */
const processSteps = ref([
  { id: 1, title: 'Inicio',                    description: 'Inicio del trámite de profesionalización.' },
  { id: 2, title: 'Envío a Revisión',          description: 'El expediente fue asignado a revisión.' },
  { id: 3, title: 'Validado por Revisores',    description: 'El expediente fue validado por Revisores.' },
  { id: 4, title: 'Validado por Supervisores', description: 'El expediente fue validado por Supervisores.' },
  { id: 5, title: 'Validado por DGCES',        description: 'El expediente fue validado por DGCES.' },
  { id: 6, title: 'Concluido',                 description: 'El proceso ha finalizado.' }
])

const employeeStatus = ref(0)
function isStepActive(stepId) {
  const s = Number(employeeStatus.value) || 0
  if (s === 6) return false
  if (s === 5) return stepId === 6
  return stepId === s
}

/* Rutas y helpers */
const positionId = Number(PROPS.positionId || 0)

const docsByPositionBase = absolutizeUrl(PROPS.routes?.docsByPositionBase || '/follow/positions')
const docsEndpoint       = joinUrl(docsByPositionBase, `${positionId || 0}/documents`)

const mainEndpoint       = absolutizeUrl(PROPS.routes?.main   || '/follow/main')
const uploadEndpoint     = absolutizeUrl(PROPS.routes?.upload || '/files/upload')

const followBase         = absolutizeUrl(PROPS.routes?.follow || '/follow')
const finalizeEndpoint   = joinUrl(followBase, 'finalize-upload')
const resetUuidsEndpoint = joinUrl(followBase, 'reset-uuids')

function makeViewUrl(filename) {
  const base = absolutizeUrl((PROPS.routes?.cloudViewBase || '/cloud/view').replace(/\/+$/, ''))
  return joinUrl(base, encodeURIComponent(filename))
}

/* Documentos */
const documents = ref([])
const files = ref({})
const accept = '.pdf'
const serverUploaded = ref({})
const idTblProfesionalizacion = ref(0)

const requiredCount = computed(() => documents.value.length)
const selectedOrServerCount = computed(() => {
  const keys = new Set([...Object.keys(files.value), ...Object.keys(serverUploaded.value)])
  return keys.size
})
const serverCount   = computed(() => Object.keys(serverUploaded.value).length)
const allSelected      = computed(() => selectedOrServerCount.value === requiredCount.value)
const everyoneUploaded = computed(() => serverCount.value === requiredCount.value)

/* 🔴 Mostrar nota solo si hay >=1 cargado y faltan otros */
const showReuploadNote = computed(() => {
  const total = requiredCount.value
  const uploaded = serverCount.value
  return total > 0 && uploaded > 0 && uploaded < total
})

function statusBadgeText(s) {
  switch (Number(s)) {
    case 2: return 'Validado'
    case 1: return 'Rechazado'
    case 3: return 'En proceso'
    default: return 'En proceso'
  }
}
function statusBadgeClass(s) {
  switch (Number(s)) {
    case 2: return 'bg-success'
    case 1: return 'bg-danger'
    case 3: return 'bg-warning'
    default: return 'bg-warning'
  }
}

function makeStableKey(d, i) {
  const id = Number(d?.id_ctrl_documentos_profesionalizacion)
  if (Number.isFinite(id) && id > 0) return `ctrl_${id}`
  return d?.key || `tmp_${i}_${Date.now()}`
}
function parseDocIdFromKey(key) {
  const m = String(key).match(/^ctrl_(\d+)$/)
  return m ? Number(m[1]) : 0
}
function docIdFromKey(key) {
  const parsed = parseDocIdFromKey(key)
  if (parsed) return parsed
  const found = documents.value.find(d => d.key === key)
  return Number(found?.id_ctrl_documentos_profesionalizacion || 0)
}

async function loadDocuments() {
  try {
    showSpinner()
    const { data } = await axios.get(docsEndpoint)
    const list = Array.isArray(data?.result?.documents) ? data.result.documents : []
    const withKeys = list.map((d, i) => ({ ...d, key: makeStableKey(d, i) }))
    documents.value = withKeys

    const uploadedNew = {}
    for (const d of withKeys) {
      const uuid = d?.uuid || d?.file_uuid || d?.filename
      if (uuid) {
        uploadedNew[d.key] = {
          viewUrl: makeViewUrl(uuid),
          displayName: uuid
        }
      }
    }
    serverUploaded.value = { ...serverUploaded.value, ...uploadedNew }

    if (!idTblProfesionalizacion.value && withKeys.length) {
      const first = withKeys[0]
      const possible =
        Number(first?.id_tbl_profesionalizacion) ||
        Number(first?.id_profesionalizacion) ||
        Number(first?.id_prof) || 0
      if (possible) idTblProfesionalizacion.value = possible
    }
  } catch {
    notyf.error('No se pudieron cargar los documentos requeridos.')
  } finally {
    hideSpinner()
  }
}

async function loadEmployeeData() {
  try {
    showSpinner()
    const { data } = await axios.post(mainEndpoint)
    const emp = Array.isArray(data?.employee) ? data.employee[0] : data?.employee
    employeeStatus.value = Number(emp?.id_cat_estatus) || 0
    idTblProfesionalizacion.value = Number(emp?.id_tbl_profesionalizacion || 0)
  } catch {
    notyf.error('No se pudo obtener la información del proceso.')
  } finally {
    hideSpinner()
  }
}

/* Reset UUIDs silencioso (si estatus=1) */
const _resetTried = { value: false }
async function resetUuidsIfNeeded() {
  if (_resetTried.value) return
  if (Number(employeeStatus.value) !== 1) return
  _resetTried.value = true
  try {
    await axios.post(resetUuidsEndpoint, {
      id_tbl_profesionalizacion: idTblProfesionalizacion.value || 0,
      _token: PROPS.csrf || undefined
    })
    await loadDocuments()
  } catch {
    // silencioso
  }
}

onMounted(async () => {
  await loadEmployeeData()
  await resetUuidsIfNeeded()
  await loadDocuments()
})

/* Finalizar a estatus 2 */
async function finalizeToStep2() {
  if (!idTblProfesionalizacion.value) return
  try {
    const { data } = await axios.post(finalizeEndpoint, {
      id_tbl_profesionalizacion: idTblProfesionalizacion.value,
      _token: PROPS.csrf || undefined
    })
    const ok = Boolean(data?.ok ?? data?.status)
    const newStatus = Number(data?.new_status ?? data?.data?.new_status ?? 2)
    if (ok) {
      employeeStatus.value = newStatus
      notyf.success('Expediente enviado a revisión.')
    } else if (data?.message) {
      notyf.error(data.message)
    }
  } catch {
    // no bloquear UI
  }
}

/* Manejo de archivos */
function onFileChange(e, key) {
  const f = e?.target?.files?.[0]
  if (f) {
    files.value = { ...files.value, [key]: f }
  } else {
    const { [key]: _, ...rest } = files.value
    files.value = rest
  }
}
function removeFile(key) {
  const { [key]: _, ...rest } = files.value
  files.value = rest
  const inputEl = document.getElementById(`file_${key}`)
  if (inputEl) inputEl.value = ''
}

/* Validación y envío */
const MAX_MB = 2
const modalEl = ref(null)
let bsModal = null

const validationRows = ref([])
const isValidating   = ref(false)
const validationDone = ref(false)
const isSubmitting   = ref(false)

const progressPercent = computed(() => {
  if (!validationRows.value.length) return 0
  const checked = validationRows.value.filter(r => r.state === 'ok' || r.state === 'fail').length
  return Math.round(checked * 100 / validationRows.value.length)
})
const allValid = computed(() =>
  validationRows.value.length > 0 &&
  validationRows.value.every(r => r.state === 'ok')
)

function bytesToMB(bytes) { return bytes / 1024 / 1024 }
function isPdfFile(file) {
  const nameOk = typeof file?.name === 'string' && /\.pdf$/i.test(file.name)
  const typeOk = file?.type === 'application/pdf'
  return nameOk || typeOk
}

function buildValidationRows() {
  const rows = []
  for (const [key, file] of Object.entries(files.value)) {
    rows.push({
      key,
      name: file?.name || 'archivo.pdf',
      sizeMB: bytesToMB(file?.size || 0),
      state: 'idle',
      reason: ''
    })
  }
  validationRows.value = rows
  validationDone.value = false
}

function openModal() {
  if (window.bootstrap && modalEl.value) {
    if (!bsModal) {
      bsModal = new window.bootstrap.Modal(modalEl.value, { backdrop: 'static', keyboard: false })
    }
    bsModal.show()
  } else {
    modalEl.value?.classList.add('show')
    modalEl.value?.setAttribute('style', 'display:block;')
  }
}

function closeModal() {
  if (window.bootstrap && bsModal) bsModal.hide()
  else {
    modalEl.value?.classList.remove('show')
    modalEl.value?.setAttribute('style', 'display:none;')
  }
}

/* Validación local: solo tamaño y PDF */
async function runValidation() {
  isValidating.value = true
  for (const row of validationRows.value) {
    row.state = 'checking'
    row.reason = ''

    if (row.sizeMB > MAX_MB) {
      row.state = 'fail'
      row.reason = 'Excede 2 MB'
      continue
    }

    const file = files.value[row.key]
    if (!file) {
      row.state = 'fail'
      row.reason = 'Archivo no encontrado'
      continue
    }

    if (!isPdfFile(file)) {
      row.state = 'fail'
      row.reason = 'Debe ser PDF'
      continue
    }

    row.state = 'ok'
  }
  isValidating.value = false
  validationDone.value = true

  if (allValid.value) notyf.success('Validación completada. Archivos correctos.')
  else notyf.error('Uno o más archivos no pasaron la validación.')
}

async function submit() {
  if (!allSelected.value) {
    notyf.error(`Selecciona los ${requiredCount.value} archivos requeridos`)
    return
  }
  buildValidationRows()
  openModal()
}

async function validateAndSend() {
  try {
    isSubmitting.value = true
    if (!validationDone.value || !allValid.value) await runValidation()
    if (allValid.value) await submitSend()
    else isSubmitting.value = false
  } catch {
    isSubmitting.value = false
  }
}

async function submitSend() {
  try {
    showSpinner()
    const uploadedNow = {}

    for (const [key, file] of Object.entries(files.value)) {
      const docId = docIdFromKey(key)
      if (!docId) {
        hideSpinner()
        notyf.error('No se encontró el identificador del documento.')
        isSubmitting.value = false
        return
      }

      const form = new FormData()
      form.append('file', file)
      form.append('doc_id', String(docId))
      if (PROPS.csrf) form.append('_token', PROPS.csrf)

      const { data } = await axios.post(uploadEndpoint, form, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })

      const ok = Boolean(data?.ok ?? data?.status)
      if (!ok) {
        hideSpinner()
        notyf.error(data?.message || 'Error al subir archivo.')
        isSubmitting.value = false
        return
      }

      const payload  = data?.data ?? data
      const filename = payload.filename || payload.uuid

      if (filename) {
        // Si backend da viewUrl relativa/absoluta, la normalizamos; si no, la construimos
        const rawView = payload.viewUrl || ''
        const viewUrl = rawView ? absolutizeUrl(rawView) : makeViewUrl(filename)

        uploadedNow[key] = {
          viewUrl,
          displayName: payload.displayName || filename
        }
      }

      if (!idTblProfesionalizacion.value && Number(payload?.id_tbl_profesionalizacion)) {
        idTblProfesionalizacion.value = Number(payload.id_tbl_profesionalizacion)
      }

      if (payload?.finalized) {
        employeeStatus.value = Number(payload.new_status ?? 2)
      }
    }

    serverUploaded.value = { ...serverUploaded.value, ...uploadedNow }
    files.value = {}

    if (Number(employeeStatus.value) === 1) {
      await finalizeToStep2()
    }

    await loadDocuments()

    hideSpinner()
    closeModal()
    notyf.success('Expediente enviado correctamente.')
  } catch (e) {
    hideSpinner()
    const msg = e?.response?.data?.message || 'No se pudo enviar el expediente.'
    notyf.error(msg)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.steps .step-item.active .h4,
.steps .step-item.active .text-secondary {
  color: #111827;
}
.bg-success-lt { background-color: rgba(34,197,94,0.08); }
.bg-muted-lt   { background-color: rgba(107,114,128,0.10); }

.badge.bg-success,
.badge.bg-danger,
.badge.bg-warning { color: #fff !important; }
</style>









