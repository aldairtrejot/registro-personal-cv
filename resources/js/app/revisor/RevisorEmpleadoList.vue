<template>
  <div class="container-xl py-4 imss-theme">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="card imss-card">
          <!-- HEADER -->
          <div class="card-header imss-card-header">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 w-100">
              <div class="min-w-0">
                <div class="imss-kicker">Módulo Revisor</div>
                <h3 class="imss-title mb-1">Revisión de CV</h3>
                <div class="imss-subtitle">
                  Consulta, valida y descarga fichas curriculares del personal.
                </div>
              </div>

              <div class="imss-actions">
                <!-- ✅ ZIP (modal) -->
                <button
                  type="button"
                  class="btn btn-imss btn-sm imss-btn-fixed"
                  data-bs-toggle="modal"
                  data-bs-target="#modalZipAprobados"
                >
                  Descargar ZIP aprobados
                </button>

                <!-- ✅ CURP -->
                <div class="imss-curp-box">
                  <label class="imss-label">Descarga por CURP</label>
                  <div class="d-flex gap-2 align-items-center">
                    <input
                      v-model.trim="curpDescarga"
                      type="text"
                      class="form-control form-control-sm imss-input"
                      placeholder="CURP (18)"
                      maxlength="18"
                      autocomplete="off"
                    />
                    <button
                      type="button"
                      class="btn btn-outline-imss btn-sm imss-btn-fixed"
                      @click="descargarPdfPorCurp"
                      :disabled="!curpValida"
                      title="Descargar PDF por CURP"
                    >
                      PDF
                    </button>
                  </div>
                  <div class="imss-help" v-if="curpDescarga && !curpValida">
                    La CURP debe tener 18 caracteres.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- FILTROS -->
          <div class="card-body imss-filters">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-md-8">
                <label class="imss-label">Búsqueda</label>
                <div class="imss-input-wrap">
                  <span class="imss-icon" aria-hidden="true">🔎</span>
                  <input
                    v-model="filtros.busqueda"
                    type="text"
                    class="form-control form-control-sm imss-input imss-input-with-icon"
                    placeholder="Buscar por nombre, CURP o área"
                  />
                </div>
              </div>

              <div class="col-12 col-md-4">
                <label class="imss-label">Estatus</label>
                <select v-model="filtros.status" class="form-select form-select-sm imss-select">
                  <option value="">Todos</option>
                  <option value="edicion">En edición</option>
                  <option value="enviado">Enviado</option>
                  <option value="aprobado">Aprobado</option>
                  <option value="rechazado">Rechazado</option>
                </select>
              </div>
            </div>
          </div>

          <!-- TABLA -->
          <div class="table-responsive imss-table-wrap">
            <table class="table align-middle mb-0 imss-table">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th style="width:170px;">CURP</th>
                  <th>Área</th>
                  <th style="width:150px;">Estatus</th>
                  <th class="text-end" style="width:220px;">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="empleadosFiltrados.length === 0">
                  <td colspan="5" class="text-center py-4 text-muted">
                    No hay registros que coincidan con los filtros.
                  </td>
                </tr>

                <tr v-for="(emp, index) in empleadosFiltrados" :key="rowKey(emp, index)">
                  <td>
                    <div class="fw-semibold imss-name">{{ emp.nombre || 'N/D' }}</div>
                  </td>

                  <td>
                    <code class="imss-code">{{ emp.curp || 'N/D' }}</code>
                  </td>

                  <td>
                    {{ emp.area_adscripcion || emp.area || 'N/D' }}
                  </td>

                  <td>
                    <span class="imss-badge" :class="badgeClass(statusKey(emp))">
                      {{ statusLabel(statusKey(emp)) }}
                    </span>
                  </td>

                  <td class="text-end">
                    <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                      <a :href="detalleUrl(getId(emp))" class="btn btn-outline-imss btn-sm imss-btn-fixed">
                        Ver detalle
                      </a>
                      <a :href="pdfEmpleadoUrl(getId(emp))" class="btn btn-outline-success btn-sm imss-btn-fixed">
                        PDF
                      </a>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- FOOTER -->
          <div class="card-footer imss-footer">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
              <div class="text-muted small">
                Mostrando <strong>{{ empleadosFiltrados.length }}</strong> de <strong>{{ empleados.length }}</strong> registro(s).
              </div>
              <div class="text-muted small">
                * Datos cargados desde la base.
              </div>
            </div>
          </div>
        </div>
        <!-- /card -->
      </div>
    </div>

    <!-- ✅ MODAL ZIP (mismo estilo que tu sistema) -->
    <div class="modal modal-blur fade" id="modalZipAprobados" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Descargar ZIP aprobados</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="alert alert-info">
              Selecciona el <b>ejercicio</b> y el <b>trimestre</b> para descargar.
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Ejercicio (año)</label>
                <input type="number" class="form-control" v-model.number="zipFiltro.ejercicio" min="2000" max="2100" />
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Trimestre</label>
                <select class="form-select" v-model.number="zipFiltro.trimestre">
                  <option :value="1">1 (Ene–Mar)</option>
                  <option :value="2">2 (Abr–Jun)</option>
                  <option :value="3">3 (Jul–Sep)</option>
                  <option :value="4">4 (Oct–Dic)</option>
                </select>
              </div>
            </div>

            <div class="mt-3 text-muted small">
              * Si no hay aprobados para ese rango, te avisaremos.
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal" :disabled="zipDescargando">Cancelar</button>
            <button type="button" class="btn btn-success" @click="descargarZipAprobados" :disabled="zipDescargando">
              <span v-if="zipDescargando">Descargando…</span>
              <span v-else>Descargar ZIP</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- /modal -->

    <!-- ✅ TOAST Bootstrap (idéntico al Blade) -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
      <div
        id="toast_cv_zip"
        ref="toastEl"
        class="toast align-items-center text-bg-danger border-0"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
        data-bs-delay="4500"
      >
        <div class="d-flex">
          <div class="toast-body">
            {{ toastMsg || 'No hay CV aprobados para exportar con el rango de fecha seleccionado.' }}
          </div>
          <button
            type="button"
            class="btn-close btn-close-white me-2 m-auto"
            data-bs-dismiss="toast"
            aria-label="Close"
          ></button>
        </div>
      </div>
    </div>
    <!-- /toast -->
  </div>
</template>

<script>
import axios from '@axios'
import { BASE_URL } from '@/components/url.js'

const EJ_MIN = 2000
const EJ_MAX = 2100
const TRIM_OK = [1, 2, 3, 4]

export default {
  name: 'RevisorEmpleadoList',
  data() {
    const now = new Date()
    const month = now.getMonth() + 1
    const trimestreActual = month <= 3 ? 1 : month <= 6 ? 2 : month <= 9 ? 3 : 4

    return {
      BASE_URL,

      filtros: { busqueda: '', status: '' },
      empleados: [],
      curpDescarga: '',

      zipFiltro: { ejercicio: now.getFullYear(), trimestre: trimestreActual },
      zipDescargando: false,

      // Toast
      toastMsg: '',
      _toastInstance: null,

      // estabilidad
      _empReqId: 0,
      _debounceTimer: null,
      _debounceMs: 250,
    }
  },

  computed: {
    curpValida() {
      const c = String(this.curpDescarga || '').trim().toUpperCase()
      return c.length === 18
    },

    empleadosFiltrados() {
      const texto = String(this.filtros.busqueda || '').trim().toLowerCase()
      const status = this.filtros.status

      return (this.empleados || []).filter((e) => {
        const st = this.statusKey(e)

        const nombre = String((e && e.nombre) || '').toLowerCase()
        const curp = String((e && e.curp) || '').toLowerCase()
        const area = String((e && (e.area_adscripcion || e.area)) || '').toLowerCase()
        const puesto = String((e && e.puesto_actual) || '').toLowerCase()

        const coincideTexto =
          !texto ||
          nombre.includes(texto) ||
          curp.includes(texto) ||
          area.includes(texto) ||
          puesto.includes(texto)

        const coincideStatus = !status || st === status
        return coincideTexto && coincideStatus
      })
    },
  },

  methods: {
    // -----------------------------
    // URLs (respeta BASE_URL)
    // -----------------------------
    _joinUrl(base, path) {
      const b = String(base || '').replace(/\/+$/, '')
      const p = String(path || '')
      if (!b) return p
      return b + (p.startsWith('/') ? p : '/' + p)
    },

    _url(path) {
      if (this.BASE_URL) return this._joinUrl(this.BASE_URL, path)
      return path
    },

    // -----------------------------
    // Toast Bootstrap (igual Blade)
    // -----------------------------
    _getToastCtor() {
      const b = window && window.bootstrap ? window.bootstrap : null
      return b && b.Toast ? b.Toast : null
    },

    showToast(message) {
      this.toastMsg = message || ''

      this.$nextTick(() => {
        const Toast = this._getToastCtor()
        const el = this.$refs.toastEl
        if (!Toast || !el) return

        try {
          if (this._toastInstance) {
            this._toastInstance.dispose()
            this._toastInstance = null
          }
        } catch (_) {}

        try {
          this._toastInstance = Toast.getOrCreateInstance(el)
          this._toastInstance.show()
        } catch (_) {}
      })
    },

    // -----------------------------
    // Helpers
    // -----------------------------
    getId(emp) {
      if (emp && typeof emp === 'object') {
        if (emp.id_tbl_empleados != null) return emp.id_tbl_empleados
        if (emp.id != null) return emp.id
      }
      return null
    },

    rowKey(emp, index) {
      const id = this.getId(emp)
      if (id != null) return String(id)
      const curp = emp && emp.curp ? String(emp.curp) : ''
      if (curp) return curp
      return 'row-' + String(index)
    },

    statusKey(emp) {
      if (emp && emp.status) return emp.status
      const n = Number(emp && emp.estatus_cv != null ? emp.estatus_cv : null)

      switch (n) {
        case 1: return 'edicion'
        case 2: return 'enviado'
        case 3: return 'aprobado'
        case 4: return 'rechazado'
        default: return 'sin_cv'
      }
    },

    statusLabel(status) {
      switch (status) {
        case 'edicion': return 'En edición'
        case 'enviado': return 'Enviado'
        case 'aprobado': return 'Aprobado'
        case 'rechazado': return 'Rechazado'
        default: return 'Sin CV'
      }
    },

    badgeClass(status) {
      switch (status) {
        case 'edicion': return 'imss-badge-neutral'
        case 'enviado': return 'imss-badge-warning'
        case 'aprobado': return 'imss-badge-success'
        case 'rechazado': return 'imss-badge-danger'
        default: return 'imss-badge-neutral'
      }
    },

    detalleUrl(id) {
      if (id == null) return '#'
      return this._url('/revisor/empleados/' + encodeURIComponent(String(id)))
    },

    pdfEmpleadoUrl(id) {
      if (id == null) return '#'
      return this._url('/revisor/empleados/' + encodeURIComponent(String(id)) + '/pdf')
    },

    descargarPdfPorCurp() {
      const curp = String(this.curpDescarga || '').trim().toUpperCase()
      if (curp.length !== 18) return
      window.location.href = this._url('/revisor/pdf/curp/' + encodeURIComponent(curp))
    },

    // -----------------------------
    // Modal safe close + unlock
    // -----------------------------
    _getModalCtor() {
      const b = window && window.bootstrap ? window.bootstrap : null
      return b && b.Modal ? b.Modal : null
    },

    _forceUnlockModals() {
      try {
        document.body.classList.remove('modal-open')
        document.body.style.removeProperty('overflow')
        document.body.style.removeProperty('padding-right')
      } catch (_) {}

      try {
        const backdrops = document.querySelectorAll('.modal-backdrop')
        for (let i = 0; i < backdrops.length; i++) {
          try { backdrops[i].remove() } catch (_) {}
        }
      } catch (_) {}
    },

    _closeZipModalSafe() {
      const el = document.getElementById('modalZipAprobados')
      if (!el) return

      const Modal = this._getModalCtor()
      try {
        if (Modal && typeof Modal.getInstance === 'function') {
          const inst = Modal.getInstance(el)
          if (inst && typeof inst.hide === 'function') inst.hide()
        } else {
          const btn = el.querySelector('[data-bs-dismiss="modal"]')
          if (btn && typeof btn.click === 'function') btn.click()
        }
      } catch (_) {}

      window.setTimeout(() => this._forceUnlockModals(), 250)
    },

    // -----------------------------
    // ZIP helpers (para detectar "sin registros" aunque sea 200)
    // -----------------------------
    _isZipContentType(ct = '') {
      const t = String(ct || '').toLowerCase()
      return (
        t.includes('application/zip') ||
        t.includes('application/x-zip-compressed') ||
        t.includes('application/octet-stream')
      )
    },

    // -----------------------------
    // ZIP: fetch -> "sin registros" => toast (aunque sea 200), ok => download
    // -----------------------------
    _zipUrl(ejercicio, trimestre) {
      const base = this._url('/revisor/pdf/aprobados.zip')
      return (
        base +
        '?ejercicio=' + encodeURIComponent(String(ejercicio)) +
        '&trimestre=' + encodeURIComponent(String(trimestre)) +
        '&_ts=' + encodeURIComponent(String(Date.now()))
      )
    },

    async descargarZipAprobados() {
      if (this.zipDescargando) return

      const ejercicio = Number(this.zipFiltro.ejercicio || new Date().getFullYear())
      const trimestre = Number(this.zipFiltro.trimestre || 1)

      if (!(ejercicio >= EJ_MIN && ejercicio <= EJ_MAX)) {
        this.showToast('Ejercicio inválido. Debe estar entre ' + EJ_MIN + ' y ' + EJ_MAX + '.')
        return
      }

      if (TRIM_OK.indexOf(trimestre) === -1) {
        this.showToast('Trimestre inválido. Debe ser 1, 2, 3 o 4.')
        return
      }

      this.zipDescargando = true
      const url = this._zipUrl(ejercicio, trimestre)

      // cerrar modal primero
      this._closeZipModalSafe()

      try {
        const resp = await fetch(url, {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })

        // ✅ backend manda error http (ideal)
        if (!resp.ok) {
          if (resp.status === 404) {
            this.showToast('No hay CV aprobados para exportar con el rango de fecha seleccionado.')
          } else if (resp.status === 422) {
            this.showToast('Parámetros inválidos. Verifica ejercicio y trimestre.')
          } else {
            this.showToast('No se pudo generar el ZIP. Revisa el servidor.')
          }
          return
        }

        // ✅ 204 no content => sin registros
        if (resp.status === 204) {
          this.showToast('No hay CV aprobados para exportar con el rango de fecha seleccionado.')
          return
        }

        const ct = (resp.headers.get('Content-Type') || resp.headers.get('content-type') || '').toLowerCase()

        // ✅ a veces el backend responde JSON con "sin datos"
        if (ct.includes('application/json')) {
          let payload = null
          try { payload = await resp.json() } catch (_) {}
          const msg =
            payload?.message ||
            payload?.mensaje ||
            'No hay CV aprobados para exportar con el rango de fecha seleccionado.'
          this.showToast(msg)
          return
        }

        // ✅ a veces regresa HTML (login/error page)
        if (ct.includes('text/html')) {
          this.showToast('No hay CV aprobados para exportar con el rango de fecha seleccionado.')
          return
        }

        // ✅ si viene content-type raro (no zip) avisamos en vez de bajar basura
        if (ct && !this._isZipContentType(ct)) {
          this.showToast('No se encontró información para el rango seleccionado.')
          return
        }

        const blob = await resp.blob()

        // ✅ blob vacío o "zip vacío" muy pequeño
        // (umbral conservador: si tu zip vacío pesa más, ajusta)
        if (!blob || blob.size === 0 || blob.size < 200) {
          this.showToast('No hay CV aprobados para exportar con el rango de fecha seleccionado.')
          return
        }

        // ✅ validar firma ZIP: "PK"
        try {
          const headBuf = await blob.slice(0, 2).arrayBuffer()
          const sig = new Uint8Array(headBuf)
          const isPK = sig[0] === 0x50 && sig[1] === 0x4b
          if (!isPK) {
            this.showToast('No hay CV aprobados para exportar con el rango de fecha seleccionado.')
            return
          }
        } catch (_) {
          // si no pudimos validar, seguimos (pero rara vez pasa)
        }

        let filename = 'aprobados.zip'
        const cd = resp.headers.get('Content-Disposition') || resp.headers.get('content-disposition') || ''
        const match = cd.match(/filename="([^"]+)"/i)
        if (match && match[1]) filename = match[1]

        const a = document.createElement('a')
        const objectUrl = window.URL.createObjectURL(blob)
        a.href = objectUrl
        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(objectUrl)

      } catch (e) {
        console.error(e)
        this.showToast('No se pudo descargar el ZIP. Intenta nuevamente.')
      } finally {
        this.zipDescargando = false
        this._forceUnlockModals()
      }
    },

    // -----------------------------
    // Data
    // -----------------------------
    scheduleCargarEmpleados() {
      try { if (this._debounceTimer) clearTimeout(this._debounceTimer) } catch (_) {}
      this._debounceTimer = setTimeout(() => {
        this.cargarEmpleados()
      }, this._debounceMs)
    },

    async cargarEmpleados() {
      const reqId = ++this._empReqId

      try {
        const apiUrl = this._url('/api/revisor/empleados')
        const { data } = await axios.get(apiUrl, {
          params: {
            q: this.filtros.busqueda || undefined,
            status: this.filtros.status || undefined,
          },
        })

        if (reqId !== this._empReqId) return
        this.empleados = Array.isArray(data) ? data : []
      } catch (e) {
        if (reqId !== this._empReqId) return
        console.error('Error al cargar empleados:', e)
        this.empleados = []
      }
    },

    _cleanupTimers() {
      try { if (this._debounceTimer) clearTimeout(this._debounceTimer) } catch (_) {}
      try {
        if (this._toastInstance) {
          this._toastInstance.dispose()
          this._toastInstance = null
        }
      } catch (_) {}
    },
  },

  watch: {
    filtros: {
      deep: true,
      handler() {
        this.scheduleCargarEmpleados()
      },
    },
  },

  mounted() {
    this.cargarEmpleados()
  },

  beforeUnmount() {
    this._cleanupTimers()
  },

  beforeDestroy() {
    this._cleanupTimers()
  },
}
</script>

<style scoped>
/* ✅ Variables */
.imss-theme {
  --imss-green: #006341;
  --imss-green-2: #0b7a53;
  --imss-ink: #10312b;
  --imss-muted: #6b7280;
  --imss-bg: #f7f9fb;
  --imss-border: #e5e7eb;
  --imss-soft: #eef5f2;
}

.imss-card {
  border: 1px solid var(--imss-border);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 10px 28px rgba(16, 49, 43, 0.06);
}

.imss-card-header {
  background: #ffffff;
  border-bottom: 1px solid var(--imss-border);
  padding: 18px 18px;
}

.imss-kicker {
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--imss-muted);
  margin-bottom: 4px;
}

.imss-title {
  color: var(--imss-ink);
  font-weight: 700;
  font-size: 1.2rem;
}

.imss-subtitle {
  color: var(--imss-muted);
  font-size: 0.9rem;
  max-width: 60ch;
}

.imss-actions {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.imss-curp-box {
  min-width: 280px;
  padding: 10px 12px;
  border: 1px solid var(--imss-border);
  border-radius: 12px;
  background: var(--imss-bg);
}

.imss-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--imss-ink);
  margin-bottom: 6px;
}

.imss-help {
  margin-top: 6px;
  font-size: 0.78rem;
  color: #b91c1c;
}

.imss-filters {
  background: var(--imss-bg);
  border-bottom: 1px solid var(--imss-border);
  padding: 14px 18px;
}

.imss-input-wrap { position: relative; }

.imss-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.9rem;
  opacity: 0.75;
}

.imss-input,
.imss-select {
  border-radius: 12px;
  border-color: var(--imss-border);
}

.imss-input-with-icon { padding-left: 34px; }

.imss-input:focus,
.imss-select:focus {
  border-color: rgba(0, 99, 65, 0.55);
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.12);
}

.imss-table-wrap { background: #ffffff; }

.imss-table thead th {
  background: #0f2f2a;
  color: #ffffff;
  font-size: 0.78rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  border-bottom: 0;
  padding: 12px 14px;
}

.imss-table tbody td {
  padding: 14px 14px;
  border-color: var(--imss-border);
}

.imss-table tbody tr:hover { background: var(--imss-soft); }

.imss-name { color: var(--imss-ink); line-height: 1.2; }

.imss-code {
  background: #0b1120;
  color: #e5e7eb;
  border-radius: 8px;
  padding: 3px 8px;
  font-size: 0.78rem;
}

.imss-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.78rem;
  font-weight: 700;
  border: 1px solid transparent;
}

.imss-badge-neutral { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.imss-badge-warning { background: #fff7ed; color: #9a3412; border-color: #fed7aa; }
.imss-badge-success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.imss-badge-danger  { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

.imss-footer {
  background: #ffffff;
  border-top: 1px solid var(--imss-border);
  padding: 12px 18px;
}

/* Botones */
.imss-btn-fixed {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  min-height: 34px;
}

.btn-imss {
  background: var(--imss-green) !important;
  border-color: var(--imss-green) !important;
  color: #ffffff !important;
  border-radius: 12px;
  font-weight: 700;
  padding: 8px 12px;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.btn-imss:hover,
.btn-imss:focus,
.btn-imss:active {
  background: var(--imss-green-2) !important;
  border-color: var(--imss-green-2) !important;
  color: #ffffff !important;
  opacity: 1 !important;
}

.btn-imss:focus { box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.14) !important; }

.btn-outline-imss {
  border-color: rgba(0, 99, 65, 0.55) !important;
  color: var(--imss-green) !important;
  border-radius: 12px;
  font-weight: 700;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.btn-outline-imss:hover,
.btn-outline-imss:focus,
.btn-outline-imss:active {
  background: var(--imss-green) !important;
  border-color: var(--imss-green) !important;
  color: #ffffff !important;
  opacity: 1 !important;
}

.btn-outline-imss:focus { box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.14) !important; }

@media (max-width: 768px) {
  .imss-curp-box { width: 100%; min-width: 0; }
  .imss-actions { width: 100%; justify-content: stretch; }
  .btn-imss.imss-btn-fixed { width: 100%; }
}
</style>