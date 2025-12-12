<template>
  <div class="container-xl py-4">
    <div class="row row-cards">
      <div class="col-12">
        <!-- Mensaje -->
        <div
          v-if="mensaje"
          :class="[
            'alert',
            mensaje.tipo === 'ok' ? 'alert-success' : 'alert-danger',
            'mb-3',
            'revisor-alert',
          ]"
        >
          {{ mensaje.texto }}
        </div>

        <!-- Encabezado -->
        <div v-if="empleado" class="card shadow-sm border-0 mb-3 revisor-card-header-main">
          <div
            class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
          >
            <div class="revisor-main-info">
              <div class="text-uppercase text-muted small mb-1 revisor-pill-title">
                Expediente de CV
              </div>
              <h3 class="card-title mb-1 revisor-title">
                {{ nombreCompleto }}
              </h3>
              <div class="text-muted small mb-1">
                CURP:
                <code class="revisor-curp-code">{{ empleado.curp }}</code>
              </div>
              <div class="text-muted small">
                Área:
                <span class="fw-semibold">
                  {{ empleado.area_adscripcion || 'Sin área' }}
                </span>
                · Puesto:
                <span class="fw-semibold">
                  {{ empleado.puesto_actual || 'N/D' }}
                </span>
              </div>
              <div class="text-muted small mt-1">
                Última actualización:
                <span class="fw-semibold">
                  {{ empleado.fecha_inicio_puesto || 'N/D' }}
                </span>
              </div>
            </div>

            <div class="text-md-end revisor-actions-header">
              <div class="mb-2">
                <span :class="badgeClass(statusLocal)" class="revisor-status-badge">
                  {{ statusLabel(statusLocal) }}
                </span>
              </div>
              <div class="btn-group revisor-btn-group">
                <button
                  type="button"
                  class="btn btn-secondary w-100"
                  @click="cambiarStatus('aprobado')"
                >
                  Aprobar CV
                </button>
                <button
                  type="button"
                  class="btn btn-imss-danger btn-sm"
                  @click="cambiarStatus('rechazado')"
                >
                  Rechazar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Bloque principal -->
        <div class="row row-cards" v-if="empleado">
          <!-- Columna izquierda -->
          <div class="col-md-5">
            <!-- Datos personales -->
            <div class="card mb-3 shadow-sm border-0 revisor-card-section">
              <div class="card-header revisor-card-section-header">
                <h3 class="card-title">Datos personales</h3>
              </div>
              <div class="card-body">
                <dl class="row mb-0 revisor-dl">
                  <dt class="col-5 text-muted">Nombre(s)</dt>
                  <dd class="col-7">{{ empleado.nombre }}</dd>

                  <dt class="col-5 text-muted">Primer apellido</dt>
                  <dd class="col-7">{{ empleado.primer_apellido }}</dd>

                  <dt class="col-5 text-muted">Segundo apellido</dt>
                  <dd class="col-7">{{ empleado.segundo_apellido || 'N/D' }}</dd>

                  <dt class="col-5 text-muted">Puesto actual</dt>
                  <dd class="col-7">{{ empleado.puesto_actual || 'N/D' }}</dd>

                  <dt class="col-5 text-muted">Fecha de inicio</dt>
                  <dd class="col-7">{{ empleado.fecha_inicio_puesto || 'N/D' }}</dd>
                </dl>
              </div>
            </div>

            <!-- Resumen -->
            <div class="card shadow-sm border-0 revisor-card-section">
              <div class="card-header revisor-card-section-header">
                <h3 class="card-title">Resumen de CV</h3>
              </div>
              <div class="card-body">
                <ul class="list-unstyled mb-0">
                  <li class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Experiencias laborales</span>
                    <span class="fw-semibold">{{ experiencias.length }}</span>
                  </li>
                  <li class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Cursos / capacitaciones</span>
                    <span class="fw-semibold">{{ cursos.length }}</span>
                  </li>
                  <li class="mb-0 d-flex justify-content-between">
                    <span class="text-muted">Nivel máximo de estudios</span>
                    <span class="fw-semibold">
                      {{ estudios ? estudios.nivel : 'N/D' }}
                    </span>
                  </li>
                </ul>
                <div class="small text-muted mt-2">
                  * Información proveniente del registro de CV.
                </div>
              </div>
            </div>
          </div>

          <!-- Columna derecha -->
          <div class="col-md-7">
            <!-- Experiencia laboral -->
            <div class="card shadow-sm border-0 mb-3 revisor-card-section">
              <div class="card-header revisor-card-section-header">
                <h3 class="card-title mb-0">Experiencia laboral</h3>
              </div>
              <div class="card-body">
                <div v-if="experiencias.length === 0" class="text-muted">
                  Sin registros.
                </div>
                <div
                  v-for="(exp, i) in experiencias"
                  :key="exp.id_tbl_cv_experiencia_laboral || i"
                  class="mb-3 pb-3 border-bottom revisor-exp-item"
                  :class="{ 'border-0': i === experiencias.length - 1 }"
                >
                  <div class="d-flex justify-content-between">
                    <div class="fw-semibold">
                      {{ exp.puesto || 'Sin puesto' }}
                      <span v-if="exp.institucion"> · {{ exp.institucion }}</span>
                    </div>
                    <div class="text-muted small">
                      {{ exp.fecha_inicio || 'N/D' }} –
                      {{ exp.fecha_termino || 'Actual' }}
                    </div>
                  </div>
                  <div class="text-muted small mb-1">
                    Sector:
                    <span v-if="exp.sector === 'publico'">Público</span>
                    <span v-else-if="exp.sector === 'privado'">Privado</span>
                    <span v-else>Sin especificar</span>
                  </div>
                  <div class="small">
                    <span class="text-muted">Campo de experiencia: </span>
                    {{ exp.campo_experiencia || 'N/D' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Estudios académicos -->
            <div class="card shadow-sm border-0 mb-3 revisor-card-section">
              <div class="card-header revisor-card-section-header">
                <h3 class="card-title mb-0">Estudios académicos</h3>
              </div>
              <div class="card-body">
                <div v-if="!estudios" class="text-muted">
                  Sin registros.
                </div>
                <dl v-else class="row mb-0 revisor-dl">
                  <dt class="col-sm-4 text-muted">Institución</dt>
                  <dd class="col-sm-8">{{ estudios.institucion }}</dd>

                  <dt class="col-sm-4 text-muted">País</dt>
                  <dd class="col-sm-8">{{ estudios.pais }}</dd>

                  <dt class="col-sm-4 text-muted">Nivel máximo de estudios</dt>
                  <dd class="col-sm-8">{{ estudios.nivel }}</dd>

                  <dt class="col-sm-4 text-muted">Número de cédula</dt>
                  <dd class="col-sm-8">{{ estudios.numero_cedula }}</dd>

                  <dt class="col-sm-4 text-muted">Carrera genérica</dt>
                  <dd class="col-sm-8">{{ estudios.carrera_generica }}</dd>

                  <dt class="col-sm-4 text-muted">Carrera específica</dt>
                  <dd class="col-sm-8">{{ estudios.carrera_especifica }}</dd>

                  <dt class="col-sm-4 text-muted">Área de estudios</dt>
                  <dd class="col-sm-8">{{ estudios.area_estudios }}</dd>
                </dl>
              </div>
            </div>

            <!-- Cursos -->
            <div class="card shadow-sm border-0 revisor-card-section">
              <div class="card-header revisor-card-section-header">
                <h3 class="card-title mb-0">Cursos y capacitaciones</h3>
              </div>
              <div class="card-body">
                <div v-if="cursos.length === 0" class="text-muted">
                  Sin registros.
                </div>
                <div
                  v-for="(curso, i) in cursos"
                  :key="curso.id_tbl_cv_cursos_capacitaciones || i"
                  class="mb-3 pb-3 border-bottom"
                  :class="{ 'border-0': i === cursos.length - 1 }"
                >
                  <div class="d-flex justify-content-between">
                    <div class="fw-semibold">
                      {{ curso.nombre_curso || 'Sin nombre' }}
                    </div>
                    <div class="text-muted small">
                      {{ curso.periodo || 'Sin periodo' }}
                    </div>
                  </div>
                  <div class="text-muted small">
                    {{ curso.institucion || 'Sin institución' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón volver -->
        <div class="mt-3">
          <a
            :href="`${BASE_URL}/revisor/empleados`"
            class="btn btn-secondary w-100"
          >
            ← Volver al listado
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@axios'
import { BASE_URL } from '@/components/url.js'

export default {
  name: 'RevisorEmpleadoShow',
  data() {
    return {
      BASE_URL,
      mensaje: null,
      empleado: null,
      statusLocal: 'edicion',
      experiencias: [],
      estudios: null,
      cursos: [],
    }
  },
  computed: {
    nombreCompleto() {
      if (!this.empleado) return ''
      const { nombre, primer_apellido, segundo_apellido } = this.empleado
      return `${nombre || ''} ${primer_apellido || ''} ${segundo_apellido || ''}`.trim()
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
    mapStatusFromInt(estatus_cv) {
      switch (Number(estatus_cv)) {
        case 1:
          return 'edicion'
        case 2:
          return 'enviado'
        case 3:
          return 'aprobado'
        case 4:
          return 'rechazado'
        default:
          return 'sin_cv'
      }
    },
    async cargarDetalle(id) {
      try {
        const { data } = await axios.get(`api/revisor/empleados/${id}`)
        this.empleado = data.empleado
        this.experiencias = data.experiencias || []
        this.estudios = data.estudios || null
        this.cursos = data.cursos || []
        this.statusLocal = this.mapStatusFromInt(this.empleado.estatus_cv)
      } catch (e) {
        this.mensaje = {
          tipo: 'error',
          texto: 'No se pudo cargar la información del empleado.',
        }
      }
    },
    async cambiarStatus(nuevo) {
      try {
        const id = this.empleado.id_tbl_empleados
        await axios.post(`api/revisor/empleados/${id}/estatus`, {
          status: nuevo,
        })
        this.statusLocal = nuevo
        this.mensaje = {
          tipo: 'ok',
          texto:
            nuevo === 'aprobado'
              ? 'CV marcado como Aprobado.'
              : 'CV marcado como Rechazado.',
        }
        setTimeout(() => {
          this.mensaje = null
        }, 4000)
      } catch (e) {
        this.mensaje = {
          tipo: 'error',
          texto: 'No se pudo actualizar el estatus del CV.',
        }
      }
    },
  },
  mounted() {
    const el = document.getElementById('blade_revisor_empleado_show')
    const id = el?.dataset?.empleadoId
    if (id) {
      this.cargarDetalle(id)
    }
  },
}
</script>

<style scoped>
:root {
  --imss-green: #006341;
  --imss-green-dark: #004b2e;
  --imss-green-soft: #e6f2ee;
  --imss-dark: #10312b;
}

/* Mensajes */
.revisor-alert {
  border-radius: 10px;
  border-left-width: 4px;
}

/* Encabezado principal */
.revisor-card-header-main {
  border-radius: 14px;
  background: linear-gradient(135deg, #f9fafb 0%, #e6f2ee 100%);
}

.revisor-title {
  color: var(--imss-dark);
  font-weight: 600;
}

.revisor-main-info {
  max-width: 420px;
}

.revisor-pill-title {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4b5563;
}

.revisor-curp-code {
  background: #0b1120;
  color: #e5e7eb;
  padding: 2px 6px;
  border-radius: 4px;
}

/* Acciones de cabecera */
.revisor-actions-header {
  min-width: 220px;
}

.revisor-btn-group .btn {
  min-width: 110px;
}

/* Botones institucionales */
.btn-imss-success {
  background: var(--imss-green);
  border-color: var(--imss-green);
  color: #ffffff;
  font-size: 0.8rem;
  border-radius: 999px 0 0 999px;
  font-weight: 500;
}

.btn-imss-success:hover {
  background: var(--imss-green-dark);
  border-color: var(--imss-green-dark);
  color: #ffffff;
}

.btn-imss-danger {
  background: #b91c1c;
  border-color: #b91c1c;
  color: #ffffff;
  font-size: 0.8rem;
  border-radius: 0 999px 999px 0;
  font-weight: 500;
}

.btn-imss-danger:hover {
  background: #991b1b;
  border-color: #991b1b;
  color: #ffffff;
}

.btn-imss-outline {
  border-color: var(--imss-green);
  color: var(--imss-green);
  font-size: 0.8rem;
  border-radius: 999px;
  padding-inline: 1rem;
  font-weight: 500;
}

.btn-imss-outline:hover {
  background: var(--imss-green);
  border-color: var(--imss-green);
  color: #ffffff;
}

.revisor-btn-back {
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
}

/* Badge de estatus */
.revisor-status-badge {
  border-radius: 999px;
  padding-inline: 0.9rem;
  font-size: 0.75rem;
}

/* Secciones */
.revisor-card-section {
  border-radius: 12px;
}

.revisor-card-section-header {
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

/* Listas tipo detalle */
.revisor-dl dt {
  font-size: 0.8rem;
}

.revisor-dl dd {
  font-size: 0.9rem;
}

/* Experiencia */
.revisor-exp-item {
  border-color: #e5e7eb !important;
}

/* Responsive */
@media (max-width: 768px) {
  .revisor-main-info {
    max-width: 100%;
  }

  .revisor-actions-header {
    text-align: left !important;
  }

  .revisor-btn-group {
    width: 100%;
  }

  .revisor-btn-group .btn {
    flex: 1;
    border-radius: 999px !important;
  }

  .btn-imss-success {
    margin-bottom: 4px;
  }
}
</style>
