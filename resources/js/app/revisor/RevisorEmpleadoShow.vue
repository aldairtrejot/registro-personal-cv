<template>
  <div class="container-xl py-4 imss-theme">
    <div class="row justify-content-center">
      <div class="col-12">
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
                    <div class="d-flex justify-content-between align-items-center gap-2">
                      <div class="imss-label mb-0">Puesto actual</div>

                      <button
                        type="button"
                        class="btn btn-sm btn-outline-imss imss-btn-fixed"
                        @click="toggleEditarPuesto"
                        :disabled="loadingPuesto || loadingCatalogoPuestos"
                        style="padding: 6px 10px;"
                      >
                        <span v-if="!editandoPuesto">Editar</span>
                        <span v-else>Cancelar</span>
                      </button>
                    </div>

                    <div v-if="!editandoPuesto" class="imss-value mt-2">
                      {{ empleado.puesto_actual || 'N/D' }}
                    </div>

                    <div v-else class="mt-2">
                      <div v-if="loadingCatalogoPuestos" class="text-muted small">
                        Cargando catálogo de puestos…
                      </div>

                      <select
                        v-else
                        v-model.number="puestoIdEdit"
                        class="form-select form-select-sm imss-select"
                        :disabled="loadingPuesto"
                      >
                        <option :value="0">Selecciona un puesto…</option>
                        <option
                          v-for="p in puestos"
                          :key="p.id_puesto"
                          :value="Number(p.id_puesto)"
                        >
                          {{ p.id_puesto }} - {{ p.nombre }}
                        </option>
                      </select>

                      <div class="d-flex gap-2 mt-2">
                        <button
                          type="button"
                          class="btn btn-imss btn-sm imss-btn-fixed"
                          @click="guardarPuestoActual"
                          :disabled="loadingPuesto || loadingCatalogoPuestos"
                        >
                          <span v-if="!loadingPuesto">Guardar</span>
                          <span v-else>Guardando…</span>
                        </button>

                        <button
                          type="button"
                          class="btn btn-outline-secondary btn-sm imss-btn-fixed"
                          @click="toggleEditarPuesto"
                          :disabled="loadingPuesto"
                        >
                          Cancelar
                        </button>
                      </div>

                      <div class="text-muted small mt-2">
                        * Se guarda el <strong>id_puesto</strong> del catálogo.
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-4">
                <div class="imss-actions-panel">
                  <a :href="pdfEmpleadoUrl" class="btn btn-outline-success btn-sm w-100 imss-btn-fixed">
                    <i class="ti ti-file-type-pdf me-1" aria-hidden="true"></i>
                    Descargar PDF
                  </a>

                  <div class="d-grid gap-2 mt-2">
                    <button
                      type="button"
                      class="btn btn-imss btn-sm imss-btn-fixed"
                      @click="cambiarStatus('aprobado')"
                      :disabled="loading"
                    >
                      <i v-if="!loading" class="ti ti-circle-check me-1" aria-hidden="true"></i>
                      <span v-if="!loading">Aprobar CV</span>
                      <span v-else>Procesando…</span>
                    </button>

                    <button
                      type="button"
                      class="btn btn-outline-imss btn-sm imss-btn-fixed"
                      @click="abrirModalFolio"
                      :disabled="loading"
                    >
                      <i class="ti ti-number me-1" aria-hidden="true"></i>
                      Personalizar folio del CV
                    </button>

                    <button
                      type="button"
                      class="btn btn-imss-danger btn-sm imss-btn-fixed"
                      @click="abrirModalRechazo"
                      :disabled="loading"
                    >
                      <i class="ti ti-circle-x me-1" aria-hidden="true"></i>
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

        <div class="row g-3" v-if="empleado">
          <div class="col-12 col-lg-4">
            <div
              v-if="statusLocal === 'rechazado'"
              class="card imss-card imss-card-danger mb-3"
            >
              <div class="card-header imss-section-header imss-section-header-danger">
                <h3 class="imss-section-title mb-0">Motivo de rechazo</h3>
              </div>
              <div class="card-body">
                <div v-if="empleado.motivo_rechazo_cv" class="imss-rechazo-texto">
                  {{ empleado.motivo_rechazo_cv }}
                </div>
                <div v-else class="text-muted">
                  No hay motivo registrado.
                </div>
              </div>
            </div>

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
              </div>
            </div>
          </div>

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
                    Sector: <span>{{ displaySector(exp.sector) }}</span>
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

        <div
          class="modal fade"
          id="modalRechazoCv"
          tabindex="-1"
          aria-labelledby="modalRechazoCvLabel"
          aria-hidden="true"
          ref="modalRechazoRef"
        >
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content imss-modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalRechazoCvLabel">Rechazar CV</h5>
                <button type="button" class="btn-close" @click="cerrarModalRechazo" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-2 text-muted small">
                  Escribe el motivo. Este se enviará al empleado por correo y se guardará en el expediente.
                </div>

                <label class="form-label fw-semibold">Motivo de rechazo *</label>
                <textarea
                  class="form-control"
                  rows="4"
                  v-model="motivoRechazo"
                  @input="errorMotivo = ''"
                  placeholder="Ej: Falta adjuntar información / corregir fechas / etc."
                  :disabled="loading"
                ></textarea>

                <div v-if="errorMotivo" class="text-danger small mt-2">
                  {{ errorMotivo }}
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary imss-btn-fixed" @click="cerrarModalRechazo" :disabled="loading">
                  Cancelar
                </button>
                <button type="button" class="btn btn-imss-danger imss-btn-fixed" @click="confirmarRechazo" :disabled="loading">
                  <span v-if="!loading">Confirmar rechazo</span>
                  <span v-else>Procesando…</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div
          class="modal fade"
          id="modalFolioCv"
          tabindex="-1"
          aria-labelledby="modalFolioCvLabel"
          aria-hidden="true"
          ref="modalFolioRef"
        >
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content imss-modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalFolioCvLabel">Personalizar folio del CV</h5>
                <button type="button" class="btn-close" @click="cerrarModalFolio" aria-label="Close"></button>
              </div>

              <div class="modal-body">
                <div class="text-muted small mb-2">
                  Usa esta opción solo si el CV fue registrado fuera del sistema y necesitas capturar el folio real.
                  <br />
                  <strong>El folio debe ser únicamente numérico</strong> y no puede repetirse.
                </div>

                <label class="form-label fw-semibold">Folio (solo número) *</label>
                <input
                  type="number"
                  class="form-control"
                  v-model.number="folioManual"
                  min="1"
                  step="1"
                  placeholder="Ej: 125"
                  :disabled="loading"
                />

                <div v-if="errorFolio" class="text-danger small mt-2">
                  {{ errorFolio }}
                </div>

                <div class="text-muted small mt-2">
                  Folio actual: <strong>{{ empleado?.folio_cv || 'Sin folio' }}</strong>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary imss-btn-fixed" @click="cerrarModalFolio" :disabled="loading">
                  Cancelar
                </button>
                <button type="button" class="btn btn-imss imss-btn-fixed" @click="confirmarFolioManual" :disabled="loading">
                  <span v-if="!loading">Guardar folio</span>
                  <span v-else>Procesando…</span>
                </button>
              </div>
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

      puestos: [],
      loadingCatalogoPuestos: false,

      editandoPuesto: false,
      puestoIdEdit: 0,
      loadingPuesto: false,

      motivoRechazo: '',
      errorMotivo: '',
      modalRechazo: null,

      folioManual: null,
      errorFolio: '',
      modalFolio: null,

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
    normalizeText(value) {
      return String(value ?? '')
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
    },
    displaySector(value) {
      const n = this.normalizeText(value)
      if (n === 'PUBLICO') return 'PÚBLICO'
      if (n === 'PRIVADO') return 'PRIVADO'
      return 'Sin especificar'
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
    mapStatusFromInt(estatus_cv) {
      switch (Number(estatus_cv)) {
        case 1: return 'edicion'
        case 2: return 'enviado'
        case 3: return 'aprobado'
        case 4: return 'rechazado'
        default: return 'sin_cv'
      }
    },

    async cargarCatalogoPuestos() {
      try {
        this.loadingCatalogoPuestos = true
        const { data } = await axios.get('/api/revisor/catalogos/puestos')
        this.puestos = Array.isArray(data) ? data : []
      } catch (e) {
        this.mensaje = { tipo: 'error', texto: 'No se pudo cargar el catálogo de puestos.' }
      } finally {
        this.loadingCatalogoPuestos = false
      }
    },

    async cargarDetalle(id) {
      try {
        const { data } = await axios.get(`/api/revisor/empleados/${id}`)
        this.empleado = data.empleado
        this.experiencias = data.experiencias || []
        this.estudios = data.estudios || null
        this.cursos = data.cursos || []
        this.statusLocal = this.mapStatusFromInt(this.empleado.estatus_cv)

        this.puestoIdEdit = Number(this.empleado?.id_puesto || 0)
      } catch (e) {
        this.mensaje = { tipo: 'error', texto: 'No se pudo cargar la información del empleado.' }
      }
    },

    async toggleEditarPuesto() {
      this.mensaje = null
      this.editandoPuesto = !this.editandoPuesto

      if (this.editandoPuesto) {
        if (!this.puestos.length) {
          await this.cargarCatalogoPuestos()
        }
        this.puestoIdEdit = Number(this.empleado?.id_puesto || 0)
      }
    },

    async guardarPuestoActual() {
      try {
        if (!this.empleado) return
        const id = this.empleado.id_tbl_empleados ?? this.empleado.id

        const idPuesto = Number(this.puestoIdEdit || 0)
        if (!idPuesto) {
          this.mensaje = { tipo: 'error', texto: 'Selecciona un puesto antes de guardar.' }
          return
        }

        this.loadingPuesto = true
        const { data } = await axios.post(`/api/revisor/empleados/${id}/puesto`, {
          id_puesto: idPuesto,
        })

        this.empleado.id_puesto = data?.id_puesto ?? idPuesto
        this.empleado.puesto_actual = data?.puesto_actual ?? this.empleado.puesto_actual

        this.editandoPuesto = false
        this.mensaje = { tipo: 'ok', texto: 'Puesto actualizado correctamente.' }
        setTimeout(() => { this.mensaje = null }, 4000)
      } catch (e) {
        const msg = e?.response?.data?.message || 'No se pudo actualizar el puesto.'
        this.mensaje = { tipo: 'error', texto: msg }
      } finally {
        this.loadingPuesto = false
      }
    },

    async cambiarStatus(status) {
      try {
        if (!this.empleado) return
        const id = this.empleado.id_tbl_empleados ?? this.empleado.id

        this.loading = true
        const { data } = await axios.post(`/api/revisor/empleados/${id}/estatus`, { status })

        this.statusLocal = status

        if (status === 'aprobado' && data?.folio) {
          this.empleado.folio_cv = data.folio
        }

        if (status !== 'rechazado' && this.empleado) {
          this.empleado.motivo_rechazo_cv = null
        }

        this.mensaje = {
          tipo: 'ok',
          texto: data?.message || `Estatus actualizado: ${this.statusLabel(status)}`,
        }

        await this.cargarDetalle(id)
        setTimeout(() => { this.mensaje = null }, 4000)
      } catch (e) {
        const msg = e?.response?.data?.message || 'No se pudo actualizar el estatus.'
        this.mensaje = { tipo: 'error', texto: msg }
      } finally {
        this.loading = false
      }
    },

    abrirModalRechazo() {
      this.mensaje = null
      this.errorMotivo = ''
      this.motivoRechazo = ''

      if (this.modalRechazo) {
        this.modalRechazo.show()
      }
    },
    cerrarModalRechazo() {
      if (this.modalRechazo) {
        this.modalRechazo.hide()
      }
    },

    async confirmarRechazo() {
      try {
        if (!this.empleado) return

        const motivo = String(this.motivoRechazo || '').trim()
        this.errorMotivo = ''

        if (!motivo) {
          this.errorMotivo = 'El motivo es obligatorio.'
          return
        }

        const id = this.empleado.id_tbl_empleados ?? this.empleado.id

        this.loading = true
        const { data } = await axios.post(`/api/revisor/empleados/${id}/estatus`, {
          status: 'rechazado',
          motivo,
        })

        this.statusLocal = 'rechazado'
        this.empleado.motivo_rechazo_cv = data?.motivo_rechazo_cv ?? motivo
        this.cerrarModalRechazo()

        this.mensaje = {
          tipo: data?.correo_enviado === false ? 'error' : 'ok',
          texto: data?.message || 'CV rechazado.',
        }

        await this.cargarDetalle(id)
        setTimeout(() => { this.mensaje = null }, 5000)
      } catch (e) {
        const msg = e?.response?.data?.message || 'No se pudo rechazar el CV.'
        this.mensaje = { tipo: 'error', texto: msg }
      } finally {
        this.loading = false
      }
    },

    abrirModalFolio() {
      this.mensaje = null
      this.errorFolio = ''
      this.folioManual = this.empleado?.folio_cv ? Number(this.empleado.folio_cv) : null

      if (this.modalFolio) {
        this.modalFolio.show()
      }
    },
    cerrarModalFolio() {
      if (this.modalFolio) {
        this.modalFolio.hide()
      }
    },

    async confirmarFolioManual() {
      try {
        if (!this.empleado) return

        const id = this.empleado.id_tbl_empleados ?? this.empleado.id
        const folio = Number(this.folioManual || 0)

        if (!folio || folio < 1 || !Number.isInteger(folio)) {
          this.errorFolio = 'Captura un folio válido (solo número entero mayor a 0).'
          return
        }

        this.loading = true
        this.errorFolio = ''

        const { data } = await axios.post(`/api/revisor/empleados/${id}/folio`, {
          folio,
        })

        this.empleado.folio_cv = data?.folio ?? folio

        this.cerrarModalFolio()
        this.mensaje = { tipo: 'ok', texto: `Folio actualizado correctamente: ${this.empleado.folio_cv}` }
        setTimeout(() => { this.mensaje = null }, 4000)

        await this.cargarDetalle(id)
      } catch (e) {
        const msg = e?.response?.data?.message || 'No se pudo actualizar el folio.'
        this.errorFolio = msg
        this.mensaje = { tipo: 'error', texto: msg }
      } finally {
        this.loading = false
      }
    },
  },

  async mounted() {
    const el = document.getElementById('blade_revisor_empleado_show')
    const id = el?.dataset?.empleadoId

    try {
      const bootstrap = await import('bootstrap/dist/js/bootstrap.bundle.min.js')
      const Modal = bootstrap?.Modal || bootstrap?.default?.Modal

      if (Modal) {
        if (this.$refs.modalRechazoRef) {
          this.modalRechazo = new Modal(this.$refs.modalRechazoRef, { backdrop: 'static', keyboard: false })
        }
        if (this.$refs.modalFolioRef) {
          this.modalFolio = new Modal(this.$refs.modalFolioRef, { backdrop: 'static', keyboard: false })
        }
      }
    } catch (e) {
      try {
        const Modal = window?.bootstrap?.Modal
        if (Modal) {
          if (this.$refs.modalRechazoRef) {
            this.modalRechazo = new Modal(this.$refs.modalRechazoRef, { backdrop: 'static', keyboard: false })
          }
          if (this.$refs.modalFolioRef) {
            this.modalFolio = new Modal(this.$refs.modalFolioRef, { backdrop: 'static', keyboard: false })
          }
        }
      } catch (_) {}
    }

    this.cargarCatalogoPuestos()
    if (id) this.cargarDetalle(id)
  },
}
</script>

<style scoped>
.imss-theme {
  --imss-border:#e5e7eb;
  --imss-bg:#f6f8f7;
  --imss-ink:#10312b;
  --imss-muted:#6b7280;
  --imss-green:#006341;
  --imss-green-2:#0b7a53;
  --imss-wine:#691c32;
  --imss-red:#9f2241;
  --imss-gold:#bc955c;
}

.imss-card {
  border:1px solid var(--imss-border);
  border-radius:10px;
  overflow:hidden;
  box-shadow:0 8px 22px rgba(16,49,43,.06);
}

.imss-card-danger {
  border-color:#fecaca;
  box-shadow:0 10px 28px rgba(185,28,28,.08);
}

.imss-alert {
  border-radius:12px;
  border-left-width:4px;
}

.imss-header-body {
  position:relative;
  background:
    linear-gradient(90deg, rgba(16,49,43,.05), rgba(188,149,92,.08)),
    #fff;
  padding:22px 18px 18px;
}

.imss-header-body::before {
  content:"";
  position:absolute;
  inset:0 0 auto;
  height:5px;
  background:linear-gradient(90deg,var(--imss-wine),var(--imss-red),var(--imss-green),var(--imss-gold));
}

.imss-kicker {
  font-size:.72rem;
  letter-spacing:.08em;
  text-transform:uppercase;
  color:var(--imss-muted);
  margin-bottom:4px;
}

.imss-title {
  color:var(--imss-ink);
  font-weight:800;
  font-size:1.25rem;
}

.imss-header-grid {
  margin-top:10px;
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:12px;
}

.imss-header-item {
  padding:10px 12px;
  border:1px solid var(--imss-border);
  background:var(--imss-bg);
  border-radius:8px;
}

.imss-label {
  font-size:.78rem;
  font-weight:800;
  color:var(--imss-ink);
  margin-bottom:6px;
}

.imss-value {
  font-size:.92rem;
  color:var(--imss-ink);
  font-weight:600;
}

.imss-code {
  background:#f3f4f6;
  color:#374151;
  border:1px solid #e5e7eb;
  border-radius:6px;
  padding:4px 10px;
  font-size:.78rem;
}

.imss-actions-panel {
  border:1px solid var(--imss-border);
  border-radius:10px;
  padding:14px;
  background:#fff;
  box-shadow:inset 0 0 0 1px rgba(16,49,43,.02);
}

.imss-help {
  font-size:.8rem;
  color:var(--imss-muted);
}

.imss-section-header {
  background:#fff;
  border-bottom:1px solid var(--imss-border);
  padding:14px 16px;
}

.imss-section-header-danger {
  background:#fef2f2;
  border-bottom:1px solid #fecaca;
}

.imss-section-title {
  color:var(--imss-ink);
  font-weight:800;
  font-size:1rem;
}

.imss-rechazo-texto {
  white-space:pre-wrap;
  color:#7f1d1d;
  font-weight:600;
  line-height:1.55;
}

.imss-item {
  padding:12px 0;
  border-bottom:1px solid var(--imss-border);
}

.imss-item-last {
  border-bottom:0;
  padding-bottom:0;
}

.imss-badge {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:999px;
  padding:6px 10px;
  font-size:.78rem;
  font-weight:800;
  border:1px solid transparent;
}

.imss-badge-neutral {
  background:#f3f4f6;
  color:#374151;
  border-color:#e5e7eb;
}

.imss-badge-warning {
  background:#fff7ed;
  color:#9a3412;
  border-color:#fed7aa;
}

.imss-badge-success {
  background:#ecfdf5;
  color:#065f46;
  border-color:#a7f3d0;
}

.imss-badge-danger {
  background:#fef2f2;
  color:#991b1b;
  border-color:#fecaca;
}

.imss-btn-fixed {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  white-space:nowrap;
  min-height:34px;
}

.btn-imss {
  background:var(--imss-green)!important;
  border-color:var(--imss-green)!important;
  color:#fff!important;
  border-radius:8px;
  font-weight:800;
}

.btn-imss:hover {
  background:var(--imss-green-2)!important;
  border-color:var(--imss-green-2)!important;
}

.btn-outline-imss {
  border-color:rgba(0,99,65,.55)!important;
  color:var(--imss-green)!important;
  border-radius:8px;
  font-weight:800;
}

.btn-outline-imss:hover {
  background:var(--imss-green)!important;
  border-color:var(--imss-green)!important;
  color:#fff!important;
}

.btn-imss-danger {
  background:#b91c1c!important;
  border-color:#b91c1c!important;
  color:#fff!important;
  border-radius:8px;
  font-weight:800;
}

.imss-select {
  border-radius:8px;
  border-color:var(--imss-border);
}

.imss-modal-content {
  border:0;
  border-radius:10px;
  overflow:hidden;
  box-shadow:0 18px 46px rgba(16,49,43,.18);
}

@media (max-width: 992px) {
  .imss-header-grid {
    grid-template-columns:1fr;
  }
}
</style>
