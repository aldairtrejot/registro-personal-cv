<template>
  <div class="cv-wrapper">
    <div class="cv-card shadow-sm">
      <!-- Encabezado -->
      <header class="cv-header">
        <div class="cv-logo-block">
          <div class="cv-logo-circle">
            <img
              :src="`${BASE_URL}/assets/images/imss_logo.png`"
              alt="IMSS"
              class="cv-logo-img"
              onerror="this.style.display='none'"
            />
          </div>
          <div>
            <h1 class="cv-title">Proceso Curricular</h1>
            <p class="cv-subtitle">Registro de CV</p>
          </div>
        </div>
      </header>

      <!-- Stepper -->
      <section class="cv-stepper">
        <div class="cv-stepper-left">
          <span class="cv-stepper-label">
            PASO {{ pasoVisual }} DE {{ totalVisual }}
          </span>
          <div class="cv-stepper-track">
            <div class="cv-stepper-bar" :style="{ width: porcentajeProgreso + '%' }"></div>
          </div>
        </div>

        <div class="cv-stepper-dots">
          <div
            v-for="n in totalVisual"
            :key="n"
            class="cv-dot"
            :class="{ 'is-active': n === pasoVisual, 'is-done': n < pasoVisual }"
          >
            {{ n }}
          </div>
        </div>
      </section>

      <!-- Mensaje superior -->
      <div v-if="mensaje" class="alert mt-2" :class="mensajeClase">
        {{ mensaje.texto }}
      </div>

      <!-- Contenido de pasos -->
      <section class="cv-content">
        <!-- PASO 1 -->
        <div v-if="pasoActual === 1">
          <h3 class="cv-section-title">1. Verificación de identidad</h3>

          <p class="cv-section-subtitle" v-if="requireToken">
            Ingresa tu CURP y un correo electrónico donde recibirás un código de
            verificación para continuar con el registro de tu CV.
          </p>

          <p class="cv-section-subtitle" v-else>
            Ingresa tu CURP para continuar con el registro de tu CV.
          </p>

          <div class="row g-3 mt-1">
            <div class="col-12">
              <label class="form-label">CURP</label>
              <input
                v-model.trim="form.curp"
                type="text"
                maxlength="18"
                class="form-control"
                placeholder="Ej. TICS950101HDFABC01"
              />
            </div>

            <!-- ✅ Correo solo si requireToken -->
            <div class="col-12" v-if="requireToken">
              <label class="form-label">Correo electrónico</label>
              <input
                v-model.trim="form.correo"
                type="email"
                class="form-control"
                placeholder="Ej. nombre@correo.com"
              />
            </div>
          </div>

          <ul class="cv-helper-list">
            <li v-if="requireToken">Verifica que el correo esté escrito correctamente.</li>
            <li>La CURP debe coincidir con la registrada en Recursos Humanos.</li>
          </ul>

          <div class="cv-actions">
            <button
              type="button"
              class="btn btn-primary w-100"
              :disabled="loading"
              @click="enviarToken"
            >
              <span v-if="!loading">
                {{ requireToken ? 'Enviar código' : 'Continuar' }}
              </span>
              <span v-else>
                {{ requireToken ? 'Enviando...' : 'Validando...' }}
              </span>
            </button>
          </div>
        </div>

        <!-- PASO 2 (solo cuando requireToken=true) -->
        <div v-else-if="pasoActual === 2 && requireToken">
          <h3 class="cv-section-title">2. Código de verificación</h3>
          <p class="cv-section-subtitle">
            Ingresa el código que enviamos a tu correo. Si no lo encuentras,
            revisa también la bandeja de spam o correo no deseado.
          </p>

          <div class="row g-3 mt-1">
            <div class="col-12">
              <label class="form-label">Código de verificación</label>
              <input
                v-model.trim="form.token"
                type="text"
                class="form-control text-center"
                maxlength="10"
                placeholder="Ej. 123456"
              />
            </div>
          </div>

          <div class="cv-actions cv-actions-two">
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(1)">
              ← Volver
            </button>
            <button type="button" class="btn btn-primary" :disabled="loading" @click="validarToken">
              <span v-if="!loading">Validar código</span>
              <span v-else>Validando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 3 - DATOS PERSONALES -->
        <div v-else-if="pasoActual === 3">
          <h3 class="cv-section-title">3. Datos personales</h3>
          <p class="cv-section-subtitle">
            Verifica o completa tus datos tal y como deben aparecer en tu CV.
          </p>

          <div class="row g-3 mt-1">
            <div class="col-12">
              <label class="form-label">Nombre(s)</label>
              <input v-model.trim="form.nombres" type="text" class="form-control" maxlength="150" />
            </div>

            <div class="col-12">
              <label class="form-label">Primer apellido</label>
              <input v-model.trim="form.primer_apellido" type="text" class="form-control" maxlength="150" />
            </div>

            <div class="col-12">
              <label class="form-label">Segundo apellido</label>
              <input v-model.trim="form.segundo_apellido" type="text" class="form-control" maxlength="150" />
            </div>

            <div class="col-12">
              <label class="form-label">Puesto actual</label>
              <select v-model="form.id_puesto" class="form-select" @change="syncPuestoGenerico">
                <option :value="null">Selecciona…</option>
                <option v-for="puesto in catalogos.puestos" :key="puesto.id" :value="puesto.id">
                  {{ puesto.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Puesto específico</label>
              <select v-model="form.id_puesto_especifico" class="form-select" @change="syncPuestoEspecifico">
                <option :value="null">Selecciona…</option>
                <option v-for="puesto in catalogos.puestosEspecificos" :key="puesto.id" :value="puesto.id">
                  {{ puesto.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Fecha de inicio en el puesto</label>
              <input v-model="form.fecha_inicio" type="date" class="form-control" />
              <div class="form-text">Formato: AAAA-MM-DD</div>
            </div>

            <div class="col-12">
              <label class="form-label">Unidad de adscripción</label>
              <select v-model="form.id_unidad" class="form-select" @change="cargarCoordinacionesUnidad">
                <option :value="null">Selecciona…</option>
                <option v-for="uni in catalogos.unidades" :key="uni.id" :value="uni.id">
                  {{ uni.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12">
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

          <div class="cv-actions cv-actions-two">
            <!-- ✅ Si no hay token, el paso anterior real es el 1 -->
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading"
              @click="irPaso(requireToken ? 2 : 1)"
            >
              ← Volver
            </button>

            <button type="button" class="btn btn-primary" :disabled="loading" @click="guardarDatosPersonales">
              <span v-if="!loading">Guardar y continuar</span>
              <span v-else>Guardando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 4 -->
        <div v-else-if="pasoActual === 4">
          <h3 class="cv-section-title">4. Experiencia laboral</h3>
          <p class="cv-section-subtitle">
            Agrega de 1 a 3 experiencias recientes relacionadas con tu puesto.
          </p>

          <div v-for="(exp, index) in form.experiencias" :key="index" class="cv-block">
            <div class="cv-block-header">
              <h4 class="cv-block-title">Experiencia #{{ index + 1 }}</h4>
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
              <div class="col-12">
                <label class="form-label">Fecha de inicio</label>
                <input v-model="exp.fecha_inicio" type="date" class="form-control" />
                <div class="form-text">Formato: AAAA-MM-DD</div>
              </div>

              <div class="col-12">
                <label class="form-label">Fecha de término</label>
                <input v-model="exp.fecha_termino" type="date" class="form-control" />
                <div class="form-text">Formato: AAAA-MM-DD</div>
              </div>

              <div class="col-12">
                <label class="form-label">Sector</label>
                <select v-model="exp.sector" class="form-select">
                  <option value="">Selecciona…</option>
                  <option value="publico">Público</option>
                  <option value="privado">Privado</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Puesto</label>
                <input v-model.trim="exp.puesto" type="text" class="form-control" maxlength="150" />
              </div>

              <div class="col-12">
                <label class="form-label">Institución</label>
                <input v-model.trim="exp.institucion" type="text" class="form-control" maxlength="200" />
              </div>

              <div class="col-12">
                <label class="form-label">Campo de experiencia</label>
                <input v-model.trim="exp.campo" type="text" class="form-control" maxlength="100" />
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

          <div class="cv-actions cv-actions-two">
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(3)">
              ← Volver
            </button>
            <button type="button" class="btn btn-primary" :disabled="loading" @click="guardarExperiencias">
              <span v-if="!loading">Guardar y continuar</span>
              <span v-else>Guardando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 5 -->
        <div v-else-if="pasoActual === 5">
          <h3 class="cv-section-title">5. Estudios académicos</h3>
          <p class="cv-section-subtitle">
            Registra tu nivel máximo de estudios relacionado con tu práctica profesional.
          </p>

          <div class="row g-3 mt-1">
            <div class="col-12">
              <label class="form-label">Institución</label>
              <input v-model.trim="form.estudios.institucion" type="text" class="form-control" maxlength="200" />
            </div>

            <div class="col-12">
              <label class="form-label">País</label>
              <select v-model="form.estudios.id_pais" class="form-select" @change="syncPaisTexto">
                <option :value="null">Selecciona…</option>
                <option v-for="pais in catalogos.paises" :key="pais.id" :value="pais.id">
                  {{ pais.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Nivel de estudios</label>
              <select v-model="form.estudios.id_nivel_estudios" class="form-select" @change="syncNivelTexto">
                <option :value="null">Selecciona…</option>
                <option v-for="nivel in catalogos.nivelesEstudio" :key="nivel.id" :value="nivel.id">
                  {{ nivel.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Número de cédula</label>
              <input v-model.trim="form.estudios.numero_cedula" type="text" class="form-control" maxlength="50" />
            </div>

            <div class="col-12">
              <label class="form-label">Carrera específica</label>
              <select v-model="form.estudios.id_carrera_especifica" class="form-select" @change="onChangeCarreraEspecifica">
                <option :value="null">Selecciona…</option>
                <option v-for="car in catalogos.carrerasEspecificas" :key="car.id" :value="car.id">
                  {{ car.nombre }}
                </option>
              </select>
            </div>

            <div class="col-12">
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

            <div class="col-12">
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

          <div class="cv-actions cv-actions-two">
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(4)">
              ← Volver
            </button>
            <button type="button" class="btn btn-primary" :disabled="loading" @click="guardarEstudios">
              <span v-if="!loading">Guardar y continuar</span>
              <span v-else>Guardando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 6 -->
        <div v-else-if="pasoActual === 6">
          <h3 class="cv-section-title">6. Cursos y capacitaciones</h3>
          <p class="cv-section-subtitle">
            Agrega de 1 a 5 cursos recientes que apoyen tu perfil profesional.
          </p>

          <div v-for="(curso, index) in form.cursos" :key="index" class="cv-block">
            <div class="cv-block-header">
              <h4 class="cv-block-title">Curso #{{ index + 1 }}</h4>
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
              <div class="col-12">
                <label class="form-label">Período</label>
                <input
                  v-model.trim="curso.periodo"
                  type="text"
                  class="form-control"
                  placeholder="DD/MM/AAAA - DD/MM/AAAA"
                  maxlength="100"
                />
                <div class="form-text">
                  Formato sugerido: <strong>DD/MM/AAAA - DD/MM/AAAA</strong> (ej. 05/01/2026 - 20/01/2026)
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Nombre del curso</label>
                <input v-model.trim="curso.nombre" type="text" class="form-control" maxlength="200" />
              </div>

              <div class="col-12">
                <label class="form-label">Institución</label>
                <input v-model.trim="curso.institucion" type="text" class="form-control" maxlength="200" />
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
            <button type="button" class="btn btn-outline-secondary" :disabled="loading" @click="irPaso(5)">
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
    </div>
  </div>
</template>

<script>
import axios from '../../components/axios'
import { BASE_URL } from '../../components/url'

export default {
  name: 'RegistroWizard',
  data() {
    return {
      BASE_URL,

      // ✅ Bandera: si VITE_CV_REQUIRE_TOKEN=false entonces no pedimos token
      requireToken: (import.meta.env.VITE_CV_REQUIRE_TOKEN !== 'false'),

      pasoActual: 1,
      totalPasos: 6,
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
        token: '',
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
        cursos: [{ periodo: '', nombre: '', institucion: '' }],
      },
    }
  },
  computed: {
    // ✅ Paso visual para que no se vea el “paso 2” cuando no hay token
    pasoVisual() {
      if (this.requireToken) return this.pasoActual
      // si token está OFF: el paso 2 no existe visualmente
      return this.pasoActual >= 3 ? this.pasoActual - 1 : this.pasoActual
    },
    totalVisual() {
      return this.requireToken ? this.totalPasos : (this.totalPasos - 1)
    },
    porcentajeProgreso() {
      if (this.totalVisual <= 1) return 0
      return ((this.pasoVisual - 1) / (this.totalVisual - 1)) * 100
    },
    mensajeClase() {
      if (!this.mensaje) return ''
      return this.mensaje.tipo === 'ok' ? 'alert-success' : 'alert-danger'
    },
  },
  methods: {
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
      if (!this.form.id_puesto_especifico) this.form.puesto_actual = item ? item.nombre : ''
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

    mostrarMensaje(tipo, texto) {
      this.mensaje = { tipo, texto }
      setTimeout(() => { this.mensaje = null }, 5000)
    },

    irPaso(n) {
      if (n >= 1 && n <= this.totalPasos) {
        this.pasoActual = n
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    },

    async enviarToken() {
      this.loading = true
      try {
        // ✅ payload dinámico
        const payload = this.requireToken
          ? { curp: this.form.curp, correo: this.form.correo }
          : { curp: this.form.curp }

        // ✅ Solo validar correo único cuando SÍ hay token
        if (this.requireToken) {
          await axios.post('/api/cv/check-correo', payload)
        } else {
          // await axios.post('/api/cv/check-correo', payload) // ⛔ bypass (solo CURP)
        }

        const { data } = await axios.post('/api/cv/send-token', payload)

        // ✅ MODO SIN TOKEN: el backend regresa empleado y brincamos a paso 3
        if (!this.requireToken) {
          if (data.empleado) {
            this.form.nombres = data.empleado.nombre || ''
            this.form.primer_apellido = data.empleado.primer_apellido || ''
            this.form.segundo_apellido = data.empleado.segundo_apellido || ''
            this.form.puesto_actual = data.empleado.puesto_actual || ''
            this.form.fecha_inicio = data.empleado.fecha_inicio_puesto || ''
            this.form.area_adscripcion = data.empleado.area_adscripcion || ''
            this.form.id_puesto = data.empleado.id_puesto || null
            this.form.id_unidad = data.empleado.id_unidad_adscripcion || null
            if (this.form.id_unidad) await this.cargarCoordinacionesUnidad()
          }

          this.mostrarMensaje('ok', data.message || 'CURP validada correctamente.')
          this.irPaso(3)
          return
        }

        // ✅ MODO CON TOKEN (tu flujo original)
        this.mostrarMensaje('ok', data.message || 'Se envió el código a tu correo.')
        this.irPaso(2)

      } catch (error) {
        const msg = error?.response?.data?.message || 'No se pudo continuar. Verifica los datos e inténtalo de nuevo.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    async validarToken() {
      // ✅ seguridad extra: si por algo lo llaman sin token, brinca
      if (!this.requireToken) {
        this.irPaso(3)
        return
      }

      this.loading = true
      try {
        const payload = { curp: this.form.curp, correo: this.form.correo, token: this.form.token }
        const { data } = await axios.post('/api/cv/validate-token', payload)

        if (data.empleado) {
          this.form.nombres = data.empleado.nombre || ''
          this.form.primer_apellido = data.empleado.primer_apellido || ''
          this.form.segundo_apellido = data.empleado.segundo_apellido || ''
          this.form.puesto_actual = data.empleado.puesto_actual || ''
          this.form.fecha_inicio = data.empleado.fecha_inicio_puesto || ''
          this.form.area_adscripcion = data.empleado.area_adscripcion || ''
          this.form.id_puesto = data.empleado.id_puesto || null
          this.form.id_unidad = data.empleado.id_unidad_adscripcion || null
          if (this.form.id_unidad) await this.cargarCoordinacionesUnidad()
        }

        this.mostrarMensaje('ok', 'Código validado correctamente.')
        this.irPaso(3)
      } catch (error) {
        const msg = error?.response?.data?.message || 'El código es inválido o ha expirado.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    async guardarDatosPersonales() {
      this.loading = true
      try {
        const payload = {
          curp: this.form.curp,
          nombres: this.form.nombres,
          primer_apellido: this.form.primer_apellido,
          segundo_apellido: this.form.segundo_apellido,
          puesto_actual: this.form.puesto_actual,
          fecha_inicio: this.form.fecha_inicio,
          area_adscripcion: this.form.area_adscripcion,
          id_puesto: this.form.id_puesto,
          id_unidad_adscripcion: this.form.id_unidad,
        }
        await axios.post('/api/cv/datos-personales', payload)
        this.mostrarMensaje('ok', 'Datos personales guardados.')
        this.irPaso(4)
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
        const payload = { curp: this.form.curp, experiencias: this.form.experiencias }
        await axios.post('/api/cv/experiencias', payload)
        this.mostrarMensaje('ok', 'Experiencia laboral guardada.')
        this.irPaso(5)
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
        const payload = { curp: this.form.curp, ...this.form.estudios }
        await axios.post('/api/cv/estudios', payload)
        this.mostrarMensaje('ok', 'Estudios académicos guardados.')
        this.irPaso(6)
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
        const payload = { curp: this.form.curp, cursos: this.form.cursos, enviar: enviar ? 1 : 0 }
        await axios.post('/api/cv/cursos', payload)

        if (enviar) {
          this.mostrarMensaje('ok', 'Has concluido con el registro de todos los datos.')
          setTimeout(() => { window.location.reload() }, 2500)
        } else {
          this.mostrarMensaje('ok', 'Cursos guardados como borrador.')
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
      this.form.experiencias.push({ fecha_inicio: '', fecha_termino: '', sector: '', puesto: '', institucion: '', campo: '' })
    },
    eliminarExperiencia(index) {
      if (this.form.experiencias.length <= 1) return
      this.form.experiencias.splice(index, 1)
    },
    agregarCurso() {
      if (this.form.cursos.length >= 5) return
      this.form.cursos.push({ periodo: '', nombre: '', institucion: '' })
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
/* (Tu CSS original tal cual) */
.cv-wrapper {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 32px 16px;
}

.cv-card {
  background: #ffffff;
  border-radius: 16px;
  max-width: 740px;
  width: 100%;
  padding: 24px 24px 28px;
}

.cv-header {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 12px;
  margin-bottom: 12px;
}

.cv-logo-block {
  display: flex;
  align-items: center;
  gap: 16px;
}

.cv-logo-circle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #e6f2ee;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cv-logo-img {
  max-width: 40px;
}

.cv-title {
  font-size: 1.25rem;
  margin: 0;
  color: #111827;
}

.cv-subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: #6b7280;
}

.cv-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 8px;
}

.cv-stepper-left { flex: 1; }

.cv-stepper-label {
  display: block;
  font-size: 0.78rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 4px;
}

.cv-stepper-track {
  position: relative;
  height: 4px;
  border-radius: 999px;
  background: #e5e7eb;
  overflow: hidden;
}

.cv-stepper-bar {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  background: #006341;
  transition: width 0.3s ease;
}

.cv-stepper-dots { display: flex; gap: 4px; }

.cv-dot {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #d1d5db;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  color: #6b7280;
}

.cv-dot.is-active { border-color: #006341; color: #006341; font-weight: 600; }
.cv-dot.is-done { border-color: #006341; background: #006341; color: #ffffff; }

.cv-content { margin-top: 4px; }

.cv-section-title { font-size: 1.05rem; margin-bottom: 4px; color: #111827; }
.cv-section-subtitle { font-size: 0.9rem; color: #6b7280; margin-bottom: 12px; }

.cv-helper-list {
  margin-top: 12px;
  margin-bottom: 0;
  padding-left: 18px;
  font-size: 0.85rem;
  color: #4b5563;
}
.cv-helper-list li + li { margin-top: 4px; }

.cv-block {
  margin-top: 16px;
  padding: 14px 14px 10px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
}

.cv-block-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.cv-block-title { font-size: 0.95rem; margin: 0; }

.cv-card .form-label { font-size: 0.86rem; margin-bottom: 4px; color: #374151; }
.cv-card .form-control, .cv-card .form-select {
  font-size: 0.9rem;
  padding: 0.44rem 0.75rem;
  border-radius: 8px;
  border-color: #d1d5db;
}

.cv-card .form-control:focus, .cv-card .form-select:focus {
  border-color: #006341;
  box-shadow: 0 0 0 1px rgba(0, 99, 65, 0.15);
}

.cv-actions { margin-top: 20px; }
.cv-actions-two, .cv-actions-three {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 20px;
}

.cv-actions-three .btn { flex: 1 1 auto; }

.cv-card .btn-primary { background: #006341; border-color: #006341; }
.cv-card .btn-primary:hover { background: #004b2e; border-color: #004b2e; }

.cv-card .btn-outline-primary { color: #006341; border-color: #006341; }
.cv-card .btn-outline-primary:hover { background: #006341; color: #ffffff; border-color: #006341; }

.cv-card .btn-success { background: #0b8450; border-color: #0b8450; }
.cv-card .btn-success:hover { background: #08663e; border-color: #08663e; }

.cv-card a, .cv-card .btn-link { color: #006341; text-decoration: none; }
.cv-card a:hover, .cv-card .btn-link:hover { color: #004b2e; text-decoration: underline; }

.cv-link-add { font-size: 0.88rem; font-weight: 500; }
.cv-link-remove { text-decoration: none; }

@media (max-width: 576px) {
  .cv-card { padding: 18px 16px 22px; }
  .cv-stepper { flex-direction: column; align-items: flex-start; }
  .cv-stepper-dots { margin-left: 2px; }
}
</style>
