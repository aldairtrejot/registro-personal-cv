<template>
  <div class="container-xl py-4 imss-theme">
    <div class="row justify-content-center">
      <div class="col-12">
        <!-- Mensajes -->
        <div
          v-if="mensaje"
          :class="[
            'alert',
            mensaje.tipo === 'ok' ? 'alert-success' : 'alert-danger',
            'mb-3',
            'imss-alert',
          ]"
        >
          {{ mensaje.texto }}
        </div>

        <!-- ENCABEZADO -->
        <div v-if="empleado" class="card imss-card mb-3">
          <div class="card-body imss-header-body">
            <div class="row g-3 align-items-center">
              <div class="col-12 col-lg-8">
                <div class="imss-kicker">Expediente</div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                  <h3 class="imss-title mb-0">{{ nombreCompleto }}</h3>
                  <span class="imss-badge" :class="badgeClass(statusLocal)">
                    {{ statusLabel(statusLocal) }}
                  </span>
                </div>

                <div class="imss-header-grid">
                  <div class="imss-header-item">
                    <div class="imss-label">CURP</div>
                    <code class="imss-code">{{ empleado.curp || 'N/D' }}</code>
                  </div>
                  <div class="imss-header-item">
                    <div class="imss-label">Área</div>
                    <div class="imss-value">
                      {{ empleado.area_adscripcion || empleado.area || 'Sin área' }}
                    </div>
                  </div>
                  <div class="imss-header-item">
                    <div class="imss-label">Puesto actual</div>
                    <div class="imss-value">{{ empleado.puesto_actual || 'N/D' }}</div>
                  </div>
                </div>
              </div>

              <!-- Acciones -->
              <div class="col-12 col-lg-4">
                <div class="imss-actions-panel">
                  <a :href="pdfEmpleadoUrl" class="btn btn-outline-success btn-sm w-100 imss-btn-fixed">
                    Descargar PDF
                  </a>

                  <div class="d-grid gap-2 mt-2">
                    <button
                      type="button"
                      class="btn btn-imss btn-sm imss-btn-fixed"
                      @click="cambiarStatus('aprobado')"
                      :disabled="loading"
                    >
                      <span v-if="!loading">Aprobar CV</span>
                      <span v-else>Procesando…</span>
                    </button>

                    <button
                      type="button"
                      class="btn btn-imss-danger btn-sm imss-btn-fixed"
                      @click="abrirModalRechazo"
                      :disabled="loading"
                    >
                      Rechazar CV
                    </button>
                  </div>

                  <div class="imss-help mt-2">
                    Al rechazar, se envía el motivo al empleado.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CONTENIDO -->
        <div class="row g-3" v-if="empleado">
          <!-- Col izquierda -->
          <div class="col-12 col-lg-4">
            <div class="card imss-card mb-3">
              <div class="card-header imss-section-header">
                <h3 class="imss-section-title mb-0">Datos personales</h3>
              </div>
              <div class="card-body">
                <dl class="row mb-0 imss-dl">
                  <dt class="col-5 text-muted">Nombre(s)</dt>
                  <dd class="col-7">{{ empleado.nombre || 'N/D' }}</dd>

                  <dt class="col-5 text-muted">Primer apellido</dt>
                  <dd class="col-7">{{ empleado.primer_apellido || 'N/D' }}</dd>

                  <dt class="col-5 text-muted">Segundo apellido</dt>
                  <dd class="col-7">{{ empleado.segundo_apellido || 'N/D' }}</dd>

                  <dt class="col-5 text-muted">Fecha inicio</dt>
                  <dd class="col-7">{{ empleado.fecha_inicio_puesto || 'N/D' }}</dd>
                </dl>
              </div>
            </div>

            <div class="card imss-card">
              <div class="card-header imss-section-header">
                <h3 class="imss-section-title mb-0">Resumen</h3>
              </div>
              <div class="card-body">
                <ul class="list-unstyled mb-0">
                  <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Experiencias</span>
                    <span class="fw-semibold">{{ experiencias.length }}</span>
                  </li>
                  <li class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Cursos</span>
                    <span class="fw-semibold">{{ cursos.length }}</span>
                  </li>
                  <li class="d-flex justify-content-between py-2">
                    <span class="text-muted">Nivel máximo</span>
                    <span class="fw-semibold">{{ estudios ? (estudios.nivel || 'N/D') : 'N/D' }}</span>
                  </li>
                </ul>
                <div class="text-muted small mt-2">
                  * Basado en el registro capturado.
                </div>
              </div>
            </div>
          </div>

          <!-- Col derecha -->
          <div class="col-12 col-lg-8">
            <div class="card imss-card mb-3">
              <div class="card-header imss-section-header">
                <h3 class="imss-section-title mb-0">Experiencia laboral</h3>
              </div>
              <div class="card-body">
                <div v-if="experiencias.length === 0" class="text-muted">
                  Sin registros.
                </div>

                <div
                  v-for="(exp, i) in experiencias"
                  :key="exp.id_tbl_cv_experiencia_laboral || i"
                  class="imss-item"
                  :class="{ 'imss-item-last': i === experiencias.length - 1 }"
                >
                  <div class="d-flex flex-column flex-md-row justify-content-between gap-1">
                    <div class="fw-semibold">
                      {{ exp.puesto || 'Sin puesto' }}
                      <span v-if="exp.institucion" class="text-muted fw-normal"> · {{ exp.institucion }}</span>
                    </div>
                    <div class="text-muted small">
                      {{ exp.fecha_inicio || 'N/D' }} – {{ exp.fecha_termino || 'Actual' }}
                    </div>
                  </div>

                  <div class="text-muted small mt-1">
                    Sector:
                    <span v-if="exp.sector === 'PUBLICO'">PÚBLICO</span>
                    <span v-else-if="exp.sector === 'PRIVADO'">PRIVADO</span>
                    <span v-else>Sin especificar</span>
                  </div>

                  <div class="small mt-1">
                    <span class="text-muted">Campo: </span>
                    {{ exp.campo_experiencia || 'N/D' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="card imss-card mb-3">
              <div class="card-header imss-section-header">
                <h3 class="imss-section-title mb-0">Estudios académicos</h3>
              </div>
              <div class="card-body">
                <div v-if="!estudios" class="text-muted">
                  Sin registros.
                </div>

                <dl v-else class="row mb-0 imss-dl">
                  <dt class="col-sm-4 text-muted">Institución</dt>
                  <dd class="col-sm-8">{{ estudios.institucion || 'N/D' }}</dd>

                  <dt class="col-sm-4 text-muted">País</dt>
                  <dd class="col-sm-8">{{ estudios.pais || 'N/D' }}</dd>

                  <dt class="col-sm-4 text-muted">Nivel</dt>
                  <dd class="col-sm-8">{{ estudios.nivel || 'N/D' }}</dd>

                  <dt class="col-sm-4 text-muted">Cédula</dt>
                  <dd class="col-sm-8">{{ estudios.numero_cedula || 'N/D' }}</dd>

                  <dt class="col-sm-4 text-muted">Carrera genérica</dt>
                  <dd class="col-sm-8">{{ estudios.carrera_generica || 'N/D' }}</dd>

                  <dt class="col-sm-4 text-muted">Carrera específica</dt>
                  <dd class="col-sm-8">{{ estudios.carrera_especifica || 'N/D' }}</dd>

                  <dt class="col-sm-4 text-muted">Área de estudios</dt>
                  <dd class="col-sm-8">{{ estudios.area_estudios || 'N/D' }}</dd>
                </dl>
              </div>
            </div>

            <div class="card imss-card">
              <div class="card-header imss-section-header">
                <h3 class="imss-section-title mb-0">Cursos y capacitaciones</h3>
              </div>
              <div class="card-body">
                <div v-if="cursos.length === 0" class="text-muted">
                  Sin registros.
                </div>

                <div
                  v-for="(curso, i) in cursos"
                  :key="curso.id_tbl_cv_cursos_capacitaciones || i"
                  class="imss-item"
                  :class="{ 'imss-item-last': i === cursos.length - 1 }"
                >
                  <div class="d-flex flex-column flex-md-row justify-content-between gap-1">
                    <div class="fw-semibold">
                      {{ curso.nombre_curso || 'Sin nombre' }}
                    </div>
                    <div class="text-muted small">
                      {{ curso.periodo || 'Sin periodo' }}
                    </div>
                  </div>
                  <div class="text-muted small mt-1">
                    {{ curso.institucion || 'Sin institución' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-3">
              <a :href="`${BASE_URL}/revisor/empleados`" class="btn btn-secondary w-100 imss-btn-fixed">
                ← Volver al listado
              </a>
            </div>
          </div>
        </div>

        <!-- MODAL RECHAZO -->
        <div
          v-if="showRechazoModal"
          class="imss-modal-backdrop"
          @click.self="cerrarModalRechazo"
        >
          <div class="imss-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="imss-modal-header">
              <div>
                <div class="imss-kicker">Rechazo</div>
                <h4 id="modal-title" class="mb-0 imss-modal-title">Motivo de rechazo</h4>
                <div class="text-muted small mt-1">
                  Selecciona una plantilla o escribe el motivo (se enviará al empleado).
                </div>
              </div>
              <button type="button" class="imss-modal-close" @click="cerrarModalRechazo" aria-label="Cerrar">
                ✕
              </button>
            </div>

            <div class="imss-modal-body">
              <label class="imss-label">Plantilla (opcional)</label>
              <select v-model="plantillaSeleccionada" class="form-select imss-select" @change="aplicarPlantilla">
                <option value="">Selecciona una plantilla…</option>
                <option v-for="(tpl, idx) in rechazoTemplates" :key="idx" :value="tpl.texto">
                  {{ tpl.titulo }}
                </option>
              </select>

              <div class="mt-3">
                <label class="imss-label">Motivo <span class="text-danger">*</span></label>
                <textarea
                  v-model.trim="motivoRechazo"
                  class="form-control imss-input"
                  rows="5"
                  maxlength="500"
                  placeholder="Ej. Falta completar estudios académicos, corregir fechas, etc."
                ></textarea>

                <div class="d-flex justify-content-between mt-2">
                  <div v-if="motivoError" class="text-danger small">
                    {{ motivoError }}
                  </div>
                  <div class="text-muted small ms-auto">
                    {{ (motivoRechazo || '').length }}/500
                  </div>
                </div>
              </div>
            </div>

            <div class="imss-modal-footer">
              <button type="button" class="btn btn-outline-secondary imss-btn-fixed" @click="cerrarModalRechazo" :disabled="loading">
                Cancelar
              </button>

              <button type="button" class="btn btn-imss-danger-solid imss-btn-fixed" @click="confirmarRechazo" :disabled="loading">
                <span v-if="!loading">Confirmar rechazo</span>
                <span v-else>Enviando…</span>
              </button>
            </div>
          </div>
        </div>
        <!-- /MODAL -->
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

      showRechazoModal: false,
      motivoRechazo: '',
      motivoError: null,

      plantillaSeleccionada: '',
      rechazoTemplates: [
        { titulo: 'Falta información en Estudios Académicos', texto: 'Falta completar el apartado de Estudios académicos (institución, nivel, carrera y área).' },
        { titulo: 'Fechas inconsistentes', texto: 'Se detectaron fechas inconsistentes. Favor de revisar fechas de inicio y término (experiencia/puesto/cursos).' },
        { titulo: 'Información incompleta en Experiencia Laboral', texto: 'La experiencia laboral está incompleta. Favor de capturar puesto, institución y campo de experiencia.' },
        { titulo: 'Datos personales por corregir', texto: 'Favor de revisar/corregir datos personales (nombre/apellidos/puesto/unidad/coordinación).' },
        { titulo: 'Cursos sin periodo o institución', texto: 'Se requiere completar los cursos/capacitaciones incluyendo período e institución.' },
      ],

      loading: false,
    }
  },
  computed: {
    nombreCompleto() {
      if (!this.empleado) return ''
      const { nombre, primer_apellido, segundo_apellido } = this.empleado
      return `${nombre || ''} ${primer_apellido || ''} ${segundo_apellido || ''}`.trim()
    },
    pdfEmpleadoUrl() {
      if (!this.empleado) return '#'
      const id = this.empleado.id_tbl_empleados ?? this.empleado.id
      return `${this.BASE_URL}/revisor/empleados/${id}/pdf`
    },
  },
  methods: {
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
    mapStatusFromInt(estatus_cv) {
      switch (Number(estatus_cv)) {
        case 1: return 'edicion'
        case 2: return 'enviado'
        case 3: return 'aprobado'
        case 4: return 'rechazado'
        default: return 'sin_cv'
      }
    },

    async cargarDetalle(id) {
      try {
        // ✅ CAMBIO: agregar "/" para no volverse ruta relativa
        const { data } = await axios.get(`/api/revisor/empleados/${id}`)
        this.empleado = data.empleado
        this.experiencias = data.experiencias || []
        this.estudios = data.estudios || null
        this.cursos = data.cursos || []
        this.statusLocal = this.mapStatusFromInt(this.empleado.estatus_cv)
      } catch (e) {
        this.mensaje = { tipo: 'error', texto: 'No se pudo cargar la información del empleado.' }
      }
    },

    abrirModalRechazo() {
      this.plantillaSeleccionada = ''
      this.motivoRechazo = ''
      this.motivoError = null
      this.showRechazoModal = true
      this.$nextTick(() => {
        const el = document.querySelector('.imss-modal textarea')
        if (el) el.focus()
      })
    },
    cerrarModalRechazo() {
      if (this.loading) return
      this.showRechazoModal = false
      this.motivoError = null
    },
    aplicarPlantilla() {
      if (this.plantillaSeleccionada) {
        this.motivoRechazo = this.plantillaSeleccionada
        this.motivoError = null
        this.$nextTick(() => {
          const el = document.querySelector('.imss-modal textarea')
          if (el) el.focus()
        })
      }
    },
    async confirmarRechazo() {
      const motivo = (this.motivoRechazo || '').trim()
      if (!motivo) {
        this.motivoError = 'El motivo es obligatorio.'
        return
      }
      if (motivo.length > 500) {
        this.motivoError = 'El motivo excede el máximo de 500 caracteres.'
        return
      }

      this.motivoError = null
      await this.cambiarStatus('rechazado', motivo)
    },

    async cambiarStatus(nuevo, motivo = null) {
      try {
        if (!this.empleado) return
        const id = this.empleado.id_tbl_empleados ?? this.empleado.id

        this.loading = true

        const payload = { status: nuevo }
        if (nuevo === 'rechazado') payload.motivo = motivo

        // ✅ CAMBIO: agregar "/" para no volverse ruta relativa
        await axios.post(`/api/revisor/empleados/${id}/estatus`, payload)

        this.statusLocal = nuevo
        this.mensaje = {
          tipo: 'ok',
          texto:
            nuevo === 'aprobado'
              ? 'CV marcado como Aprobado.'
              : 'CV marcado como Rechazado. Se envió el motivo al empleado.',
        }

        if (nuevo === 'rechazado') this.showRechazoModal = false

        setTimeout(() => { this.mensaje = null }, 4000)
      } catch (e) {
        const msg = e?.response?.data?.message || 'No se pudo actualizar el estatus del CV.'
        this.mensaje = { tipo: 'error', texto: msg }
        if (nuevo === 'rechazado') this.motivoError = msg
      } finally {
        this.loading = false
      }
    },

    handleKeydown(e) {
      if (e.key === 'Escape' && this.showRechazoModal) {
        this.cerrarModalRechazo()
      }
    },
  },
  mounted() {
    window.addEventListener('keydown', this.handleKeydown)

    const el = document.getElementById('blade_revisor_empleado_show')
    const id = el?.dataset?.empleadoId
    if (id) this.cargarDetalle(id)
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.handleKeydown)
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

.imss-alert {
  border-radius: 12px;
  border-left-width: 4px;
}

.imss-header-body {
  background: #ffffff;
  padding: 18px;
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
  font-weight: 800;
  font-size: 1.25rem;
}

.imss-header-grid {
  margin-top: 10px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.imss-header-item {
  padding: 10px 12px;
  border: 1px solid var(--imss-border);
  background: var(--imss-bg);
  border-radius: 12px;
}

.imss-label {
  font-size: 0.78rem;
  font-weight: 800;
  color: var(--imss-ink);
  margin-bottom: 6px;
}

.imss-value {
  font-size: 0.92rem;
  color: var(--imss-ink);
  font-weight: 600;
}

.imss-code {
  background: #0b1120;
  color: #e5e7eb;
  border-radius: 10px;
  padding: 4px 10px;
  font-size: 0.78rem;
}

.imss-actions-panel {
  border: 1px solid var(--imss-border);
  border-radius: 14px;
  padding: 14px;
  background: var(--imss-bg);
}

.imss-help {
  font-size: 0.8rem;
  color: var(--imss-muted);
}

/* Secciones */
.imss-section-header {
  background: #ffffff;
  border-bottom: 1px solid var(--imss-border);
  padding: 14px 16px;
}

.imss-section-title {
  color: var(--imss-ink);
  font-weight: 800;
  font-size: 1rem;
}

.imss-dl dt {
  font-size: 0.82rem;
}
.imss-dl dd {
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--imss-ink);
}

/* Items */
.imss-item {
  padding: 12px 0;
  border-bottom: 1px solid var(--imss-border);
}
.imss-item-last {
  border-bottom: 0;
  padding-bottom: 0;
}

/* Badges */
.imss-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.78rem;
  font-weight: 800;
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
  font-weight: 800;
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

.btn-imss-danger {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
  color: #ffffff !important;
  border-radius: 12px;
  font-weight: 800;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.btn-imss-danger:hover,
.btn-imss-danger:focus,
.btn-imss-danger:active {
  background: #991b1b !important;
  border-color: #991b1b !important;
  color: #ffffff !important;
  opacity: 1 !important;
}

.btn-imss-danger:focus {
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.14) !important;
}

.btn-imss-danger-solid {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
  color: #ffffff !important;
  border-radius: 12px;
  font-weight: 900;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.btn-imss-danger-solid:hover,
.btn-imss-danger-solid:focus,
.btn-imss-danger-solid:active {
  background: #991b1b !important;
  border-color: #991b1b !important;
  color: #ffffff !important;
  opacity: 1 !important;
}

.btn-imss-danger-solid:focus {
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.14) !important;
}

/* Modal */
.imss-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 9999;
}

.imss-modal {
  width: 100%;
  max-width: 620px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 22px 50px rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(16, 49, 43, 0.12);
  overflow: hidden;
}

.imss-modal-header {
  padding: 14px 16px;
  border-bottom: 1px solid var(--imss-border);
  display: flex;
  justify-content: space-between;
  gap: 12px;
  background: #ffffff;
}

.imss-modal-title {
  font-weight: 900;
  color: var(--imss-ink);
}

.imss-modal-close {
  border: 0;
  background: var(--imss-bg);
  border: 1px solid var(--imss-border);
  width: 36px;
  height: 36px;
  border-radius: 10px;
  font-size: 16px;
  line-height: 1;
  color: #334155;
}
.imss-modal-close:hover {
  background: #ffffff;
}

.imss-modal-body {
  padding: 16px;
  background: #ffffff;
}

.imss-modal-footer {
  padding: 14px 16px;
  border-top: 1px solid var(--imss-border);
  background: var(--imss-bg);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.imss-input,
.imss-select {
  border-radius: 12px;
  border-color: var(--imss-border);
}
.imss-input:focus,
.imss-select:focus {
  border-color: rgba(0, 99, 65, 0.55);
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.12);
}

@media (max-width: 992px) {
  .imss-header-grid {
    grid-template-columns: 1fr;
  }
}
</style>
