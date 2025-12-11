<template>
  <div class="container-xl py-4">
    <div class="row row-cards">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <!-- Header -->
          <div class="card-header d-flex flex-column flex-md-row align-items-md-center">
            <div class="mb-3 mb-md-0">
              <h3 class="card-title mb-1">Revisión de CV</h3>
              <div class="text-muted small">
                Consulta y revisa los CV capturados por el personal.
              </div>
            </div>

            <div class="ms-md-auto w-100 w-md-auto d-flex flex-column flex-md-row gap-2">
              <input
                v-model="filtros.busqueda"
                type="text"
                class="form-control form-control-sm"
                placeholder="Buscar por nombre, CURP o área"
              />
              <select
                v-model="filtros.status"
                class="form-select form-select-sm"
                style="max-width: 200px;"
              >
                <option value="">Todos los estatus</option>
                <option value="edicion">En edición</option>
                <option value="enviado">Enviado</option>
                <option value="aprobado">Aprobado</option>
                <option value="rechazado">Rechazado</option>
              </select>
            </div>
          </div>

          <!-- Tabla -->
          <div class="table-responsive">
            <table class="table table-vcenter card-table mb-0">
              <thead>
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>Nombre</th>
                  <th>CURP</th>
                  <th>Área</th>
                  <th>Última actualización</th>
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
                >
                  <td>{{ index + 1 }}</td>
                  <td class="fw-semibold">{{ emp.nombre }}</td>
                  <td><code>{{ emp.curp }}</code></td>
                  <td>{{ emp.area || 'N/D' }}</td>
                  <td>{{ emp.fechaActualizacion || 'N/D' }}</td>
                  <td>
                    <span :class="badgeClass(emp.status)">
                      {{ statusLabel(emp.status) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <a
                      :href="detalleUrl(emp.id)"
                      class="btn btn-outline-primary btn-sm"
                    >
                      Ver detalle
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer -->
          <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center">
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
    async cargarEmpleados() {
      try {
        const { data } = await axios.get('api/revisor/empleados', {
          params: {
            q: this.filtros.busqueda || undefined,
            status: this.filtros.status || undefined,
          },
        })
        // Espera un array de empleados
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
