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
        <div
          v-if="empleado"
          class="card shadow-sm border-0 mb-3 revisor-card-header-main"
        >
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
                <!-- ✅ Descargar PDF -->
                <a
                  v-if="empleado"
                  class="btn btn-outline-primary w-100"
                  :href="`${BASE_URL}/revisor/empleados/${empleado.id_tbl_empleados}/pdf`"
                  target="_blank"
                  rel="noopener"
                >
                  Descargar PDF
                </a>

                <button
                  type="button"
                  class="btn btn-secondary w-100"
                  @click="cambiarStatus('aprobado')"
                  :disabled="loading"
                >
                  <span v-if="!loading">Aprobar CV</span>
                  <span v-else>Procesando…</span>
                </button>

                <!-- Rechazar abre modal -->
                <button
                  type="button"
                  class="btn btn-imss-danger btn-sm"
                  @click="abrirModalRechazo"
                  :disabled="loading"
                >
                  Rechazar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Bloque principal -->
        <div class="row row-cards" v-if="empleado">
          <div class="col-md-5">
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

          <div class="col-md-7">
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

    <!-- MODAL RECHAZO -->
    <div
      v-if="showRechazoModal"
      class="imss-modal-backdrop"
      @click.self="cerrarModalRechazo"
    >
      <div class="imss-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="imss-modal-header">
          <div class="imss-modal-title">
            <div class="imss-modal-chip">Rechazo de CV</div>
            <h4 id="modal-title" class="mb-0">Motivo de rechazo</h4>
          </div>
          <button
            type="button"
            class="imss-modal-close"
            @click="cerrarModalRechazo"
            aria-label="Cerrar"
          >
            ✕
          </button>
        </div>

        <div class="imss-modal-body">
          <p class="text-muted small mb-2">
            Selecciona una plantilla o escribe el motivo. Este texto se enviará al empleado por correo.
            <span class="ms-1"><strong>(ESC</strong> para cerrar)</span>
          </p>

          <!-- ✅ Plantillas -->
          <label class="form-label">Plantilla (opcional)</label>
          <select
            v-model="plantillaSeleccionada"
            class="form-select"
            @change="aplicarPlantilla"
          >
            <option value="">Selecciona una plantilla…</option>
            <option
              v-for="(tpl, idx) in rechazoTemplates"
              :key="idx"
              :value="tpl.texto"
            >
              {{ tpl.titulo }}
            </option>
          </select>
          <div class="form-text">
            Al elegir una plantilla, se copiará al campo “Motivo” (puedes editarlo).
          </div>

          <div class="mt-3">
            <label class="form-label">Motivo <span class="text-danger">*</span></label>
            <textarea
              v-model.trim="motivoRechazo"
              class="form-control"
              rows="4"
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
          <button
            type="button"
            class="btn btn-outline-secondary"
            @click="cerrarModalRechazo"
            :disabled="loading"
          >
            Cancelar
          </button>

          <button
            type="button"
            class="btn btn-imss-danger-solid"
            @click="confirmarRechazo"
            :disabled="loading"
          >
            <span v-if="!loading">Confirmar rechazo</span>
            <span v-else>Enviando…</span>
          </button>
        </div>
      </div>
    </div>
    <!-- /MODAL -->
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

      // modal rechazo
      showRechazoModal: false,
      motivoRechazo: '',
      motivoError: null,

      // ✅ plantillas
      plantillaSeleccionada: '',
      rechazoTemplates: [
        {
          titulo: 'Falta información en Estudios Académicos',
          texto: 'Falta completar el apartado de Estudios académicos (institución, nivel, carrera y área).',
        },
        {
          titulo: 'Fechas inconsistentes',
          texto: 'Se detectaron fechas inconsistentes. Favor de revisar fechas de inicio y término (experiencia/puesto/cursos).',
        },
        {
          titulo: 'Información incompleta en Experiencia Laboral',
          texto: 'La experiencia laboral está incompleta. Favor de capturar puesto, institución y campo de experiencia.',
        },
        {
          titulo: 'Datos personales por corregir',
          texto: 'Favor de revisar/corregir datos personales (nombre/apellidos/puesto/unidad/coordinación).',
        },
        {
          titulo: 'Cursos sin periodo o institución',
          texto: 'Se requiere completar los cursos/capacitaciones incluyendo período e institución.',
        },
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
        case 'edicion': return 'badge bg-secondary'
        case 'enviado': return 'badge bg-warning'
        case 'aprobado': return 'badge bg-success'
        case 'rechazado': return 'badge bg-danger'
        default: return 'badge bg-secondary'
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
        const { data } = await axios.get(`api/revisor/empleados/${id}`)
        this.empleado = data.empleado
        this.experiencias = data.experiencias || []
        this.estudios = data.estudios || null
        this.cursos = data.cursos || []
        this.statusLocal = this.mapStatusFromInt(this.empleado.estatus_cv)
      } catch (e) {
        this.mensaje = { tipo: 'error', texto: 'No se pudo cargar la información del empleado.' }
      }
    },

    // ✅ Modal rechazo
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
      // Copia el texto de plantilla, pero deja editable el textarea
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
        const id = this.empleado.id_tbl_empleados

        this.loading = true

        const payload = { status: nuevo }
        if (nuevo === 'rechazado') payload.motivo = motivo

        await axios.post(`api/revisor/empleados/${id}/estatus`, payload)

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

    // ✅ ESC para cerrar modal
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
:root {
  --imss-green: #006341;
  --imss-green-dark: #004b2e;
  --imss-green-soft: #e6f2ee;
  --imss-dark: #10312b;
}

.revisor-alert { border-radius: 10px; border-left-width: 4px; }

.revisor-card-header-main {
  border-radius: 14px;
  background: linear-gradient(135deg, #f9fafb 0%, #e6f2ee 100%);
}

.revisor-title { color: var(--imss-dark); font-weight: 600; }
.revisor-main-info { max-width: 420px; }

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

.revisor-actions-header { min-width: 260px; }
.revisor-btn-group .btn { min-width: 110px; }

.btn-imss-danger {
  background: #b91c1c;
  border-color: #b91c1c;
  color: #ffffff;
  font-size: 0.8rem;
  border-radius: 0 999px 999px 0;
  font-weight: 500;
}
.btn-imss-danger:hover { background: #991b1b; border-color: #991b1b; color: #ffffff; }

.btn-imss-danger-solid {
  background: #b91c1c;
  border-color: #b91c1c;
  color: #ffffff;
  font-weight: 600;
}
.btn-imss-danger-solid:hover { background: #991b1b; border-color: #991b1b; color: #ffffff; }

.revisor-status-badge {
  border-radius: 999px;
  padding-inline: 0.9rem;
  font-size: 0.75rem;
}

.revisor-card-section { border-radius: 12px; }
.revisor-card-section-header { background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
.revisor-dl dt { font-size: 0.8rem; }
.revisor-dl dd { font-size: 0.9rem; }
.revisor-exp-item { border-color: #e5e7eb !important; }

/* MODAL */
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
  max-width: 560px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  border: 1px solid rgba(16, 49, 43, 0.12);
}
.imss-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #f9fafb 0%, #e6f2ee 100%);
  border-bottom: 1px solid #e5e7eb;
}
.imss-modal-title h4 { color: var(--imss-dark); font-weight: 700; }
.imss-modal-chip {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #7f1d1d;
  background: #fee2e2;
  margin-bottom: 6px;
}
.imss-modal-close {
  border: 0;
  background: transparent;
  font-size: 18px;
  line-height: 1;
  color: #334155;
  padding: 6px 8px;
  border-radius: 8px;
}
.imss-modal-close:hover { background: rgba(0, 0, 0, 0.06); }

.imss-modal-body { padding: 16px; }
.imss-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 16px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

@media (max-width: 768px) {
  .revisor-main-info { max-width: 100%; }
  .revisor-actions-header { text-align: left !important; }
  .revisor-btn-group { width: 100%; }
  .revisor-btn-group .btn { flex: 1; border-radius: 999px !important; }
}
</style>
