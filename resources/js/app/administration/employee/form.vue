<!-- resources/js/app/administration/employee/form.vue -->
<template>
  <div class="card shadow-lg">
    <div class="card-body">
      <!-- Header: datos del empleado -->
      <div class="mb-3">
        <h4>Editar datos del empleado</h4>
        <p class="text-muted mb-0">Vista informativa del empleado.</p>
      </div>

      <div class="container-fluid">
        <div class="row gx-1">
          <div class="col-md-6 position-relative pe-md-4">
            <div class="pe-3">
              <div class="mb-2"><span class="fw-bold">Nombre:</span> {{ nombreCompleto || '—' }}</div>
              <div class="mb-2"><span class="fw-bold">RFC:</span> {{ rfc || '—' }}</div>
              <div class="mb-2"><span class="fw-bold">CURP:</span> {{ curp || '—' }}</div>
              <div class="mb-2"><span class="fw-bold">Estatus:</span> {{ estatus || '—' }}</div>
              <div class="mb-2"><span class="fw-bold">Entidad:</span> {{ entidad_label || '—' }}</div>
              <div class="mb-2"><span class="fw-bold">Correo:</span> {{ correo || '—' }}</div>
            </div>
          </div>

          <div class="col-md-6 ps-md-3">
            <div class="mb-2"><span class="fw-bold">Rama:</span> {{ rama || '—' }}</div>
            <div class="mb-2"><span class="fw-bold">Puesto:</span> {{ puesto_label || '—' }}</div>
            <div class="mb-2"><span class="fw-bold">Siguiente puesto:</span> {{ siguiente_puesto || '—' }}</div>
            <div class="mb-2"><span class="fw-bold">CLUES:</span> {{ clues || '—' }}</div>
            <div class="mb-2"><span class="fw-bold">Zona:</span> {{ zona || '—' }}</div>
          </div>
        </div>
      </div>

      <!-- Actividad / lista de documentos -->
      <hr class="my-4" />
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Actividad de documentos</h5>
        <small class="text-muted" v-if="docs.length">{{ docs.length }} documento(s) relacionados</small>
      </div>
      <p class="text-muted mt-0">
        Documentos en el repositorio de <em>cloud</em> vinculados a esta profesionalización.
      </p>

      <div v-if="docsLoading" class="text-center py-4">
        <div class="spinner-border" role="status" aria-hidden="true"></div>
        <div class="mt-2 text-muted">Cargando documentos…</div>
      </div>

      <div v-else-if="!docs.length" class="alert alert-light border text-secondary">
        Aún no hay documentos para este expediente.
      </div>

      <div v-else class="list-group list-group-flush list-group-hoverable" ref="docsListRef">
        <div
          v-for="doc in docs"
          :key="`${doc.id}_${doc.uuid || ''}`"
          class="list-group-item"
          :data-doc-id="doc.id"
        >
          <div class="row align-items-center">
            <div class="col-auto">
              <span class="badge" :class="statusDotClass(doc.estatus, doc.id_cat_estatus_documento)"></span>
            </div>
            <div class="col-auto">
              <div class="doc-icon" :title="doc.tipo || 'Documento'"><i class="ti ti-clipboard-text"></i></div>
            </div>
            <div class="col text-truncate">
              <div class="d-block text-secondary text-truncate mt-n1">
                {{ (doc.tipo || 'Documento') }} • {{ formatDate(doc.creado_en) }}
              </div>
            </div>
            <div class="col-auto d-flex align-items-center gap-2 action-fixed">
              <tableButtonEdit
                v-if="doc.url_view || doc.url_preview"
                :href="'javascript:void(0)'"
                color="#621132"
                icon="ti ti-report-search"
                tooltip="Historia de documento"
              />
              <tableButtonEdit
                v-if="doc.url_view || doc.url_preview"
                :href="'javascript:void(0)'"
                color="#10312b"
                icon="ti ti-eye"
                tooltip="Ver documento"
                @click="openInNewTab(doc, $event)"
              />
              <tableButtonEdit
                v-if="doc.can_edit"
                :href="'javascript:void(0)'"
                color="#145DA0"
                icon="ti ti-edit-circle"
                tooltip="Editar"
                @click="openPdfModal(doc, $event)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- ===================================================== -->
      <!--   Verificación de CÉDULA (visible para TODOS)         -->
      <!-- ===================================================== -->
      <div v-if="canUploadCedula" class="card h-100 d-flex flex-column mt-3">
        <div class="card-body d-flex flex-column h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0">
              <i class="ti ti-shield-check me-2"></i> Verificación de cédulas
            </h5>
            <small class="text-muted">{{ cedulaDocs.length }} elemento(s)</small>
          </div>
          <p class="text-muted mt-0">
            Adjunta el <strong>PDF de verificación</strong> de cada cédula (independiente del PDF que cargó el empleado).
            Tamaño máximo: <strong>2&nbsp;MB</strong>.
          </p>

          <div v-if="!cedulaDocs.length" class="alert alert-warning" role="alert">
            No encontramos documentos de cédula. Verifica que el nombre incluya "cédula" o que la clave tenga "CED".
          </div>

          <div v-else class="row g-3 flex-grow-1">
            <div v-for="c in cedulaDocs" :key="'ced-'+c.id" class="col-12 col-sm-6 col-lg-3">
              <div class="card shadow-sm h-100">
                <div class="card-header py-2 d-flex align-items-center">
                  <i :class="(c.icon || 'ti ti-file-description') + ' me-2'"></i>
                  <h4 class="card-title mb-0 fs-6">
                    {{ c.tipo || c.tipo_documento || 'Cédula profesional' }}
                  </h4>
                </div>
                <div class="card-body p-2">
                  <div class="mb-2">
                    <span class="badge" :class="statusBadgeClass(c.id_cat_estatus_documento)">
                      {{ statusBadgeText(c.id_cat_estatus_documento) }}
                    </span>
                  </div>

                  <label
                    v-if="!cedUploaded[c.id] && !c.uuid_verificacion_cedula"
                    :for="`ced_file_${c.id}`"
                    class="dropzone dz-clickable border-2 border-dashed rounded-2 p-2 text-center cursor-pointer w-100"
                    style="min-height: 220px; display:flex; align-items:center; justify-content:center; font-size: 0.95rem;"
                  >
                    <div>
                      <i class="ti ti-cloud-upload text-secondary fs-2"></i>
                      <div class="small mt-1">Seleccionar archivo</div>
                      <div class="text-muted small mt-1">PDF, máx. 2 MB</div>
                    </div>
                  </label>

                  <div v-else>
                    <div
                      class="p-2 rounded d-flex align-items-center justify-content-between"
                      :class="(cedUploaded[c.id] || c.uuid_verificacion_cedula) ? 'bg-success-lt' : 'bg-muted-lt'"
                      aria-live="polite"
                    >
                      <div class="d-flex align-items-center flex-grow-1" style="min-width:0%;">
                        <i
                          :class="(cedUploaded[c.id] || c.uuid_verificacion_cedula) ? 'ti ti-folder-check text-success' : 'ti ti-file text-secondary'"
                          class="fs-5 me-2"
                        ></i>
                        <div class="min-w-0" style="min-width:0%;">
                          <div
                            class="fw-bold small"
                            :class="(cedUploaded[c.id] || c.uuid_verificacion_cedula) ? 'text-success' : 'text-secondary'"
                          >
                            {{ (cedUploaded[c.id] || c.uuid_verificacion_cedula) ? 'Archivo cargado' : 'Archivo seleccionado' }}
                          </div>
                          <div class="text-secondary small text-truncate" style="max-width:100%;">
                            {{ cedUploaded[c.id]?.displayName || c.uuid_verificacion_cedula || '—' }}
                          </div>
                        </div>
                      </div>
                      <div class="btn-group btn-group-sm flex-shrink-0 ms-2">
                        <a
                          v-if="c.url_verif_view || cedUploaded[c.id]?.viewUrl"
                          class="btn btn-link text-secondary px-1"
                          :href="c.url_verif_view || cedUploaded[c.id].viewUrl"
                          target="_blank"
                          rel="noopener"
                          title="Ver"
                        >
                          <i class="ti ti-eye"></i>
                        </a>

                        <!-- Quitar selección local (si aún no se subió) -->
                        <button
                          v-if="!c.uuid_verificacion_cedula && cedUploaded[c.id] && !cedUploaded[c.id].viewUrl"
                          type="button"
                          class="btn btn-link text-secondary px-1"
                          title="Quitar"
                          @click="cedRemoveLocal(c.id)"
                        >
                          <i class="ti ti-trash"></i>
                        </button>

                        <!-- Eliminar verificación ya subida -->
                        <button
                          v-if="(c.uuid_verificacion_cedula || cedUploaded[c.id]?.viewUrl) && canDeleteCedula"
                          type="button"
                          class="btn btn-link text-danger px-1"
                          title="Eliminar verificación"
                          @click="deleteCedula(c)"
                        >
                          <i class="ti ti-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <input
                    v-if="canUploadCedula"
                    type="file"
                    class="d-none"
                    :id="`ced_file_${c.id}`"
                    accept="application/pdf,.pdf"
                    @change="onUploadCedula($event, c)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Observaciones + botones -->
      <hr class="my-4" />
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Observaciones</h5>
      </div>
      <textarea v-model="obsText" rows="4" class="form-control" placeholder="Escribe observaciones (opcional)…"></textarea>

      <div class="text-end mt-3">
        <a href="#" class="btn btn-1 me-2" @click.prevent="confirmAndRun('regresar')">Regresar</a>

        <!-- Rechazar: SIEMPRE habilitado, validación por toast en confirmAndRun -->
        <button
          type="button"
          class="btn btn-primary btn-2 me-2"
          style="background-color: #9F2241;"
          @click="confirmAndRun('enviar')"
        >
          Rechazar
        </button>

        <!-- Cambio de estatus -->
        <button
          type="button"
          class="btn btn-primary btn-3"
          style="background-color: #55585a;"
          @click="confirmAndRun('cambio')"
          :title="clientCanAdvance().reason || backendReason || ''"
        >
        Guardar
        </button>
      </div>
    </div>
  </div>

  <!-- Modal confirmación genérica -->
  <div class="modal fade" id="modal_add_employee" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-body text-center">
          <i class="ti ti-alert-triangle text-warning" style="font-size: 48px;"></i>
          <h3 class="mt-3">¿Quieres continuar?</h3>
          <p class="text-muted">Esta acción <strong>no se puede deshacer</strong>.</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" @click="onConfirmModal">Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal confirmación: eliminar verificación de cédula -->
  <div class="modal fade" id="modal_delete_cedula" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-body text-center">
          <i class="ti ti-alert-triangle text-warning" style="font-size: 48px;"></i>
          <h3 class="mt-3">¿Eliminar verificación de cédula?</h3>
          <p class="text-muted">Esta acción <strong>no se puede deshacer</strong>.</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" @click="onConfirmDeleteCedula">Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal PDF -->
  <PdfPreviewModal
    v-model="showPdfModal"
    :url="modalUrl"
    :base-url="BASE_URL"
    :initial-obs="modalObs"
    :initial-status="modalStatus"
    @save="saveFromModal"
  />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from '@axios'
import { notyf } from '@components/notyf.js'
import { showSpinner, hideSpinner } from '@components/spinner.js'
import tableButtonEdit from '@helpers/table/table-button-edit.vue'
import PdfPreviewModal from './pdfpreviewmodal.vue'

/* ========= BASE URL ========= */
const BASE_URL = (import.meta.env.VITE_BASE_URL || window.APP_BASE_URL || '').replace(/\/+$/, '')
const CEDULA_UPLOAD_ENDPOINT = `${BASE_URL}/employee/cedula/validate`

/* ========= URLs ========= */
function absolutizeUrl(u) {
  if (!u) return null
  if (/^https?:\/\//i.test(u)) return u
  if (u.startsWith('/')) return `${BASE_URL}${u}`
  return `${BASE_URL}/${u}`
}

/* ===== Tipos CÉDULA ===== */
const CEDULA_TYPES = new Set([2, 4])

/* ===== Roles ===== */
const ROLE_ADMIN = 1
const ROLE_REVISOR = 3

/* ===== Rol principal (inyectado por Blade) ===== */
const container = typeof document !== 'undefined' ? document.getElementById('blade_employee_form') : null
const roleFromData = Number(container?.dataset?.userRoleMain || 0)
const userMainRole = Number(window.USER_ROLE_MAIN ?? roleFromData ?? 0)

/* ✅ Todos pueden cargar verificación */
const canUploadCedula = computed(() => true)

/* ✅ Solo Admin(1) y Revisor(3) pueden ELIMINAR verificación */
const canDeleteCedula = computed(() => [ROLE_ADMIN, ROLE_REVISOR].includes(userMainRole))

/* ===== Datos del empleado ===== */
const id = ref(0)
const prof_id = ref(0)
const nombre = ref('')
const primer_apellido = ref('')
const segundo_apellido = ref('')
const rfc = ref('')
const curp = ref('')
const estatus = ref('')
const entidad_label = ref('')
const correo = ref('')
const rama = ref('')
const puesto_label = ref('')
const siguiente_puesto = ref('')
const clues = ref('')
const zona = ref('')

const nombreCompleto = computed(() => {
  const a = (primer_apellido.value || '').trim()
  const b = (segundo_apellido.value || '').trim()
  const c = (nombre.value || '').trim()
  return [a, b, c].filter(Boolean).join(' ')
})

/* ===== Documentos ===== */
const docs = ref([])
const docsLoading = ref(false)
const docsListRef = ref(null)

/* ===== Observaciones ===== */
const obsText = ref('')

/* ===== Modal PDF ===== */
const showPdfModal = ref(false)
const modalUrl = ref('about:blank')
const modalObs = ref('')
const modalStatus = ref('')
const modalDocId = ref(null)

/* ===== Confirmación universal ===== */
const pendingAction = ref(null)

/* ===== Avance controlado por backend ===== */
const backendCanAdvance = ref(false)
const backendReason = ref('')

/* ===== Toasts ===== */
function toastSuccess (msg) { try { notyf.success(msg) } catch { console.log('SUCCESS:', msg) } }
function toastError (msg) { try { notyf.error(msg) } catch { console.error('ERROR:', msg) } }
function toastWarning (msg) { toastError(msg) }
function toastInfo (msg) { toastError(msg) }

/* Helpers */
function readProfIdFromUrl () {
  const parts = window.location.pathname.split('/').filter(Boolean)
  const last = parts[parts.length - 1]
  const n = Number(last)
  return Number.isFinite(n) && n > 0 ? n : 0
}
function pad (n) { return String(n).padStart(2, '0') }
function formatDate (iso) {
  if (!iso) return '—'
  const d = new Date(iso); if (isNaN(d)) return '—'
  const y = d.getFullYear(), m = pad(d.getMonth()+1), dd = pad(d.getDate()), hh=pad(d.getHours()), mm=pad(d.getMinutes())
  return `${y}-${m}-${dd} ${hh}:${mm}`
}
function statusDotClass (statusText, statusId) {
  const id = Number(statusId ?? 0)
  if (id === 1) return 'bg-red'
  if (id === 2) return 'bg-green'
  if (id === 3) return 'bg-yellow'
  const s = String(statusText || '').toLowerCase()
  if (s.includes('rechaz')) return 'bg-red'
  if (s.includes('valid') || s.includes('acept')) return 'bg-green'
  if (s.includes('proces')) return 'bg-yellow'
  if (s.includes('carg')) return 'bg-blue'
  return 'bg-gray'
}
function mapEstatus (raw) {
  const v = String(raw ?? '').toLowerCase().trim()
  if (v === '2' || v === 'validado' || v === 'aceptado' || v === 'aceptar') return 'validado'
  if (v === '1' || v === 'rechazado' || v === 'rechazar') return 'rechazado'
  return ''
}
function mapStatusToId (s) {
  const v = String(s || '').toLowerCase()
  if (v === 'validado' || v === 'aceptado' || v === 'aceptar') return 2
  if (v === 'rechazado' || v === 'rechazar') return 1
  return null
}
function statusBadgeClass (idCatEstatusDoc) {
  const id = Number(idCatEstatusDoc ?? 0)
  if (id === 2) return 'bg-success'
  if (id === 1) return 'bg-danger'
  if (id === 3) return 'bg-warning'
  return 'bg-secondary'
}
function statusBadgeText (idCatEstatusDoc) {
  const id = Number(idCatEstatusDoc ?? 0)
  if (id === 2) return 'Validado'
  if (id === 1) return 'Rechazado'
  if (id === 3) return 'En proceso'
  return 'Sin estatus'
}

/* Abrir documento */
function openInNewTab (doc, ev) {
  ev?.preventDefault?.(); ev?.stopPropagation?.()
  const url = (doc?.url_view || doc?.url_preview || '').toString()
  if (!url) { toastError('Este documento no tiene vista disponible.'); return }
  const a = document.createElement('a'); a.href = url; a.target = '_blank'; a.rel = 'noopener noreferrer'
  document.body.appendChild(a); a.click(); a.remove()
}
function openPdfModal (doc, ev) {
  ev?.preventDefault?.(); ev?.stopPropagation?.()
  const url = (doc?.url_view || doc?.url_preview || '').toString()
  if (!url) { toastError('No se puede previsualizar este documento.'); return }
  modalDocId.value  = doc.id
  modalUrl.value    = url
  modalObs.value    = doc.observaciones || ''
  modalStatus.value = mapEstatus(doc.id_cat_estatus_documento ?? doc.estatus_documento ?? doc.estatus)
  showPdfModal.value = true
}

/* ===== Helpers de validación ===== */
const ACCEPTED_ID = 2
const REJECTED_ID = 1
function isAccepted (d) {
  const id = Number(d?.id_cat_estatus_documento ?? 0)
  const s  = String(d?.estatus || '').toLowerCase()
  return id === ACCEPTED_ID || s === 'validado' || s === 'aceptado'
}
function isRejected (d) {
  const id = Number(d?.id_cat_estatus_documento ?? 0)
  const s  = String(d?.estatus || '').toLowerCase()
  return id === REJECTED_ID
}
function hasAnyRejected () { return (docs.value || []).some(isRejected) }
function allDocsAccepted () {
  const list = docs.value || []
  if (!list.length) return false
  return list.every(isAccepted)
}

/* ===== Backend can-advance ===== */
async function refreshCanAdvance (opts = {}) {
  const notify = !!opts.notify
  if (!prof_id.value) {
    backendCanAdvance.value = false
    backendReason.value = 'Sin prof_id'
    return
  }
  try {
    const { data } = await axios.post(`${BASE_URL}/follow/status/can-advance`, { prof_id: prof_id.value })
    backendCanAdvance.value = !!data?.can_advance
    backendReason.value = data?.reason || ''
    if (notify && !backendCanAdvance.value && backendReason.value) {
      toastInfo(`Aún no puedes avanzar. ${backendReason.value}`)
    }
  } catch {
    backendCanAdvance.value = false
    backendReason.value = 'No se pudo validar en el servidor'
    if (notify) toastInfo('Aún no puedes avanzar. No se pudo validar en el servidor')
  }
}

/* ===== Validación local estricta ===== */
const cedulaDocs = computed(() => (docs.value || []).filter(d => d.is_cedula))
const cedUploaded = ref({}) // { [docId]: { viewUrl, displayName } }

function clientCanAdvance () {
  if (!docs.value.length) {
    return { ok: false, reason: 'No hay documentos en el expediente.' }
  }
  const notGreen = docs.value.filter(d => Number(d.id_cat_estatus_documento) !== 2)
  if (notGreen.length) {
    return { ok: false, reason: 'Faltan documentos por validar (todos deben estar en verde).' }
  }
  const greenWithoutFile = docs.value.filter(d => Number(d.id_cat_estatus_documento) === 2 && !d.uuid)
  if (greenWithoutFile.length) {
    return { ok: false, reason: 'Hay documentos validados sin archivo cargado.' }
  }
  const missingCedulaVerif = cedulaDocs.value.filter(c => !(c.uuid_verificacion_cedula || cedUploaded.value[c.id]?.viewUrl))
  if (missingCedulaVerif.length) {
    return { ok: false, reason: 'Falta subir el PDF de verificación de cédula en al menos un documento.' }
  }
  return { ok: true, reason: '' }
}
const uiCanAdvance = computed(() => {
  const client = clientCanAdvance()
  return backendCanAdvance.value && client.ok
})

/* ===== Avance ===== */
async function onAdvanceFlow () {
  if (!allDocsAccepted()) {
    toastWarning('Aprueba todos los documentos para continuar.')
    return
  }
  try {
    showSpinner()
    const client = clientCanAdvance()
    if (!client.ok) {
      toastWarning(client.reason)
      return
    }
    await refreshCanAdvance({ notify: true })
    if (!backendCanAdvance.value) {
      if (backendReason.value) toastWarning(backendReason.value)
      else toastWarning('Aún no puedes avanzar.')
      return
    }
    const { data } = await axios.post(`${BASE_URL}/follow/status/advance`, {
      prof_id: prof_id.value,
      observaciones: obsText.value?.trim() || null
    })
    if (!data?.status) {
      toastError(data?.message || 'No se pudo avanzar.')
      return
    }
    toastSuccess(`Listo. Avanzamos a: ${data.new_stage_text}`)
    setTimeout(() => { window.location.href = `${BASE_URL || ''}/employee` }, 600)
  } catch {
    toastError('No se pudo cambiar el estatus. Intenta de nuevo.')
  } finally {
    hideSpinner()
  }
}

/* ===== Rechazo ===== */
async function onEnviar () {
  const obs = (obsText.value || '').trim()
  if (!obs) { toastWarning('Escribe la razón del rechazo.'); return }
  if (!hasAnyRejected()) { toastWarning('Debes rechazar al menos un documento para continuar.'); return }

  try {
    showSpinner()
    const { data } = await axios.post(`${BASE_URL}/employee/reject`, {
      prof_id: prof_id.value,
      observacion: obs
    })
    if (!data?.status) { toastError(data?.message || 'No se pudo rechazar el proceso.'); return }
    toastSuccess('El proceso se rechazó correctamente.')
    setTimeout(() => { window.location.href = `${BASE_URL || ''}/employee` }, 600)
  } catch (e) {
    toastError(e?.response?.data?.message || 'No se pudo rechazar el proceso. Intenta de nuevo.')
  } finally {
    hideSpinner()
  }
}
const onCambioEstatus = () => onAdvanceFlow()

/* ===== Confirmación ===== */
function openConfirmModal () {
  if (window.$) {
    window.$('#modal_add_employee').modal('show')
  } else if (window.bootstrap) {
    const el = document.getElementById('modal_add_employee')
    const modal = new window.bootstrap.Modal(el)
    modal.show()
  }
}
async function confirmAndRun (accion) {
  if (accion === 'regresar' || accion === 'cancelar') {
    if (BASE_URL) window.location.href = `${BASE_URL}/employee`
    else {
      const path = window.location.pathname
      const idx = path.indexOf('/employee/')
      const base = idx !== -1 ? path.substring(0, idx) : ''
      window.location.href = `${base}/employee`
    }
    return
  }

  if (accion === 'enviar' && !obsText.value.trim()) { toastWarning('Escribe la razón del rechazo.'); return }
  if (accion === 'enviar' && !hasAnyRejected()) { toastWarning('Debes rechazar al menos un documento para continuar.'); return }
  if (accion === 'cambio' && !allDocsAccepted()) { toastWarning('Aprueba todos los documentos para continuar.'); return }

  pendingAction.value = accion
  openConfirmModal()
}
async function onConfirmModal () {
  try {
    if (window.$) window.$('#modal_add_employee').modal('hide')
    else if (window.bootstrap) {
      const el = document.getElementById('modal_add_employee')
      const modal = window.bootstrap.Modal.getInstance(el) || new window.bootstrap.Modal(el)
      modal.hide()
    }
    const actions = { enviar: onEnviar, cambio: onCambioEstatus }
    const run = actions[pendingAction.value]
    if (run) await run()
  } finally {
    pendingAction.value = null
  }
}

/* ===== Cargas ===== */
async function loadEmployee () {
  showSpinner()
  try {
    const hiddenProf = Number(document.getElementById('prof_id')?.value || 0)
    const fromUrl = readProfIdFromUrl()
    prof_id.value = hiddenProf > 0 ? hiddenProf : fromUrl
    if (prof_id.value <= 0) { toastError('No pudimos identificar el expediente. Vuelve a intentar.'); return }

    const { data } = await axios.post(`${BASE_URL}/employee/get`, { prof_id: prof_id.value })
    if (!data?.status || !data?.result) { toastError('No pudimos cargar el expediente. Intenta de nuevo.'); return }
    const r = data.result
    id.value = Number(r.id || 0)
    nombre.value = r.nombre || ''
    primer_apellido.value = r.primer_apellido || ''
    segundo_apellido.value = r.segundo_apellido || ''
    rfc.value = r.rfc || ''
    curp.value = r.curp || ''
    estatus.value = r.estatus || ''
    entidad_label.value = r.entidad_label || ''
    correo.value = r.correo || ''
    rama.value = r.rama || ''
    puesto_label.value = r.puesto_label || ''
    siguiente_puesto.value = r.siguiente_puesto || ''
    clues.value = r.clues || ''
    zona.value = r.zona || ''
    obsText.value = r.observacion || ''
  } catch (err) {
    if (err?.response?.status === 403) toastError('No tienes acceso a este expediente.')
    else if (err?.response?.status === 404) toastError('No encontramos este expediente.')
    else toastError('No pudimos cargar el expediente. Intenta de nuevo.')
  } finally { hideSpinner() }
}

/* ===== Carga de documentos ===== */
async function loadDocs () {
  docsLoading.value = true
  try {
    if (!prof_id.value) return
    const { data } = await axios.post(`${BASE_URL}/employee/documents`, { prof_id: prof_id.value })
    if (!data?.status) { docs.value = []; return }

    const list = data.result || data.list || []
    docs.value = list.map(d => {
      const uuid = d.uuid ?? null
      const urlView = absolutizeUrl(d.url_view || (uuid ? `/cloud/view/${encodeURIComponent(uuid)}` : null))
      const verUuid = d.uuid_verificacion_cedula ?? null
      const urlVerif = absolutizeUrl(d.url_verif_view || (verUuid ? `/cloud/view/${encodeURIComponent(verUuid)}` : null))
      const idTipo = Number(d.id_cat_tipo_documento ?? 0)

      return {
        id: d.id ?? d.id_ctrl_documentos_profesionalizacion ?? null,
        uuid,
        url_view: urlView,
        uuid_verificacion_cedula: verUuid,
        url_verif_view: urlVerif,
        nombre: d.nombre ?? d.nombre_archivo ?? '(sin nombre)',
        creado_en: d.creado_en ?? null,
        tipo: d.tipo_documento ?? d.tipo ?? 'Documento',
        observaciones: d.observaciones ?? '',
        id_cat_estatus_documento: d.id_cat_estatus_documento ?? null,
        id_cat_tipo_documento: idTipo,
        estatus: mapEstatus(d.id_cat_estatus_documento ?? d.estatus_documento ?? d.estatus),
        url_preview: d.url_preview ? absolutizeUrl(d.url_preview) : null,
        url_download: d.url_download ? absolutizeUrl(d.url_download) : null,
        url_edit: d.url_edit ? absolutizeUrl(d.url_edit) : null,
        can_edit: 'can_edit' in d ? !!d.can_edit : true,
        is_cedula:
          d.is_cedula === true ||
          CEDULA_TYPES.has(idTipo) ||
          /(c[eé]dula|ced\.?|cprof|céd\.?)/i.test(String(d?.tipo_documento || d?.tipo || d?.nombre || '')),
        etag: d.etag ?? d.if_match ?? null,
        hist_version: d.hist_version ?? d.version ?? null,
      }
    })
  } catch { docs.value = [] } 
  finally { docsLoading.value = false }
}

/* ========= Sección CÉDULA ========= */
function cedRemoveLocal (docId) {
  const { [docId]: _, ...rest } = cedUploaded.value
  cedUploaded.value = rest
}

const deleteTarget = ref(null)
function deleteCedula(doc) {
  deleteTarget.value = doc
  if (window.$) {
    window.$('#modal_delete_cedula').modal('show')
  } else if (window.bootstrap) {
    const el = document.getElementById('modal_delete_cedula')
    const modal = new window.bootstrap.Modal(el)
    modal.show()
  }
}
async function onConfirmDeleteCedula () {
  try {
    if (window.$) window.$('#modal_delete_cedula').modal('hide')
    else if (window.bootstrap) {
      const el = document.getElementById('modal_delete_cedula')
      const modal = window.bootstrap.Modal.getInstance(el) || new window.bootstrap.Modal(el)
      modal.hide()
    }

    const doc = deleteTarget.value
    deleteTarget.value = null

    if (!doc?.id) { toastError('Documento inválido.'); return }

    showSpinner()
    const { data } = await axios.post(`${BASE_URL}/employee/cedula/delete`, { doc_id: doc.id })
    if (!data?.status) { toastError(data?.message || 'No se pudo eliminar la verificación.'); return }

    const idx = docs.value.findIndex(d => d.id === doc.id)
    if (idx !== -1) {
      docs.value[idx].uuid_verificacion_cedula = null
      docs.value[idx].url_verif_view = null
    }
    if (cedUploaded.value[doc.id]) {
      const { [doc.id]: _, ...rest } = cedUploaded.value
      cedUploaded.value = rest
    }
    toastSuccess('Verificación eliminada.')
    await refreshCanAdvance()
  } catch (e) {
    toastError(e?.response?.data?.message || 'Error al eliminar la verificación.')
  } finally { hideSpinner() }
}

async function onUploadCedula (ev, doc) {
  try {
    const file = ev?.target?.files?.[0]
    if (!file) return
    if (file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name || '')) {
      toastWarning('Selecciona un archivo PDF.'); ev.target.value=''; return
    }
    if (file.size > 2 * 1024 * 1024) { toastWarning('El PDF no puede superar 2 MB.'); ev.target.value=''; return }

    const fd = new FormData()
    fd.append('file', file)
    fd.append('doc_id', String(doc.id))

    showSpinner()
    const { data } = await axios.post(CEDULA_UPLOAD_ENDPOINT, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (!data?.status) { toastError('No pudimos subir el PDF. Intenta de nuevo.'); return }

    const filename = data.filename || data.uuid || ''
    const viewUrl  = absolutizeUrl(data.viewUrl || (filename ? `/cloud/view/${encodeURIComponent(filename)}` : null))

    const idx = docs.value.findIndex(d => d.id === doc.id)
    if (idx !== -1) {
      docs.value[idx].uuid_verificacion_cedula = filename
      docs.value[idx].url_verif_view = viewUrl
    }
    cedUploaded.value = { ...cedUploaded.value, [doc.id]: { viewUrl, displayName: filename || file.name } }
    toastSuccess('Se cargó el PDF de verificación.')
  } catch { toastError('No pudimos subir el PDF. Intenta de nuevo.') }
  finally { hideSpinner(); if (ev?.target) ev.target.value = '' }
}

/* Delegación click "Historia de documento" */
function onDocsActionClick (e) {
  const btn = e.target.closest('a,button'); if (!btn) return
  const hasHistoryIcon = !!btn.querySelector?.('.ti.ti-report-search') || !!e.target.closest?.('.ti.ti-report-search')
  const tip = btn.getAttribute('title') || btn.getAttribute('data-bs-original-title') || btn.getAttribute('data-bs-title') || btn.getAttribute('aria-label') || btn.getAttribute('data-tooltip') || ''
  if (!hasHistoryIcon && tip.trim() !== 'Historia de documento') return
  e.preventDefault()
  const li = btn.closest('.list-group-item'); const docId = Number(li?.dataset?.docId || 0)
  if (!docId) { toastError('No pudimos abrir la historia de este documento.'); return }
  window.dispatchEvent(new CustomEvent('open-doc-history', { detail: { docId } }))
}

/* Guardar desde modal */
async function saveFromModal ({ observaciones, estatus }) {
  const docId = modalDocId.value
  try {
    if (!docId) { toastError('Documento inválido.'); return }

    const i = docs.value.findIndex(d => d.id === docId)
    const doc = i !== -1 ? docs.value[i] : null

    let ifMatchVal = null
    if (doc?.etag != null && doc.etag !== '') ifMatchVal = doc.etag
    else if (doc?.hist_version != null) ifMatchVal = doc.hist_version

    const estatusId = mapStatusToId(estatus)

    const csrf =
      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
      window.CSRF_TOKEN ||
      null

    showSpinner()

    const payload = {
      doc_id: docId,
      prof_id: prof_id.value,
      observaciones: observaciones ?? '',
      estatus,
      estatus_id: estatusId,
      id_cat_estatus_documento: estatusId,
      hist_seen: Number(doc?.hist_version ?? 0),
    }
    if (ifMatchVal != null && ifMatchVal !== '') {
      payload.if_match = String(ifMatchVal)
    }
    if (csrf) payload._token = csrf

    const { data, status } = await axios.post(
      `${BASE_URL}/employee/document/update`,
      payload,
      { validateStatus: s => s >= 200 && s < 500 }
    )

    if (status === 422) {
      const errs = data?.errors || {}
      const list = Object.entries(errs)
        .map(([k, v]) => Array.isArray(v) ? `• ${k}: ${v.join(' ')}` : `• ${k}: ${v}`)
        .join('\n')
      toastError('Validación: revisa los campos enviados.')
      notyf.error(list || 'Solicitud inválida.')
      return
    }

    if (!data?.status) {
      toastError(data?.message || 'No pudimos guardar los cambios.')
      return
    }

    if (i !== -1) {
      docs.value[i].observaciones = observaciones ?? ''
      docs.value[i].estatus = estatus
      docs.value[i].id_cat_estatus_documento = estatusId
      if (data?.result?.new_etag) docs.value[i].etag = data.result.new_etag
      if (typeof data?.result?.hist_version === 'number') {
        docs.value[i].hist_version = data.result.hist_version
      }
    }

    toastSuccess('Los cambios se guardaron.')
    await refreshCanAdvance()
  } catch (e) {
    if (e?.response?.status === 409) {
      toastWarning('Este documento cambió hace un momento. Actualiza y vuelve a intentar.')
    } else {
      const msg = e?.response?.data?.message || 'No pudimos guardar los cambios. Intenta de nuevo.'
      toastError(msg)
    }
  } finally {
    hideSpinner()
  }
}

/* Mount/Unmount */
onMounted(async () => {
  await loadEmployee()
  await loadDocs()
  await refreshCanAdvance()
  if (docsListRef.value) docsListRef.value.addEventListener('click', onDocsActionClick)
})
onUnmounted(() => {
  if (docsListRef.value) docsListRef.value.removeEventListener('click', onDocsActionClick)
})
</script>

<style>
.list-group-item .list-group-item-actions,
.action-fixed .list-group-item-actions { opacity: 1 !important; visibility: visible !important; }

.action-fixed a, .action-fixed a:hover, .action-fixed a:focus { text-decoration: none !important; border-bottom: 0 !important; }

.doc-icon { width: 28px; height: 28px; border-radius: 8px; background: #f0f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; }
.doc-icon i { font-size: 18px; }

.badge.bg-green { background-color: #22c55e !important; }
.badge.bg-yellow { background-color: #f59e0b !important; }
.badge.bg-blue { background-color: #3b82f6 !important; }
.badge.bg-red { background-color: #ef4444 !important; }
.badge.bg-gray { background-color: #9ca3af !important; }

.badge.bg-success,
.badge.bg-danger,
.badge.bg-warning { color: #fff !important; }

.btn-1 { background: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 600; }
.btn-1:hover { background: #f9fafb; }

.btn-2 { color: #ffffff; border: 1px solid #9F2241 !important; border-radius: 8px; font-weight: 600; }
.btn-2:hover { filter: brightness(0.95); }

.btn-3 { color: #ffffff; border: 1px solid #55585a !important; border-radius: 8px; font-weight: 600; }
.btn-3:hover { filter: brightness(0.95); }

.bg-success-lt { background-color: rgba(34,197,94,0.08); }
.bg-muted-lt   { background-color: rgba(107,114,128,0.10); }

.dropzone { border-color: #d1d5db; }
.dropzone:hover { background: #fafafa; }
</style>










