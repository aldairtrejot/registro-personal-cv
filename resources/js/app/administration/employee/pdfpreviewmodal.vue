<!-- resources/js/app/administration/employee/pdfpreviewmodal.vue -->
<template>
  <div v-if="modelValue" class="custom-modal-overlay" @click.self="close">
    <div class="custom-modal" role="dialog" aria-modal="true">
      <div class="custom-modal-header">
        <h3 class="m-0">
          <i class="ti ti-file-description me-2"></i> Vista previa del documento
        </h3>
        <button type="button" class="btn-close" @click="close" aria-label="Cerrar"></button>
      </div>

      <div class="custom-modal-body">
        <!-- GRID de 2 columnas -->
        <div class="two-col-grid">
          <!-- Izquierda: Observaciones / Estatus -->
          <div class="left-panel">
            <div class="card h-100 shadow-sm card-gray-border">
              <div class="card-header" style="background:#fff;">
                <h3 class="card-title mb-0" style="color:#212529;">Revisión del documento</h3>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label required">Comentarios</label>
                  <textarea
                    v-model="localObs"
                    class="form-control"
                    rows="6"
                    placeholder="Escribe tus comentarios…"
                  ></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label required">Resultado</label>

                  <!-- Radios inline estilo Tabler-like -->
                  <div class="form-check-group-inline">
                    <label class="form-check form-check-inline">
                      <input
                        class="form-check-input radio-gold"
                        type="radio"
                        name="estatusDoc"
                        value="validado"
                        v-model="localStatus"
                      />
                      <span class="form-check-label">Aprobar</span>
                    </label>

                    <label class="form-check form-check-inline">
                      <input
                        class="form-check-input radio-silver"
                        type="radio"
                        name="estatusDoc"
                        value="rechazado"
                        v-model="localStatus"
                      />
                      <span class="form-check-label">Rechazar</span>
                    </label>
                  </div>
                </div>

                <div class="d-grid">
                  <button type="button" class="btn btn-primary" @click="saveAndClose">
                    <i class="ti ti-check me-1"></i> Guardar cambios
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Derecha: Visor PDF + zoom -->
          <div class="right-panel">
            <div class="card h-100 shadow-sm card-gray-border">
              <div class="card-header d-flex align-items-center justify-content-between" style="background:#fff;">
                <h3 class="card-title mb-0" style="color:#212529;">Documento PDF</h3>
                <span class="badge bg-gray-soft text-dark">Vista en esta ventana</span>
              </div>

              <div class="card-body d-flex flex-col">
                <div class="pdf-viewport border rounded flex-1">
                  <!-- Spinner overlay mientras carga el PDF -->
                  <div v-if="isLoadingPdf" class="pdf-loader-overlay" aria-live="polite">
                    <div class="spinner-border" role="status" aria-label="Cargando el documento…"></div>
                    <div class="loader-text mt-2">Cargando el documento…</div>
                  </div>

                  <div class="pdf-scale" :style="scaleStyle">
                    <iframe
                      class="w-100 h-100"
                      style="border:0;"
                      :src="resolvedUrl"
                      :key="resolvedUrl"
                      title="Previsualización PDF"
                      @load="onPdfLoad"
                    ></iframe>
                  </div>
                </div>

                <div class="d-flex justify-content-center align-items-center gap-2 mt-3">
                  <button type="button" class="btn btn-zoom btn-icon" title="Alejar" @click="zoomOut">
                    <i class="ti ti-zoom-out"></i>
                  </button>
                  <span class="zoom-badge-gray">{{ zoomPct }}</span>
                  <button type="button" class="btn btn-zoom btn-icon" title="Acercar" @click="zoomIn">
                    <i class="ti ti-zoom-in"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div> <!-- /two-col-grid -->
      </div> <!-- /custom-modal-body -->
    </div> <!-- /custom-modal -->
  </div> <!-- /custom-modal-overlay -->
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  modelValue:     { type: Boolean, default: false }, // v-model
  url:            { type: String,  default: '' },    // URL directa (abs/rel)
  filename:       { type: String,  default: '' },    // alternativamente, solo filename
  baseUrl:        { type: String,  default: '' },    // prefijo para rutas relativas
  // 👇 Se llena desde form.vue con d.observaciones (profesionalizacion.ctrl_documentos_profesionalizacion.observaciones)
  initialObs:     { type: String,  default: '' },
  // 'aceptado'|'validado'|'rechazado'|'rechazar'
  initialStatus:  { type: String,  default: '' },
})

const emit = defineEmits(['update:modelValue', 'save'])

const localObs    = ref(props.initialObs || '')
const localStatus = ref(normalizeStatus(props.initialStatus) || 'validado')

function normalizeStatus(val) {
  const v = String(val || '').toLowerCase().trim()
  if (v === 'aceptado' || v === 'aceptar') return 'validado'
  if (v === 'rechazar') return 'rechazado'
  return v
}

// ===== Spinner de carga PDF =====
const isLoadingPdf = ref(false)
let loadTimeout = null

function armTimeout() {
  // Failsafe: si el onload no dispara, ocultar spinner después de 12s
  clearTimeout(loadTimeout)
  loadTimeout = setTimeout(() => { isLoadingPdf.value = false }, 12000)
}

function onPdfLoad() {
  clearTimeout(loadTimeout)
  isLoadingPdf.value = false
}

// Zoom
const zoom = ref(1.0)
const MIN = 0.5, MAX = 2.0, STEP = 0.1
const zoomPct = computed(() => `${Math.round(zoom.value * 100)}%`)
const scaleStyle = computed(() => ({
  transform: `scale(${zoom.value})`,
  transformOrigin: '0 0',
  width: `${zoom.value * 100}%`,
  height: `${zoom.value * 100}%`,
}))

// URL final (respeta /cloud/view/{filename})
const resolvedUrl = computed(() => {
  const base = (props.baseUrl || '').replace(/\/+$/, '')
  let u = props.url?.trim()

  if (!u && props.filename) {
    u = `${base ? base : ''}${base ? '/cloud/view/' : '/cloud/view/'}${encodeURIComponent(props.filename)}`
  } else if (u) {
    if (!/^https?:\/\//i.test(u) && base) {
      u = `${base}${u.startsWith('/') ? '' : '/'}${u}`
    }
  }

  if (!u) return 'about:blank'
  return u.includes('#') ? u : `${u}#toolbar=1&view=FitH`
})

function zoomIn(){ zoom.value = Math.min(MAX, +(zoom.value + STEP).toFixed(2)) }
function zoomOut(){ zoom.value = Math.max(MIN, +(zoom.value - STEP).toFixed(2)) }

function close(){ emit('update:modelValue', false) }

function saveAndClose(){
  emit('save', {
    observaciones: (localObs.value ?? '').trim(),
    estatus: localStatus.value
  })
  close()
}

/* 
 * Sincroniza SIEMPRE las observaciones que llegan del padre:
 * - Al abrir el modal (modelValue -> true)
 * - Si initialObs cambia mientras el modal está abierto
 */
watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      zoom.value        = 1.0
      localObs.value    = props.initialObs || ''
      localStatus.value = normalizeStatus(props.initialStatus) || 'validado'
      // Al abrir, si hay una URL real, mostrar spinner
      if (resolvedUrl.value && resolvedUrl.value !== 'about:blank') {
        isLoadingPdf.value = true
        armTimeout()
      } else {
        isLoadingPdf.value = false
      }
    } else {
      clearTimeout(loadTimeout)
    }
  }
)

// Si cambia la URL/archivo mientras está abierto, volver a mostrar spinner
watch(
  () => resolvedUrl.value,
  (u) => {
    if (props.modelValue) {
      if (u && u !== 'about:blank') {
        isLoadingPdf.value = true
        armTimeout()
      } else {
        isLoadingPdf.value = false
      }
    }
  }
)

// Si cambian las observaciones en el padre mientras el modal está abierto, reflejarlo.
watch(
  () => props.initialObs,
  (newVal) => {
    if (props.modelValue) {
      localObs.value = newVal || ''
    }
  }
)
</script>

<style>
/* ===== Modal (sin Bootstrap) ===== */
.custom-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}
.custom-modal {
  width: min(1280px, 96vw);
  max-height: 92vh;
  background: #f8f9fa;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 15px 45px rgba(0,0,0,.2);
  display: flex;
  flex-direction: column;
}
.custom-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  padding: .85rem 1rem;
  background: #ffffff;
  border-bottom: 1px solid #eceeef;
  color: #212529;
}
.custom-modal-body {
  padding: 1rem;
  overflow: auto;
}

/* ===== GRID 2 columnas ===== */
.two-col-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
@media (min-width: 992px) {
  .two-col-grid {
    grid-template-columns: 420px 1fr;
    align-items: stretch;
  }
}
.left-panel, .right-panel { min-width: 0; }

/* Radios inline */
.form-check-group-inline {
  display: flex;
  flex-wrap: wrap;
  gap: .75rem 1rem;
  align-items: center;
}
.form-check { display: inline-flex; align-items: center; gap: .45rem; cursor: pointer; user-select: none; }
.form-check-inline { margin-right: 0; }
.form-check-input {
  width: 1.05rem;
  height: 1.05rem;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
  outline: none;
  accent-color: #6b7280;
}
.form-check-label { color: #111827; }

/* Dorado / Plateado */
.radio-gold   { accent-color: #C6A24A; }
.radio-silver { accent-color: #C0C0C0; }

/* Visor PDF y zoom */
.flex-col { display: flex; flex-direction: column; }
.flex-1 { flex: 1 1 auto; min-height: 0; }
.pdf-viewport {
  position: relative;           /* para overlay */
  height: 64vh;
  overflow: auto;
  background: #F8FAFC;
  border-color: #E4E4E7 !important;
}
.pdf-scale { width: 100%; height: 100%; transform-origin: 0 0; }

/* Spinner overlay */
.pdf-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  background: linear-gradient(180deg, rgba(248,250,252,0.95), rgba(248,250,252,0.85));
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.loader-text { color: #3D3D3D; font-weight: 600; }

/* Botones/zoom */
.btn-icon {
  width: 40px; height: 40px; padding: 0;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: .5rem; font-size: 1.05rem;
}
.btn-zoom { background-color: #E4E4E7; border-color: #E4E4E7; color: #3D3D3D; }
.btn-zoom:hover { background-color: #848487; border-color: #848487; color: #fff; }
.zoom-badge-gray {
  display: inline-block; padding: .2rem .6rem; border-radius: .5rem;
  background: #F2F2F3; color: #3D3D3D; font-size: .9rem; font-weight: 600;
  min-width: 3.2rem; text-align: center; border: 1px solid #E4E4E7;
}

/* Acentos grises */
.btn.btn-primary { background-color: #98989a; border-color: #98989a; color: #fff; }
.btn.btn-primary:hover { background-color: #848487; border-color: #848487; color: #fff; }
.card-gray-border { border: 1px solid #E4E4E7; }
.bg-gray-soft { background-color: #F2F2F3 !important; }
.form-label.required::after { content: " *"; color: #6F6F73; font-weight: 600; }
</style>