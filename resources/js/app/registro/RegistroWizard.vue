<template>
  <div class="cv-wrapper">
    <article class="cv-card">
      <header class="cv-hero cv-hero--compact">
        <div class="cv-hero__compact-main">
          <div class="cv-hero__logo-wrap">
            <img
              :src="logoBienestar"
              alt="IMSS Bienestar"
              class="cv-hero__logo"
              @error="$event.target.style.display = 'none'"
            >
          </div>

          <div class="cv-hero__text">
            <p class="cv-eyebrow">IMSS Bienestar · Registro curricular</p>
            <h1 class="cv-title">Proceso Curricular</h1>
            <p class="cv-subtitle">
              Captura tu información en un flujo guiado, claro y seguro.
            </p>
          </div>

          <div class="cv-hero__quick-status">
            <div class="cv-quick-pill">
              <span>Paso</span>
              <strong>{{ pasoActual }} / {{ totalPasos }}</strong>
            </div>
            <div class="cv-quick-pill cv-quick-pill--section">
              <span>Sección</span>
              <strong>{{ nombrePasoActual }}</strong>
            </div>
          </div>
        </div>
      </header>

      <section class="cv-progress-card" aria-label="Progreso del registro curricular">
        <div class="cv-progress-card__head">
          <div>
            <span class="cv-progress-kicker">Progreso general</span>
            <h2 class="cv-progress-title">Paso {{ pasoActual }} de {{ totalPasos }}</h2>
          </div>
          <div class="cv-progress-caption">{{ nombrePasoActual }}</div>
        </div>

        <div class="cv-progress-track" aria-hidden="true">
          <div class="cv-progress-bar" :style="{ width: porcentajeProgreso + '%' }"></div>
        </div>

        <div class="cv-step-list" aria-label="Listado de pasos">
          <div
            v-for="n in totalPasos"
            :key="n"
            class="cv-step-item"
            :class="{ 'is-active': n === pasoActual, 'is-done': n < pasoActual }"
          >
            <span class="cv-step-dot">{{ n }}</span>
          </div>
        </div>
      </section>

      <div v-if="mensaje" class="alert cv-alert" :class="mensajeClase" role="alert" aria-live="polite">
        {{ mensaje.texto }}
      </div>

      <section class="cv-content">
        <!-- PASO 1 -->
        <div v-if="pasoActual === 1" class="cv-panel">
          <div class="cv-panel-head">
            <div>
              <span class="cv-panel-tag">Paso 1</span>
              <h2 class="cv-section-title">Acceso</h2>
            </div>
            <p class="cv-section-subtitle">
              Ingresa tu CURP y un correo electrónico. Se validará únicamente que tu CURP exista en la base de datos.
            </p>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">CURP</label>
              <input
                v-model.trim="form.curp"
                @input="form.curp = toUpperText(form.curp)"
                type="text"
                maxlength="18"
                class="form-control"
                placeholder="Ej. TICS950101HDFABC01"
              >
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Correo electrónico</label>
              <input
                v-model.trim="form.correo"
                type="email"
                class="form-control"
                maxlength="254"
                placeholder="Ej. nombre@correo.com"
              >
              <div class="form-text">Este correo se registrará en tu CV.</div>
            </div>
          </div>

          <div class="cv-actions cv-actions-end">
            <button
              type="button"
              class="btn btn-primary"
              :disabled="loading"
              @click="validarCurp"
            >
              <span v-if="!loading">Continuar</span>
              <span v-else>Validando…</span>
            </button>
          </div>
        </div>

        <!-- PASO 2 -->
        <div v-else-if="pasoActual === 2" class="cv-panel">
          <div class="cv-panel-head">
            <div>
              <span class="cv-panel-tag">Paso 2</span>
              <h2 class="cv-section-title">Datos personales</h2>
            </div>
            <p class="cv-section-subtitle">
              Verifica o completa tus datos tal y como deben aparecer en tu CV.
            </p>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Correo electrónico</label>
              <input v-model.trim="form.correo" type="email" class="form-control" maxlength="254">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Nacionalidad</label>
              <select v-model="form.nacionalidad" class="form-select">
                <option value="">Selecciona…</option>
                <option value="NACIONAL">NACIONAL</option>
                <option value="EXTRANJERO">EXTRANJERO</option>
              </select>
              <div class="form-text">Solo se permite: NACIONAL o EXTRANJERO.</div>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Nombre(s)</label>
              <input
                v-model.trim="form.nombres"
                @input="form.nombres = toUpperText(form.nombres)"
                type="text"
                class="form-control"
                maxlength="150"
              >
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Primer apellido</label>
              <input
                v-model.trim="form.primer_apellido"
                @input="form.primer_apellido = toUpperText(form.primer_apellido)"
                type="text"
                class="form-control"
                maxlength="150"
              >
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Segundo apellido</label>
              <input
                v-model.trim="form.segundo_apellido"
                @input="form.segundo_apellido = toUpperText(form.segundo_apellido)"
                type="text"
                class="form-control"
                maxlength="150"
              >
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Puesto actual</label>
              <select v-model="form.id_puesto" class="form-select" @change="syncPuestoGenerico">
                <option :value="null">Selecciona…</option>
                <option v-for="puesto in catalogos.puestos" :key="puesto.id" :value="puesto.id">
                  {{ puesto.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Puesto específico</label>
              <select v-model="form.id_puesto_especifico" class="form-select" @change="syncPuestoEspecifico">
                <option :value="null">Selecciona…</option>
                <option v-for="puesto in catalogos.puestosEspecificos" :key="puesto.id" :value="puesto.id">
                  {{ puesto.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Fecha de inicio en el puesto</label>
              <input v-model="form.fecha_inicio" type="date" class="form-control">
              <div class="form-text">Formato: AAAA-MM-DD</div>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Unidad de adscripción</label>
              <select v-model="form.id_unidad" class="form-select" @change="cargarCoordinacionesUnidad">
                <option :value="null">Selecciona…</option>
                <option v-for="uni in catalogos.unidades" :key="uni.id" :value="uni.id">
                  {{ uni.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Coordinación</label>
              <select
                v-model="form.id_coordinacion"
                class="form-select"
                :disabled="!catalogos.coordinaciones.length"
                @change="syncAreaAdscripcionTexto"
              >
                <option :value="null">Selecciona…</option>
                <option v-for="coord in catalogos.coordinaciones" :key="coord.id" :value="coord.id">
                  {{ coord.nombre }}
                </option>
              </select>
            </div>
          </div>

          <div class="cv-actions cv-actions-between">
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(1)">
              ← Volver
            </button>

            <button type="button" class="btn btn-primary" :disabled="loading" @click="guardarDatosPersonales">
              <span v-if="!loading">Guardar y continuar</span>
              <span v-else>Guardando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 3 -->
        <div v-else-if="pasoActual === 3" class="cv-panel">
          <div class="cv-panel-head">
            <div>
              <span class="cv-panel-tag">Paso 3</span>
              <h2 class="cv-section-title">Experiencia laboral</h2>
            </div>
            <p class="cv-section-subtitle">
              Registra tu experiencia laboral. Puedes capturar hasta 3 experiencias.
            </p>
          </div>

          <div v-for="(exp, index) in form.experiencias" :key="index" class="cv-block">
            <div class="cv-block-header">
              <h3 class="cv-block-title">Experiencia #{{ index + 1 }}</h3>
              <button
                v-if="form.experiencias.length > 1"
                type="button"
                class="btn btn-link text-danger p-0 cv-link-remove"
                @click="eliminarExperiencia(index)"
              >
                Eliminar
              </button>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Fecha de inicio</label>
                <input v-model="exp.fecha_inicio" type="date" class="form-control">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Fecha de término</label>
                <input v-model="exp.fecha_termino" type="date" class="form-control">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Sector</label>
                <select v-model="exp.sector" class="form-select">
                  <option value="">Selecciona…</option>
                  <option value="PÚBLICO">PÚBLICO</option>
                  <option value="PRIVADO">PRIVADO</option>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Puesto</label>
                <input
                  v-model.trim="exp.puesto"
                  @input="exp.puesto = toUpperText(exp.puesto)"
                  type="text"
                  class="form-control"
                  maxlength="150"
                >
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Institución</label>
                <input
                  v-model.trim="exp.institucion"
                  @input="exp.institucion = toUpperText(exp.institucion)"
                  type="text"
                  class="form-control"
                  maxlength="200"
                >
              </div>

              <div class="col-12">
                <label class="form-label">Campo de experiencia</label>
                <input
                  v-model.trim="exp.campo"
                  @input="exp.campo = toUpperText(exp.campo)"
                  type="text"
                  class="form-control"
                  maxlength="100"
                >
                <div class="form-text">Máximo 100 caracteres.</div>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-link p-0 mt-2 cv-link-add"
            :disabled="form.experiencias.length >= 3"
            @click="agregarExperiencia"
          >
            + Agregar otra experiencia (máx. 3)
          </button>

          <div class="cv-actions cv-actions-between">
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(2)">
              ← Volver
            </button>
            <button type="button" class="btn btn-primary" :disabled="loading" @click="guardarExperiencias">
              <span v-if="!loading">Guardar y continuar</span>
              <span v-else>Guardando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 4 -->
        <div v-else-if="pasoActual === 4" class="cv-panel">
          <div class="cv-panel-head">
            <div>
              <span class="cv-panel-tag">Paso 4</span>
              <h2 class="cv-section-title">Estudios académicos</h2>
            </div>
            <p class="cv-section-subtitle">
              Completa la información académica correspondiente a tu formación.
            </p>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Institución</label>
              <input
                v-model.trim="form.estudios.institucion"
                @input="form.estudios.institucion = toUpperText(form.estudios.institucion)"
                type="text"
                class="form-control"
                maxlength="200"
              >
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">País</label>
              <select v-model="form.estudios.id_pais" class="form-select" @change="syncPaisTexto">
                <option :value="null">Selecciona…</option>
                <option v-for="pais in catalogos.paises" :key="pais.id" :value="pais.id">
                  {{ pais.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Nivel de estudios</label>
              <select v-model="form.estudios.id_nivel_estudios" class="form-select" @change="syncNivelTexto">
                <option :value="null">Selecciona…</option>
                <option v-for="nivel in catalogos.nivelesEstudio" :key="nivel.id" :value="nivel.id">
                  {{ nivel.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Número de cédula</label>
              <input
                v-model.trim="form.estudios.numero_cedula"
                @input="form.estudios.numero_cedula = toUpperText(form.estudios.numero_cedula)"
                type="text"
                class="form-control"
                maxlength="50"
              >
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Carrera específica</label>
              <select v-model="form.estudios.id_carrera_especifica" class="form-select" @change="onChangeCarreraEspecifica">
                <option :value="null">Selecciona…</option>
                <option v-for="car in catalogos.carrerasEspecificas" :key="car.id" :value="car.id">
                  {{ car.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Carrera genérica</label>
              <select
                v-model="form.estudios.id_carrera_generica"
                class="form-select"
                :disabled="!catalogos.carrerasGenericas.length"
                @change="onChangeCarreraGenerica"
              >
                <option :value="null">Selecciona…</option>
                <option v-for="car in catalogos.carrerasGenericas" :key="car.id" :value="car.id">
                  {{ car.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Área de estudios</label>
              <select
                v-model="form.estudios.id_area_estudios"
                class="form-select"
                :disabled="!catalogos.areasEstudioFiltradas.length"
                @change="onChangeAreaEstudios"
              >
                <option :value="null">Selecciona…</option>
                <option v-for="area in catalogos.areasEstudioFiltradas" :key="area.id" :value="area.id">
                  {{ area.nombre }}
                </option>
              </select>
            </div>
          </div>

          <div class="cv-actions cv-actions-between">
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(3)">
              ← Volver
            </button>
            <button type="button" class="btn btn-primary" :disabled="loading" @click="guardarEstudios">
              <span v-if="!loading">Guardar y continuar</span>
              <span v-else>Guardando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 5 -->
        <div v-else-if="pasoActual === 5" class="cv-panel">
          <div class="cv-panel-head">
            <div>
              <span class="cv-panel-tag">Paso 5</span>
              <h2 class="cv-section-title">Cursos y capacitaciones</h2>
            </div>
            <p class="cv-section-subtitle">
              Este apartado es opcional. Si no cuentas con cursos o capacitaciones, puedes finalizar sin capturar información.
            </p>
          </div>

          <div v-for="(curso, index) in form.cursos" :key="index" class="cv-block">
            <div class="cv-block-header">
              <h3 class="cv-block-title">Curso #{{ index + 1 }}</h3>
              <button
                v-if="form.cursos.length > 1"
                type="button"
                class="btn btn-link text-danger p-0 cv-link-remove"
                @click="eliminarCurso(index)"
              >
                Eliminar
              </button>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Fecha inicio</label>
                <input
                  v-model="curso.fecha_inicio"
                  type="date"
                  class="form-control"
                  @change="syncCursoPeriodo(curso)"
                >
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Fecha fin</label>
                <input
                  v-model="curso.fecha_fin"
                  type="date"
                  class="form-control"
                  @change="syncCursoPeriodo(curso)"
                >
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Período (generado)</label>
                <input
                  v-model="curso.periodo"
                  type="text"
                  class="form-control"
                  readonly
                  placeholder="DD/MM/AAAA - DD/MM/AAAA"
                >
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Nombre del curso</label>
                <input
                  v-model.trim="curso.nombre"
                  @input="curso.nombre = toUpperText(curso.nombre)"
                  type="text"
                  class="form-control"
                  maxlength="200"
                >
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Institución</label>
                <input
                  v-model.trim="curso.institucion"
                  @input="curso.institucion = toUpperText(curso.institucion)"
                  type="text"
                  class="form-control"
                  maxlength="200"
                >
              </div>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-link p-0 mt-2 cv-link-add"
            :disabled="form.cursos.length >= 5"
            @click="agregarCurso"
          >
            + Agregar otro curso (máx. 5)
          </button>

          <div class="cv-actions cv-actions-three">
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(4)">
              ← Volver
            </button>

            <button type="button" class="btn btn-outline-primary" :disabled="loading" @click="guardarCursos(false)">
              <span v-if="!loading">Guardar borrador</span>
              <span v-else>Guardando…</span>
            </button>

            <button type="button" class="btn btn-success" :disabled="loading" @click="guardarCursos(true)">
              <span v-if="!loading">Finalizar y enviar</span>
              <span v-else>Enviando…</span>
            </button>
          </div>
        </div>
      </section>
    </article>
  </div>
</template>

<script>
import axios from '../../components/axios'
import { BASE_URL } from '../../components/url'
import Swal from 'sweetalert2'

export default {
  name: 'RegistroWizard',
  data() {
    return {
      BASE_URL,

      pasoActual: 1,
      totalPasos: 5,
      loading: false,
      mensaje: null,

      catalogos: {
        paises: [],
        nivelesEstudio: [],
        areasEstudio: [],
        puestos: [],
        puestosEspecificos: [],
        unidades: [],
        coordinaciones: [],
        carrerasEspecificas: [],
        carrerasGenericas: [],
        areasEstudioFiltradas: [],
      },

      form: {
        curp: '',
        correo: '',
        nacionalidad: '',

        nombres: '',
        primer_apellido: '',
        segundo_apellido: '',
        puesto_actual: '',
        fecha_inicio: '',
        area_adscripcion: '',
        id_puesto: null,
        id_puesto_especifico: null,
        id_unidad: null,
        id_coordinacion: null,

        experiencias: [
          { fecha_inicio: '', fecha_termino: '', sector: '', puesto: '', institucion: '', campo: '' },
        ],

        estudios: {
          institucion: '',
          id_pais: null,
          pais: '',
          id_nivel_estudios: null,
          nivel: '',
          numero_cedula: '',
          id_carrera_especifica: null,
          carrera_especifica: '',
          id_carrera_generica: null,
          carrera_generica: '',
          id_area_estudios: null,
          area_estudios: '',
        },

        cursos: [
          { fecha_inicio: '', fecha_fin: '', periodo: '', nombre: '', institucion: '' },
        ],
      },
    }
  },

  computed: {
    porcentajeProgreso() {
      if (this.totalPasos <= 1) return 0
      return ((this.pasoActual - 1) / (this.totalPasos - 1)) * 100
    },

    mensajeClase() {
      if (!this.mensaje) return ''
      return this.mensaje.tipo === 'ok' ? 'alert-success' : 'alert-danger'
    },


    logoImss() {
      return `${this.BASE_URL}/assets/images/imss_logo.png`
    },

    logoBienestar() {
      return `${this.BASE_URL}/assets/images/imss-bienestar-2025.png`
    },

    nombrePasoActual() {
      const pasos = { 
        1: 'Acceso',
        2: 'Datos personales',
        3: 'Experiencia laboral',
        4: 'Estudios académicos',
        5: 'Cursos y capacitaciones',
      }

      return pasos[this.pasoActual] || 'Registro'
    },
  },

  methods: {
    toUpperText(value) {
      return String(value ?? '').toUpperCase()
    },

    normalizeText(value) {
      return String(value ?? '')
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
    },

    canonicalSector(value) {
      const n = this.normalizeText(value)
      if (n === 'PUBLICO') return 'PÚBLICO'
      if (n === 'PRIVADO') return 'PRIVADO'
      return ''
    },

    isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim())
    },

    formatDateDMY(iso) {
      if (!iso) return ''
      const [y, m, d] = String(iso).split('-')
      if (!y || !m || !d) return ''
      return `${d}/${m}/${y}`
    },

    syncCursoPeriodo(curso) {
      const ini = curso?.fecha_inicio || ''
      const fin = curso?.fecha_fin || ''
      curso.periodo = (ini && fin) ? `${this.formatDateDMY(ini)} - ${this.formatDateDMY(fin)}` : ''
    },

    mostrarMensaje(tipo, texto) {
      this.mensaje = { tipo, texto }
      setTimeout(() => { this.mensaje = null }, 5000)
    },

    async mostrarSwalExito(titulo, texto) {
      await Swal.fire({
        icon: 'success',
        title: titulo,
        text: texto,
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#006657',
        background: '#FFFFFF',
      })
    },

    async mostrarSwalFinal() {
      await Swal.fire({
        icon: 'success',
        title: 'Registro concluido',
        html: `
          <div style="font-size:14px; line-height:1.5;">
            Tu registro curricular se completó correctamente.
            <br><br>
            La información fue enviada de manera exitosa.
          </div>
        `,
        confirmButtonText: 'Capturar otro registro',
        confirmButtonColor: '#006657',
        background: '#FFFFFF',
      })

      const finishUrl = window.CV_FINISH_URL || window.location.href

      if (finishUrl && finishUrl !== window.location.href) {
        window.location.href = finishUrl
      } else {
        window.location.reload()
      }
    },

    irPaso(n) {
      if (n >= 1 && n <= this.totalPasos) {
        this.pasoActual = n
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    },

    async cargarCatalogos() {
      try {
        const results = await Promise.allSettled([
          axios.get('/api/cv/catalogos/paises'),
          axios.get('/api/cv/catalogos/niveles-estudio'),
          axios.get('/api/cv/catalogos/areas-estudio'),
          axios.get('/api/cv/catalogos/puestos'),
          axios.get('/api/cv/catalogos/puestos-especificos'),
          axios.get('/api/cv/catalogos/unidades'),
          axios.get('/api/cv/catalogos/carreras-especificas'),
        ])

        const [
          paisesRes,
          nivelesRes,
          areasRes,
          puestosRes,
          puestosEspRes,
          unidadesRes,
          carrerasEspRes,
        ] = results

        if (paisesRes.status === 'fulfilled') this.catalogos.paises = paisesRes.value.data || []
        if (nivelesRes.status === 'fulfilled') this.catalogos.nivelesEstudio = nivelesRes.value.data || []
        if (areasRes.status === 'fulfilled') this.catalogos.areasEstudio = areasRes.value.data || []
        if (puestosRes.status === 'fulfilled') this.catalogos.puestos = puestosRes.value.data || []
        if (puestosEspRes.status === 'fulfilled') this.catalogos.puestosEspecificos = puestosEspRes.value.data || []
        if (unidadesRes.status === 'fulfilled') this.catalogos.unidades = unidadesRes.value.data || []
        if (carrerasEspRes.status === 'fulfilled') this.catalogos.carrerasEspecificas = carrerasEspRes.value.data || []

        this.catalogos.coordinaciones = []
        this.catalogos.carrerasGenericas = []
        this.catalogos.areasEstudioFiltradas = []
      } catch (e) {
        console.error('Error inesperado cargando catálogos', e)
      }
    },

    syncPaisTexto() {
      const id = this.form.estudios.id_pais
      const item = this.catalogos.paises.find((p) => p.id === id)
      this.form.estudios.pais = item ? item.nombre : ''
    },

    syncNivelTexto() {
      const id = this.form.estudios.id_nivel_estudios
      const item = this.catalogos.nivelesEstudio.find((n) => n.id === id)
      this.form.estudios.nivel = item ? item.nombre : ''
    },

    syncPuestoGenerico() {
      const id = this.form.id_puesto
      const item = this.catalogos.puestos.find((p) => p.id === id)
      if (!this.form.id_puesto_especifico) {
        this.form.puesto_actual = item ? item.nombre : ''
      }
    },

    syncPuestoEspecifico() {
      const id = this.form.id_puesto_especifico
      const item = this.catalogos.puestosEspecificos.find((p) => p.id === id)
      this.form.puesto_actual = item ? item.nombre : ''
    },

    async cargarCoordinacionesUnidad() {
      this.form.id_coordinacion = null
      this.catalogos.coordinaciones = []
      const id = this.form.id_unidad

      if (!id) {
        this.syncAreaAdscripcionTexto()
        return
      }

      try {
        const { data } = await axios.get(`/api/cv/catalogos/coordinaciones-por-unidad/${id}`)
        this.catalogos.coordinaciones = data || []
      } catch (e) {
        console.error('Error cargando coordinaciones', e)
      }

      this.syncAreaAdscripcionTexto()
    },

    syncAreaAdscripcionTexto() {
      const unidad = this.catalogos.unidades.find((u) => u.id === this.form.id_unidad)
      const coord = this.catalogos.coordinaciones.find((c) => c.id === this.form.id_coordinacion)

      if (unidad && coord) this.form.area_adscripcion = `${unidad.nombre} - ${coord.nombre}`
      else if (unidad) this.form.area_adscripcion = unidad.nombre
      else this.form.area_adscripcion = ''
    },

    async onChangeCarreraEspecifica() {
      const idEspecifica = this.form.estudios.id_carrera_especifica

      this.form.estudios.carrera_especifica = ''
      this.form.estudios.id_carrera_generica = null
      this.form.estudios.carrera_generica = ''
      this.form.estudios.id_area_estudios = null
      this.form.estudios.area_estudios = ''
      this.catalogos.carrerasGenericas = []
      this.catalogos.areasEstudioFiltradas = []

      if (!idEspecifica) return

      const item = this.catalogos.carrerasEspecificas.find((c) => c.id === idEspecifica)
      this.form.estudios.carrera_especifica = item ? item.nombre : ''

      try {
        const { data } = await axios.get(`/api/cv/catalogos/carreras-genericas/${idEspecifica}`)
        this.catalogos.carrerasGenericas = data || []
      } catch (e) {
        console.error('Error cargando carreras genéricas', e)
      }
    },

    async onChangeCarreraGenerica() {
      const idEspecifica = this.form.estudios.id_carrera_especifica
      const idGenerica = this.form.estudios.id_carrera_generica

      this.form.estudios.carrera_generica = ''
      this.form.estudios.id_area_estudios = null
      this.form.estudios.area_estudios = ''
      this.catalogos.areasEstudioFiltradas = []

      const item = this.catalogos.carrerasGenericas.find((c) => c.id === idGenerica)
      this.form.estudios.carrera_generica = item ? item.nombre : ''

      if (!idEspecifica || !idGenerica) return

      try {
        const { data } = await axios.get(`/api/cv/catalogos/areas-estudio-por-carrera/${idEspecifica}/${idGenerica}`)
        this.catalogos.areasEstudioFiltradas = data || []
      } catch (e) {
        console.error('Error cargando áreas de estudio', e)
      }
    },

    onChangeAreaEstudios() {
      const item = this.catalogos.areasEstudioFiltradas.find((a) => a.id === this.form.estudios.id_area_estudios)
      this.form.estudios.area_estudios = item ? item.nombre : ''
    },

    async validarCurp() {
      this.loading = true
      try {
        this.form.curp = this.toUpperText((this.form.curp || '').trim())
        this.form.correo = (this.form.correo || '').trim()

        if (!this.form.curp || this.form.curp.length !== 18) {
          this.mostrarMensaje('error', 'Captura una CURP válida de 18 caracteres.')
          return
        }

        if (!this.form.correo) {
          this.mostrarMensaje('error', 'Captura tu correo electrónico.')
          return
        }

        if (!this.isValidEmail(this.form.correo)) {
          this.mostrarMensaje('error', 'El correo no tiene un formato válido.')
          return
        }

        const { data } = await axios.post('/api/cv/validar-curp', {
          curp: this.form.curp,
          correo: this.form.correo,
        })

        if (data.empleado) {
          this.form.nombres = this.toUpperText(data.empleado.nombre || '')
          this.form.primer_apellido = this.toUpperText(data.empleado.primer_apellido || '')
          this.form.segundo_apellido = this.toUpperText(data.empleado.segundo_apellido || '')
          this.form.puesto_actual = data.empleado.puesto_actual || ''
          this.form.fecha_inicio = data.empleado.fecha_inicio_puesto || ''
          this.form.area_adscripcion = data.empleado.area_adscripcion || ''
          this.form.id_puesto = data.empleado.id_puesto || null
          this.form.id_unidad = data.empleado.id_unidad_adscripcion || null

          if (this.form.id_unidad) {
            await this.cargarCoordinacionesUnidad()
          }
        }

        this.mostrarMensaje('ok', data.message || 'CURP validada correctamente.')
        await this.mostrarSwalExito('Acceso validado', 'La CURP y el correo se validaron correctamente.')
        this.irPaso(2)
      } catch (error) {
        const msg = error?.response?.data?.message || 'No se pudo continuar. Verifica la CURP.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    async guardarDatosPersonales() {
      this.loading = true
      try {
        this.form.curp = this.toUpperText((this.form.curp || '').trim())
        this.form.correo = (this.form.correo || '').trim()

        if (!this.form.correo || !this.isValidEmail(this.form.correo)) {
          this.mostrarMensaje('error', 'Captura un correo válido antes de continuar.')
          return
        }

        if (!this.form.nacionalidad) {
          this.mostrarMensaje('error', 'Selecciona tu nacionalidad.')
          return
        }

        if (!['NACIONAL', 'EXTRANJERO'].includes(this.form.nacionalidad)) {
          this.mostrarMensaje('error', 'Nacionalidad inválida. Solo: NACIONAL o EXTRANJERO.')
          return
        }

        const payload = {
          curp: this.form.curp,
          correo: this.form.correo,
          nacionalidad: this.form.nacionalidad,

          nombres: this.toUpperText(this.form.nombres),
          primer_apellido: this.toUpperText(this.form.primer_apellido),
          segundo_apellido: this.toUpperText(this.form.segundo_apellido),

          puesto_actual: this.form.puesto_actual,
          fecha_inicio: this.form.fecha_inicio,
          area_adscripcion: this.form.area_adscripcion,
          id_puesto: this.form.id_puesto,
          id_puesto_especifico: this.form.id_puesto_especifico,
          id_unidad_adscripcion: this.form.id_unidad,
          id_coordinacion: this.form.id_coordinacion,
        }

        await axios.post('/api/cv/datos-personales', payload)
        this.mostrarMensaje('ok', 'Datos personales guardados.')
        await this.mostrarSwalExito('Datos personales guardados', 'La información personal se registró correctamente.')
        this.irPaso(3)
      } catch (error) {
        const msg = error?.response?.data?.message || 'No se pudieron guardar los datos personales.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    async guardarExperiencias() {
      this.loading = true
      try {
        this.form.experiencias = this.form.experiencias.map((e) => ({
          ...e,
          sector: this.canonicalSector(e.sector),
          puesto: this.toUpperText(e.puesto),
          institucion: this.toUpperText(e.institucion),
          campo: this.toUpperText(e.campo),
        }))

        await axios.post('/api/cv/experiencias', {
          curp: this.form.curp,
          experiencias: this.form.experiencias,
        })

        this.mostrarMensaje('ok', 'Experiencia laboral guardada.')
        await this.mostrarSwalExito('Experiencia guardada', 'La experiencia laboral se registró correctamente.')
        this.irPaso(4)
      } catch (error) {
        const msg = error?.response?.data?.message || 'No se pudo guardar la experiencia laboral.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    async guardarEstudios() {
      this.loading = true
      try {
        const e = this.form.estudios

        const estaVacio =
          !e.institucion &&
          !e.id_pais &&
          !e.id_nivel_estudios &&
          !e.numero_cedula &&
          !e.id_carrera_especifica &&
          !e.id_carrera_generica &&
          !e.id_area_estudios

        if (estaVacio) {
          this.mostrarMensaje('error', 'El apartado de estudios no puede guardarse en blanco.')
          return
        }

        this.form.estudios.institucion = this.toUpperText(this.form.estudios.institucion)
        this.form.estudios.numero_cedula = this.toUpperText(this.form.estudios.numero_cedula)

        await axios.post('/api/cv/estudios', {
          curp: this.form.curp,
          ...this.form.estudios,
        })

        this.mostrarMensaje('ok', 'Estudios académicos guardados.')
        await this.mostrarSwalExito('Estudios guardados', 'La información académica se registró correctamente.')
        this.irPaso(5)
      } catch (error) {
        const msg = error?.response?.data?.message || 'No se pudieron guardar los estudios académicos.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    async guardarCursos(enviar) {
      this.loading = true
      try {
        const cursosValidos = []

        for (const c of this.form.cursos) {
          c.nombre = this.toUpperText(c.nombre)
          c.institucion = this.toUpperText(c.institucion)
          this.syncCursoPeriodo(c)

          const tieneAlgo = !!(
            c.fecha_inicio ||
            c.fecha_fin ||
            (c.nombre || '').trim() ||
            (c.institucion || '').trim()
          )

          if (!tieneAlgo) continue

          if ((c.fecha_inicio && !c.fecha_fin) || (!c.fecha_inicio && c.fecha_fin)) {
            this.mostrarMensaje('error', 'En cursos: captura Fecha inicio y Fecha fin, ambas.')
            return
          }

          if (c.fecha_inicio && c.fecha_fin && String(c.fecha_fin) < String(c.fecha_inicio)) {
            this.mostrarMensaje('error', 'En cursos: la Fecha fin no puede ser menor que la Fecha inicio.')
            return
          }

          const completo =
            !!c.fecha_inicio &&
            !!c.fecha_fin &&
            !!(c.nombre || '').trim() &&
            !!(c.institucion || '').trim()

          if (!completo) {
            this.mostrarMensaje('error', 'Si capturas un curso, debes completar fechas, nombre e institución.')
            return
          }

          cursosValidos.push({
            fecha_inicio: c.fecha_inicio,
            fecha_fin: c.fecha_fin,
            periodo: c.periodo,
            nombre: c.nombre,
            institucion: c.institucion,
          })
        }

        await axios.post('/api/cv/cursos', {
          curp: this.form.curp,
          cursos: cursosValidos,
          enviar: enviar ? 1 : 0,
        })

        if (enviar) {
          this.mostrarMensaje('ok', 'Has concluido con el registro de todos los datos.')
          await this.mostrarSwalFinal()
        } else {
          this.mostrarMensaje('ok', 'Cursos guardados como borrador.')
          await this.mostrarSwalExito('Cursos guardados', 'La información de cursos se guardó correctamente.')
        }
      } catch (error) {
        const msg = error?.response?.data?.message || 'No se pudieron guardar los cursos.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    agregarExperiencia() {
      if (this.form.experiencias.length >= 3) return

      this.form.experiencias.push({
        fecha_inicio: '',
        fecha_termino: '',
        sector: '',
        puesto: '',
        institucion: '',
        campo: '',
      })
    },

    eliminarExperiencia(index) {
      if (this.form.experiencias.length <= 1) return
      this.form.experiencias.splice(index, 1)
    },

    agregarCurso() {
      if (this.form.cursos.length >= 5) return

      this.form.cursos.push({
        fecha_inicio: '',
        fecha_fin: '',
        periodo: '',
        nombre: '',
        institucion: '',
      })
    },

    eliminarCurso(index) {
      if (this.form.cursos.length <= 1) return
      this.form.cursos.splice(index, 1)
    },
  },

  mounted() {
    this.cargarCatalogos()
  },
}
</script>

<style scoped>
.cv-wrapper {
  --imss-red: #9F2241;
  --imss-wine: #691C32;
  --imss-green: #006657;
  --imss-teal: #235B4E;
  --imss-green-dark: #10312B;
  --imss-gold: #BC955C;
  --imss-beige: #DDC9A3;
  --cv-text: #1f2937;
  --cv-muted: #5f6b72;
  --cv-border: rgba(16, 49, 43, 0.12);
  --cv-border-soft: rgba(16, 49, 43, 0.08);
  --cv-shadow: 0 18px 42px rgba(16, 49, 43, 0.12);

  width: 100%;
}

.cv-card {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.cv-hero {
  position: relative;
  overflow: hidden;
  border-radius: 24px;
  padding: 24px;
  background:
    linear-gradient(135deg, rgba(16, 49, 43, 0.96) 0%, rgba(35, 91, 78, 0.95) 42%, rgba(0, 102, 87, 0.94) 100%),
    linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0));
  box-shadow: var(--cv-shadow);
}

.cv-hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at top left, rgba(255,255,255,0.10), transparent 30%),
    radial-gradient(circle at bottom right, rgba(188,149,92,0.18), transparent 30%),
    linear-gradient(120deg, rgba(159,34,65,0.12), transparent 28%);
  pointer-events: none;
}

.cv-hero__main,
.cv-progress-card,
.cv-panel,
.cv-alert {
  position: relative;
  z-index: 1;
}

.cv-brand-card {
  display: grid;
  grid-template-columns: minmax(240px, 360px) minmax(0, 1fr);
  gap: 22px;
  align-items: center;
}

.cv-brand-card__logos {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  min-height: 138px;
  padding: 16px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.10);
  border: 1px solid rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(6px);
}

.cv-logo-box {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 18px;
  overflow: hidden;
}

.cv-logo-box--imss {
  width: 92px;
  height: 92px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.14);
}

.cv-logo-box--bienestar {
  min-width: 180px;
  height: 92px;
  padding: 12px 18px;
  background: #ffffff;
  border: 1px solid rgba(188, 149, 92, 0.36);
  box-shadow: 0 12px 28px rgba(16, 49, 43, 0.18);
}

.cv-logo-imss {
  width: 62px;
  height: 62px;
  object-fit: contain;
}

.cv-logo-bienestar {
  width: 150px;
  max-width: 100%;
  height: 54px;
  object-fit: contain;
}

.cv-brand-divider {
  width: 1px;
  height: 62px;
  background: linear-gradient(180deg, transparent, rgba(255,255,255,0.42), transparent);
}

.cv-eyebrow {
  margin: 0 0 8px;
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: .14em;
  text-transform: uppercase;
}

.cv-title {
  margin: 0;
  color: #ffffff;
  font-size: clamp(1.6rem, 2.6vw, 2.4rem);
  line-height: 1.06;
  font-weight: 900;
  letter-spacing: -.03em;
}

.cv-subtitle {
  margin: 10px 0 0;
  max-width: 760px;
  color: rgba(255, 255, 255, 0.90);
  font-size: .98rem;
  line-height: 1.6;
}

.cv-hero__summary {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.cv-summary-card {
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.18);
  background: rgba(255, 255, 255, 0.10);
  backdrop-filter: blur(5px);
}

.cv-summary-card--green {
  box-shadow: inset 0 0 0 1px rgba(0, 102, 87, 0.16);
}

.cv-summary-card--gold {
  box-shadow: inset 0 0 0 1px rgba(188, 149, 92, 0.18);
}

.cv-summary-card--wine {
  box-shadow: inset 0 0 0 1px rgba(159, 34, 65, 0.16);
}

.cv-summary-label {
  display: block;
  margin-bottom: 4px;
  color: rgba(255, 255, 255, 0.72);
  font-size: .74rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.cv-summary-value {
  display: block;
  color: #ffffff;
  font-size: 1rem;
  font-weight: 800;
}

.cv-progress-card {
  padding: 18px 20px;
  border-radius: 20px;
  background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(255,255,255,0.90));
  border: 1px solid rgba(16, 49, 43, 0.08);
  box-shadow: 0 10px 24px rgba(16, 49, 43, 0.07);
}

.cv-progress-card__head {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.cv-progress-kicker {
  display: block;
  margin-bottom: 4px;
  color: var(--cv-muted);
  font-size: .76rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.cv-progress-title {
  margin: 0;
  font-size: 1.08rem;
  color: var(--imss-green-dark);
  font-weight: 800;
}

.cv-progress-caption {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 40px;
  padding: 8px 14px;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(221, 201, 163, 0.36), rgba(255,255,255,0.95));
  color: var(--imss-wine);
  border: 1px solid rgba(188, 149, 92, 0.28);
  font-size: .82rem;
  font-weight: 800;
}

.cv-progress-track {
  position: relative;
  height: 10px;
  border-radius: 999px;
  background: rgba(16, 49, 43, 0.08);
  overflow: hidden;
}

.cv-progress-bar {
  position: absolute;
  inset: 0 auto 0 0;
  background: linear-gradient(90deg, var(--imss-wine) 0%, var(--imss-red) 18%, var(--imss-teal) 55%, var(--imss-green) 100%);
  transition: width .3s ease;
}

.cv-step-list {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 16px;
}

.cv-step-item {
  display: flex;
  align-items: center;
  justify-content: center;
}

.cv-step-dot {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #ffffff;
  border: 2px solid rgba(16, 49, 43, 0.16);
  color: var(--cv-muted);
  font-weight: 800;
  font-size: .84rem;
}

.cv-step-item.is-active .cv-step-dot {
  border-color: var(--imss-green);
  color: var(--imss-green);
  background: rgba(0, 102, 87, 0.08);
  box-shadow: 0 0 0 4px rgba(0, 102, 87, 0.08);
}

.cv-step-item.is-done .cv-step-dot {
  border-color: var(--imss-green);
  background: var(--imss-green);
  color: #ffffff;
}

.cv-alert {
  margin: 0;
  border-radius: 16px;
  border-width: 1px;
  box-shadow: 0 8px 18px rgba(16, 49, 43, 0.05);
}

.cv-alert.alert-success {
  color: var(--imss-green-dark);
  background: rgba(0, 102, 87, 0.08);
  border-color: rgba(0, 102, 87, 0.16);
}

.cv-alert.alert-danger {
  color: var(--imss-wine);
  background: rgba(159, 34, 65, 0.08);
  border-color: rgba(159, 34, 65, 0.18);
}

.cv-content {
  display: flex;
  flex-direction: column;
}

.cv-panel {
  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(253,252,249,0.98));
  border: 1px solid var(--cv-border-soft);
  border-radius: 22px;
  padding: 22px;
  box-shadow: 0 12px 30px rgba(16, 49, 43, 0.06);
}

.cv-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(16, 49, 43, 0.08);
}

.cv-panel-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  padding: 4px 12px;
  margin-bottom: 8px;
  border-radius: 999px;
  background: linear-gradient(90deg, rgba(188,149,92,0.22), rgba(221,201,163,0.42));
  color: var(--imss-wine);
  border: 1px solid rgba(188,149,92,0.24);
  font-size: .74rem;
  font-weight: 800;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.cv-section-title {
  margin: 0;
  color: var(--imss-green-dark);
  font-size: 1.16rem;
  line-height: 1.2;
  font-weight: 800;
}

.cv-section-subtitle {
  margin: 0;
  max-width: 560px;
  color: var(--cv-muted);
  font-size: .95rem;
  line-height: 1.6;
}

.cv-panel :deep(.form-label) {
  color: var(--imss-green-dark);
  font-weight: 700;
  margin-bottom: 6px;
}

.cv-panel :deep(.form-text) {
  color: var(--cv-muted);
  font-size: .78rem;
}

.cv-panel :deep(.form-control),
.cv-panel :deep(.form-select) {
  min-height: 46px;
  border-radius: 14px;
  border-color: rgba(16, 49, 43, 0.14);
  background: rgba(255, 255, 255, 0.98);
  color: var(--cv-text);
  box-shadow: none;
}

.cv-panel :deep(.form-control:focus),
.cv-panel :deep(.form-select:focus) {
  border-color: rgba(0, 102, 87, 0.45);
  box-shadow: 0 0 0 .2rem rgba(0, 102, 87, 0.12);
}

.cv-block {
  margin-top: 16px;
  padding: 18px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(248,250,249,0.94));
  border: 1px solid rgba(16, 49, 43, 0.08);
  border-left: 5px solid var(--imss-gold);
}

.cv-block-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.cv-block-title {
  margin: 0;
  color: var(--imss-green-dark);
  font-size: 1rem;
  font-weight: 800;
}

.cv-link-remove,
.cv-link-add {
  color: var(--imss-wine);
  font-weight: 700;
  text-decoration: none;
}

.cv-link-add {
  color: var(--imss-green);
}

.cv-link-remove:hover,
.cv-link-add:hover {
  text-decoration: underline;
}

.cv-actions {
  display: flex;
  gap: 12px;
  margin-top: 22px;
  flex-wrap: wrap;
}

.cv-actions-between {
  justify-content: space-between;
}

.cv-actions-end {
  justify-content: flex-end;
}

.cv-actions-three {
  justify-content: space-between;
}

.cv-panel :deep(.btn) {
  min-height: 44px;
  padding: 10px 18px;
  border-radius: 14px;
  font-weight: 700;
  letter-spacing: .01em;
}

.cv-panel :deep(.btn-primary) {
  background: linear-gradient(135deg, var(--imss-teal), var(--imss-green));
  border-color: var(--imss-green);
}

.cv-panel :deep(.btn-primary:hover),
.cv-panel :deep(.btn-primary:focus) {
  background: linear-gradient(135deg, var(--imss-green-dark), var(--imss-green));
  border-color: var(--imss-green-dark);
}

.cv-panel :deep(.btn-outline-primary) {
  color: var(--imss-green);
  border-color: rgba(0, 102, 87, 0.38);
  background: rgba(0, 102, 87, 0.04);
}

.cv-panel :deep(.btn-outline-primary:hover),
.cv-panel :deep(.btn-outline-primary:focus) {
  color: #ffffff;
  background: var(--imss-green);
  border-color: var(--imss-green);
}

.cv-panel :deep(.btn-success) {
  background: linear-gradient(135deg, var(--imss-wine), var(--imss-red));
  border-color: var(--imss-red);
}

.cv-panel :deep(.btn-success:hover),
.cv-panel :deep(.btn-success:focus) {
  background: linear-gradient(135deg, var(--imss-wine), #8b1835);
  border-color: var(--imss-wine);
}

.cv-panel :deep(.btn-outline-secondary) {
  color: var(--imss-green-dark);
  border-color: rgba(16, 49, 43, 0.18);
  background: #ffffff;
}

.cv-panel :deep(.btn-outline-secondary:hover),
.cv-panel :deep(.btn-outline-secondary:focus) {
  color: #ffffff;
  background: var(--imss-green-dark);
  border-color: var(--imss-green-dark);
}

@media (max-width: 992px) {
  .cv-brand-card {
    grid-template-columns: 1fr;
  }

  .cv-hero__summary {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .cv-panel-head {
    flex-direction: column;
  }
}

@media (max-width: 768px) {
  .cv-hero {
    padding: 18px;
    border-radius: 20px;
  }

  .cv-brand-card__logos {
    flex-direction: column;
    min-height: auto;
  }

  .cv-brand-divider {
    width: 60px;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.42), transparent);
  }

  .cv-logo-box--bienestar {
    min-width: 100%;
  }

  .cv-hero__summary {
    grid-template-columns: 1fr;
  }

  .cv-progress-card {
    padding: 16px;
  }

  .cv-progress-card__head {
    flex-direction: column;
    align-items: stretch;
  }

  .cv-progress-caption {
    width: 100%;
  }

  .cv-panel {
    padding: 16px;
    border-radius: 18px;
  }

  .cv-block {
    padding: 14px;
  }

  .cv-actions-between,
  .cv-actions-end,
  .cv-actions-three {
    justify-content: stretch;
  }

  .cv-actions :deep(.btn) {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .cv-title {
    font-size: 1.45rem;
  }

  .cv-subtitle {
    font-size: .92rem;
  }

  .cv-panel-tag {
    margin-bottom: 6px;
  }

  .cv-step-list {
    gap: 8px;
  }

  .cv-step-dot {
    width: 30px;
    height: 30px;
    font-size: .78rem;
  }
}


/* Encabezado compacto con una sola imagen institucional */
.cv-hero--compact {
  padding: 18px 22px;
  border-radius: 22px;
}

.cv-hero__compact-main {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 18px;
}

.cv-hero__logo-wrap {
  width: 170px;
  height: 74px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border-radius: 18px;
  background: #ffffff;
  border: 1px solid rgba(221, 201, 163, 0.40);
  box-shadow: 0 12px 24px rgba(16, 49, 43, 0.18);
}

.cv-hero__logo {
  width: 142px;
  max-width: 100%;
  max-height: 52px;
  object-fit: contain;
}

.cv-hero__text {
  min-width: 0;
}

.cv-hero__quick-status {
  display: flex;
  align-items: stretch;
  gap: 10px;
}

.cv-quick-pill {
  min-width: 112px;
  padding: 10px 12px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: #ffffff;
}

.cv-quick-pill span {
  display: block;
  margin-bottom: 3px;
  color: rgba(255,255,255,.70);
  font-size: .70rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.cv-quick-pill strong {
  display: block;
  font-size: .9rem;
  line-height: 1.2;
  font-weight: 900;
}

.cv-quick-pill--section {
  min-width: 190px;
}

@media (max-width: 992px) {
  .cv-hero__compact-main {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .cv-hero__logo-wrap {
    margin: 0 auto;
  }

  .cv-hero__quick-status {
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 576px) {
  .cv-hero--compact {
    padding: 16px;
  }

  .cv-hero__logo-wrap {
    width: 100%;
    max-width: 220px;
    height: 66px;
  }

  .cv-hero__quick-status,
  .cv-quick-pill,
  .cv-quick-pill--section {
    width: 100%;
    min-width: 0;
  }
}

</style>
