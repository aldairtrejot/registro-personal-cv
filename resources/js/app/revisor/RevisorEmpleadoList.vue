<template>
  <div class="container-xl py-4 imss-theme">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="card imss-card">
          <!-- HEADER (TÍTULO + ACCIONES) -->
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
                <a :href="zipAprobadosUrl" class="btn btn-imss btn-sm imss-btn-fixed">
                  Descargar ZIP aprobados
                </a>

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
                  <th style="width:70px;">#</th>
                  <th>Nombre</th>
                  <th style="width:170px;">CURP</th>
                  <th>Área</th>
                  <th style="width:150px;">Estatus</th>
                  <th class="text-end" style="width:220px;">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="empleadosFiltrados.length === 0">
                  <td colspan="6" class="text-center py-4 text-muted">
                    No hay registros que coincidan con los filtros.
                  </td>
                </tr>

                <tr v-for="(emp, index) in empleadosFiltrados" :key="getId(emp)">
                  <td class="text-muted">{{ index + 1 }}</td>

                  <td>
                    <div class="fw-semibold imss-name">
                      {{ emp.nombre || 'N/D' }}
                    </div>
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
  </div>
</template>

<script>
import axios from '@axios'
import { BASE_URL } from '@/components/url.js'

export default {
  name: 'RevisorEmpleadoList',
  data() {
    return {
      BASE_URL,
      filtros: {
        busqueda: '',
        status: '',
      },
      empleados: [],
      curpDescarga: '',
    }
  },
  computed: {
    zipAprobadosUrl() {
      return `${this.BASE_URL}/revisor/pdf/aprobados.zip`
    },
    curpValida() {
      const c = (this.curpDescarga || '').trim().toUpperCase()
      return c.length === 18
    },
    empleadosFiltrados() {
      const texto = (this.filtros.busqueda || '').trim().toLowerCase()
      const status = this.filtros.status

      return this.empleados.filter((e) => {
        const st = this.statusKey(e)

        const coincideTexto =
          !texto ||
          ((e.nombre || '').toLowerCase().includes(texto)) ||
          ((e.curp || '').toLowerCase().includes(texto)) ||
          ((e.area_adscripcion || e.area || '').toLowerCase().includes(texto)) ||
          ((e.puesto_actual || '').toLowerCase().includes(texto))

        const coincideStatus = !status || st === status
        return coincideTexto && coincideStatus
      })
    },
  },
  methods: {
    getId(emp) {
      return emp?.id_tbl_empleados ?? emp?.id
    },

    statusKey(emp) {
      if (emp?.status) return emp.status
      const n = Number(emp?.estatus_cv)
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
      return `${this.BASE_URL}/revisor/empleados/${id}`
    },

    pdfEmpleadoUrl(id) {
      return `${this.BASE_URL}/revisor/empleados/${id}/pdf`
    },

    descargarPdfPorCurp() {
      const curp = (this.curpDescarga || '').trim().toUpperCase()
      if (curp.length !== 18) return
      window.location.href = `${this.BASE_URL}/revisor/pdf/curp/${encodeURIComponent(curp)}`
    },

    async cargarEmpleados() {
      try {
        // ✅ CAMBIO IMPORTANTE: agregar "/" para que no se vuelva ruta relativa y truene
        const { data } = await axios.get('/api/revisor/empleados', {
          params: {
            q: this.filtros.busqueda || undefined,
            status: this.filtros.status || undefined,
          },
        })
        this.empleados = Array.isArray(data) ? data : []
      } catch (e) {
        console.error('Error al cargar empleados:', e)
        this.empleados = []
      }
    },
  },
  watch: {
    filtros: {
      deep: true,
      handler() {
        this.cargarEmpleados()
      },
    },
  },
  mounted() {
    this.cargarEmpleados()
  },
}
</script>

<style scoped>
/* ✅ Variables (AHORA SÍ FUNCIONAN EN scoped) */
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

.imss-input-wrap {
  position: relative;
}

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

.imss-input-with-icon {
  padding-left: 34px;
}

.imss-input:focus,
.imss-select:focus {
  border-color: rgba(0, 99, 65, 0.55);
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.12);
}

.imss-table-wrap {
  background: #ffffff;
}

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

.imss-table tbody tr:hover {
  background: var(--imss-soft);
}

.imss-name {
  color: var(--imss-ink);
  line-height: 1.2;
}

.imss-meta {
  color: var(--imss-muted);
  font-size: 0.82rem;
  margin-top: 2px;
}

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

.imss-badge-neutral {
  background: #f3f4f6;
  color: #374151;
  border-color: #e5e7eb;
}

.imss-badge-warning {
  background: #fff7ed;
  color: #9a3412;
  border-color: #fed7aa;
}

.imss-badge-success {
  background: #ecfdf5;
  color: #065f46;
  border-color: #a7f3d0;
}

.imss-badge-danger {
  background: #fef2f2;
  color: #991b1b;
  border-color: #fecaca;
}

.imss-footer {
  background: #ffffff;
  border-top: 1px solid var(--imss-border);
  padding: 12px 18px;
}

/* ✅ Botones: SIEMPRE visibles (sin desaparecer en hover/active/focus) */
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

.btn-imss:focus {
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.14) !important;
}

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

.btn-outline-imss:focus {
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.14) !important;
}

@media (max-width: 768px) {
  .imss-curp-box {
    width: 100%;
    min-width: 0;
  }
  .imss-actions {
    width: 100%;
    justify-content: stretch;
  }
  .btn-imss.imss-btn-fixed {
    width: 100%;
  }
}
</style>
