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
            'mb-3'
          ]"
        >
          {{ mensaje.texto }}
        </div>

        <!-- Encabezado -->
        <div v-if="empleado" class="card shadow-sm border-0 mb-3">
          <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="mb-3 mb-md-0">
              <div class="text-uppercase text-muted small mb-1">
                Expediente de CV
              </div>
              <h3 class="card-title mb-1">
                {{ nombreCompleto }}
              </h3>
              <div class="text-muted small mb-1">
                CURP: <code>{{ empleado.curp }}</code>
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
                {{ empleado.fecha_inicio_puesto || 'N/D' }}
              </div>
            </div>

            <div class="text-md-end">
              <div class="mb-2">
                <span :class="badgeClass(statusLocal)">
                  {{ statusLabel(statusLocal) }}
                </span>
              </div>
              <div class="btn-group">
                <button
                  type="button"
                  class="btn btn-success btn-sm"
                  @click="cambiarStatus('aprobado')"
                >
                  Aprobar CV
                </button>
                <button
                  type="button"
                  class="btn btn-danger btn-sm"
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
          <!-- Datos personales / resumen -->
          <div class="col-md-5">
            <!-- Datos personales -->
            <div class="card mb-3 shadow-sm border-0">
              <div class="card-header">
                <h3 class="card-title">Datos personales</h3>
              </div>
              <div class="card-body">
                <dl class="row mb-0">
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
            <div class="card shadow-sm border-0">
              <div class="card-header">
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

          <!-- Detalle de CV -->
          <div class="col-md-7">
            <!-- Experiencia laboral -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-header">
                <h3 class="card-title mb-0">Experiencia laboral</h3>
              </div>
              <div class="card-body">
                <div v-if="experiencias.length === 0" class="text-muted">
                  Sin registros.
                </div>
                <div
                  v-for="(exp, i) in experiencias"
                  :key="exp.id_tbl_cv_experiencia_laboral || i"
                  class="mb-3 pb-3 border-bottom"
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
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-header">
                <h3 class="card-title mb-0">Estudios académicos</h3>
              </div>
              <div class="card-body">
                <div v-if="!estudios" class="text-muted">
                  Sin registros.
                </div>
                <dl v-else class="row mb-0">
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

            <!-- Cursos y capacitaciones -->
            <div class="card shadow-sm border-0">
              <div class="card-header">
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
          <a href="/registro-personal-cv/public/revisor/empleados" class="btn btn-outline-secondary">
            ← Volver al listado
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../../components/axios'

export default {
  name: 'RevisorEmpleadoShow',
  data() {
    return {
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
