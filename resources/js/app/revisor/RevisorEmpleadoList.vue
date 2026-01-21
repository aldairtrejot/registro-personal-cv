<template>
  <div class="container-xl py-4">
    <div class="row row-cards">
      <div class="col-12">
        <div class="card shadow-sm border-0 revisor-card">
          <!-- Header -->
          <div
            class="card-header d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-3 revisor-card-header"
          >
            <div class="revisor-header-text">
              <h3 class="card-title mb-1 revisor-title">Revisión de CV</h3>
              <div class="text-muted small">
                Consulta y revisa los CV capturados por el personal.
              </div>

              <!-- ✅ Acciones rápidas -->
              <div class="mt-3 d-flex flex-column flex-sm-row gap-2 revisor-actions">
                <a
                  class="btn btn-success btn-sm"
                  :href="`${BASE_URL}/revisor/pdf/aprobados.zip`"
                  target="_blank"
                  rel="noopener"
                >
                  Descargar ZIP de aprobados
                </a>

                <!-- PDF por CURP -->
                <div class="d-flex gap-2 align-items-center revisor-curp-box">
                  <input
                    v-model.trim="curpPdf"
                    type="text"
                    class="form-control form-control-sm revisor-curp-input"
                    maxlength="18"
                    placeholder="CURP para PDF"
                  />
                  <button
                    class="btn btn-outline-primary btn-sm"
                    type="button"
                    :disabled="!curpPdf || curpPdf.length < 10"
                    @click="descargarPdfPorCurp"
                  >
                    PDF por CURP
                  </button>
                </div>
              </div>
            </div>

            <!-- Filtros -->
            <div
              class="ms-md-auto d-flex flex-column flex-md-row align-items-stretch gap-3 revisor-filters"
            >
              <!-- Búsqueda -->
              <div class="revisor-filter-block">
                <label class="revisor-filter-label">Buscar empleado</label>
                <div class="revisor-filter-input-group">
                  <span class="revisor-filter-icon">
                    <i class="ti ti-search" aria-hidden="true"></i>
                  </span>
                  <input
                    v-model="filtros.busqueda"
                    type="text"
                    class="form-control form-control-sm revisor-filter-input"
                    placeholder="Nombre, CURP o área"
                  />
                </div>
              </div>

              <!-- Estatus -->
              <div class="revisor-filter-block">
                <label class="revisor-filter-label">Estatus</label>
                <select
                  v-model="filtros.status"
                  class="form-select form-select-sm revisor-filter-select"
                >
                  <option value="">Todos los estatus</option>
                  <option value="edicion">En edición</option>
                  <option value="enviado">Enviado</option>
                  <option value="aprobado">Aprobado</option>
                  <option value="rechazado">Rechazado</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table class="table table-vcenter card-table mb-0 revisor-table">
              <thead>
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>Nombre</th>
                  <th>CURP</th>
                  <th>Área</th>
                  <!-- <th>Fecha de captura</th> -->
                  <th>Estatus</th>
                  <th class="w-1 text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="empleadosFiltrados.length === 0">
                  <td colspan="7" class="text-center text-muted py-4">
                    No hay empleados que coincidan con los filtros.
                  </td>
                </tr>

                <tr
                  v-for="(emp, index) in empleadosFiltrados"
                  :key="emp.id"
                  class="align-middle"
                >
                  <td>{{ index + 1 }}</td>
                  <td class="fw-semibold">{{ emp.nombre }}</td>
                  <td><code>{{ emp.curp }}</code></td>
                  <td>{{ emp.area || 'N/D' }}</td>
                  <!-- <td>{{ emp.fechaCaptura || 'N/D' }}</td> -->
                  <td>
                    <span :class="badgeClass(emp.status)" class="revisor-status-badge">
                      {{ statusLabel(emp.status) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <a
                      :href="detalleUrl(emp.id)"
                      class="btn btn-imss-outline btn-sm revisor-btn"
                    >
                      Ver detalle
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer -->
          <div
            class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center revisor-footer"
          >
            <div class="text-muted small mb-2 mb-md-0">
              Mostrando <strong>{{ empleadosFiltrados.length }}</strong> registro(s) de
              <strong>{{ empleados.length }}</strong>.
            </div>
            <div class="text-muted small">
              * Información cargada desde la base de datos.
            </div>
          </div>
        </div>
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
      curpPdf: '',
      filtros: {
        busqueda: '',
        status: '',
      },
      empleados: [],
    }
  },
  computed: {
    empleadosFiltrados() {
      const texto = this.filtros.busqueda.trim().toLowerCase()
      const status = this.filtros.status

      return this.empleados.filter((e) => {
        const coincideTexto =
          !texto ||
          (e.nombre && e.nombre.toLowerCase().includes(texto)) ||
          (e.curp && e.curp.toLowerCase().includes(texto)) ||
          ((e.area || '').toLowerCase().includes(texto))

        const coincideStatus = !status || e.status === status

        return coincideTexto && coincideStatus
      })
    },
  },
  methods: {
    statusLabel(status) {
      switch (status) {
        case 'edicion':
          return 'En edición'
        case 'enviado':
          return 'Enviado'
        case 'aprobado':
          return 'Aprobado'
        case 'rechazado':
          return 'Rechazado'
        default:
          return 'Sin CV'
      }
    },
    badgeClass(status) {
      switch (status) {
        case 'edicion':
          return 'badge bg-secondary'
        case 'enviado':
          return 'badge bg-warning'
        case 'aprobado':
          return 'badge bg-success'
        case 'rechazado':
          return 'badge bg-danger'
        default:
          return 'badge bg-secondary'
      }
    },
    detalleUrl(id) {
      return `${this.BASE_URL}/revisor/empleados/${id}`
    },
    descargarPdfPorCurp() {
      const curp = (this.curpPdf || '').trim().toUpperCase()
      if (!curp) return
      const url = `${this.BASE_URL}/revisor/pdf/curp/${encodeURIComponent(curp)}`
      window.open(url, '_blank', 'noopener')
    },
    async cargarEmpleados() {
      try {
        const { data } = await axios.get('api/revisor/empleados', {
          params: {
            q: this.filtros.busqueda || undefined,
            status: this.filtros.status || undefined,
          },
        })
        this.empleados = data
      } catch (e) {
        console.error('Error al cargar empleados:', e)
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
/* Colores institucionales */
:root {
  --imss-green: #006341;
  --imss-green-dark: #004b2e;
  --imss-green-soft: #e6f2ee;
  --imss-dark: #10312b;
}

/* Card general */
.revisor-card {
  border-radius: 14px;
}

/* Header */
.revisor-card-header {
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

.revisor-title {
  color: var(--imss-dark);
  font-weight: 600;
}

.revisor-header-text {
  max-width: 460px;
}

/* ✅ Acciones rápidas */
.revisor-actions {
  flex-wrap: wrap;
}

.revisor-curp-box {
  width: 100%;
}

.revisor-curp-input {
  min-width: 200px;
  border-radius: 999px;
}

/* Filtros */
.revisor-filters {
  min-width: 260px;
}

.revisor-filter-block {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.revisor-filter-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--imss-dark);
  font-weight: 600;
}

.revisor-filter-input-group {
  display: flex;
  align-items: center;
  gap: 6px;
}

.revisor-filter-icon {
  width: 32px;
  height: 32px;
  border-radius: 999px;
  background: var(--imss-green-soft);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--imss-green);
  font-size: 0.9rem;
}

.revisor-filter-input,
.revisor-filter-select {
  border-radius: 999px;
  border-color: #d1d5db;
  font-size: 0.86rem;
}

.revisor-filter-input:focus,
.revisor-filter-select:focus {
  border-color: var(--imss-green);
  box-shadow: 0 0 0 1px rgba(0, 99, 65, 0.12);
}

/* Tabla */
.revisor-table thead th {
  background: var(--imss-dark);
  color: #ffffff;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 0;
  padding-top: 0.6rem;
  padding-bottom: 0.6rem;
}

.revisor-table thead th:first-child {
  border-top-left-radius: 8px;
}

.revisor-table thead th:last-child {
  border-top-right-radius: 8px;
}

.revisor-table tbody tr:nth-child(even) {
  background-color: #f9fafb;
}

.revisor-table tbody tr:hover {
  background-color: #eef5f3;
}

/* Botones institucionales */
.btn-imss-outline {
  border-color: var(--imss-green);
  color: var(--imss-green);
  font-size: 0.8rem;
  border-radius: 999px;
  padding-inline: 0.9rem;
  font-weight: 500;
}

.btn-imss-outline:hover {
  background: var(--imss-green);
  border-color: var(--imss-green);
  color: #ffffff;
}

/* Badge */
.revisor-status-badge {
  border-radius: 999px;
  padding-inline: 0.75rem;
  font-size: 0.75rem;
}

/* Footer */
.revisor-footer {
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}

/* Responsive */
@media (max-width: 768px) {
  .revisor-header-text {
    max-width: 100%;
  }

  .revisor-filters {
    width: 100%;
  }

  .revisor-filter-input-group {
    width: 100%;
  }

  .revisor-filter-input,
  .revisor-filter-select {
    width: 100%;
  }

  .revisor-curp-box {
    flex-direction: column;
    align-items: stretch;
  }

  .revisor-curp-input {
    width: 100%;
  }
}
</style>
