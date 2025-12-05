<template>
  <div class="container-tight py-4">
    <div
      class="card card-md shadow-sm border-0 mx-auto"
      style="max-width: 640px;"
    >
      <div class="card-body p-4 p-md-5">
        <!-- Encabezado del paso -->
        <div
          class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4"
        >
          <div class="mb-3 mb-md-0">
            <span class="badge bg-primary-lt text-uppercase fw-semibold mb-1">
              Paso {{ paso }} de {{ totalPasos }}
            </span>
            <div class="fw-semibold">
              {{ tituloPaso }}
            </div>
            <small class="text-muted">
              {{ descripcionPaso }}
            </small>
          </div>

          <!-- Barra de progreso -->
          <div class="w-100 w-md-50 ms-md-3">
            <div class="progress progress-sm">
              <div
                class="progress-bar"
                role="progressbar"
                :style="{ width: progreso + '%' }"
                :aria-valuenow="progreso"
                aria-valuemin="0"
                aria-valuemax="100"
              ></div>
            </div>
            <div class="d-flex justify-content-between mt-1 small text-muted">
              <span>Inicio</span>
              <span>Fin</span>
            </div>
          </div>
        </div>

        <hr class="mt-0 mb-3" />

        <!-- Mensaje global -->
        <div
          v-if="alerta"
          :class="[
            'alert mb-4',
            tipoAlerta === 'error' ? 'alert-danger' : 'alert-success'
          ]"
          role="alert"
        >
          {{ alerta }}
        </div>

        <!-- =========================
             PASO 1: Enviar código
        ========================== -->
        <div v-if="paso === 1">
          <p class="text-muted mb-4">
            Ingresa tu CURP y un correo electrónico donde recibirás un código
            de verificación para continuar con el registro de tu CV.
          </p>

          <div class="mb-3">
            <label class="form-label fw-semibold">CURP</label>
            <input
              v-model="form.curp"
              type="text"
              class="form-control"
              maxlength="18"
              placeholder="Ej. TICF950130HDFNHL02"
            />
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Correo electrónico</label>
            <input
              v-model="form.correo"
              type="email"
              class="form-control"
              placeholder="Ej. nombre@correo.com"
            />
          </div>

          <div class="d-flex justify-content-end">
            <button
              type="button"
              class="btn btn-primary"
              :disabled="cargando"
              @click="enviarCodigo"
            >
              <span
                v-if="cargando"
                class="spinner-border spinner-border-sm me-2"
              ></span>
              Enviar código
            </button>
          </div>
        </div>

        <!-- =========================
             PASO 2: Validar código
        ========================== -->
        <div v-else-if="paso === 2">
          <p class="text-muted mb-4">
            Revisa tu correo e ingresa el código de verificación que recibiste.
          </p>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">CURP</label>
              <input
                type="text"
                class="form-control bg-light"
                :value="form.curp"
                readonly
              />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Correo electrónico</label>
              <input
                type="email"
                class="form-control bg-light"
                :value="form.correo"
                readonly
              />
            </div>
          </div>

          <div class="border rounded p-3 mb-3 bg-light-subtle">
            <label class="form-label fw-semibold mb-1">
              Código de verificación
            </label>
            <input
              v-model="form.token"
              type="text"
              class="form-control"
              maxlength="10"
              placeholder="Ingresa el código recibido"
            />
            <small class="text-muted">
              El código es válido por 15 minutos. Si no te llega, revisa también
              tu bandeja de correo no deseado.
            </small>
          </div>

          <div class="d-flex justify-content-between mt-3">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="paso = 1"
              :disabled="cargando"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="cargando"
              @click="validarCodigo"
            >
              <span
                v-if="cargando"
                class="spinner-border spinner-border-sm me-2"
              ></span>
              Continuar
            </button>
          </div>
        </div>

        <!-- =========================
             PASO 3: Datos personales
        ========================== -->
        <div v-else-if="paso === 3">
          <p class="text-muted mb-4">
            Verifica o completa tus datos personales tal y como deben aparecer
            en tu CV.
          </p>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Nombre(s)</label>
              <input
                v-model="form.datosPersonales.nombres"
                type="text"
                class="form-control"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Primer apellido</label>
              <input
                v-model="form.datosPersonales.primer_apellido"
                type="text"
                class="form-control"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Segundo apellido</label>
              <input
                v-model="form.datosPersonales.segundo_apellido"
                type="text"
                class="form-control"
              />
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Puesto actual</label>
              <input
                v-model="form.datosPersonales.puesto_actual"
                type="text"
                class="form-control"
                placeholder="Ej. Médico General, Enfermera Especialista"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Fecha de inicio en el puesto</label>
              <input
                v-model="form.datosPersonales.fecha_inicio"
                type="date"
                class="form-control"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Área de adscripción</label>
              <input
                v-model="form.datosPersonales.area_adscripcion"
                type="text"
                class="form-control"
              />
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="paso = 2"
              :disabled="cargando"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="cargando"
              @click="guardarDatosPersonales"
            >
              <span
                v-if="cargando"
                class="spinner-border spinner-border-sm me-2"
              ></span>
              Guardar y continuar
            </button>
          </div>
        </div>

        <!-- =========================
             PASO 4: Experiencia laboral
        ========================== -->
        <div v-else-if="paso === 4">
          <p class="text-muted mb-3">
            Registra de 1 a 3 experiencias laborales más relevantes.
          </p>

          <div
            v-for="(exp, index) in form.experiencias"
            :key="index"
            class="card mb-3 border-0 shadow-sm"
          >
            <div class="card-body">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h4 class="card-title h5 mb-0">Experiencia #{{ index + 1 }}</h4>
                <button
                  v-if="form.experiencias.length > 1"
                  type="button"
                  class="btn btn-link text-danger p-0"
                  @click="eliminarExperiencia(index)"
                >
                  Eliminar
                </button>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Fecha de inicio</label>
                  <input
                    v-model="exp.fecha_inicio"
                    type="date"
                    class="form-control"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Fecha de término</label>
                  <input
                    v-model="exp.fecha_termino"
                    type="date"
                    class="form-control"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Sector</label>
                  <select v-model="exp.sector" class="form-select">
                    <option value="">Selecciona sector</option>
                    <option value="publico">Público</option>
                    <option value="privado">Privado</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Cargo o puesto</label>
                  <input
                    v-model="exp.puesto"
                    type="text"
                    class="form-control"
                  />
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold">
                    Institución o empresa
                  </label>
                  <input
                    v-model="exp.institucion"
                    type="text"
                    class="form-control"
                  />
                </div>
                <div class="col-12">
                  <label class="form-label fw-semibold">
                    Campo de experiencia (máx. 100 caracteres)
                  </label>
                  <textarea
                    v-model="exp.campo"
                    rows="2"
                    class="form-control"
                    maxlength="100"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <div
            class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2"
          >
            <button
              type="button"
              class="btn btn-outline-primary btn-sm"
              @click="agregarExperiencia"
              :disabled="form.experiencias.length >= 3"
            >
              + Agregar experiencia
            </button>

            <div class="d-flex">
              <button
                type="button"
                class="btn btn-outline-secondary me-2"
                @click="paso = 3"
                :disabled="cargando"
              >
                ← Volver
              </button>
              <button
                type="button"
                class="btn btn-primary"
                :disabled="cargando"
                @click="guardarExperiencias"
              >
                <span
                  v-if="cargando"
                  class="spinner-border spinner-border-sm me-2"
                ></span>
                Guardar y continuar
              </button>
            </div>
          </div>
        </div>

        <!-- =========================
             PASO 5: Estudios
        ========================== -->
        <div v-else-if="paso === 5">
          <p class="text-muted mb-4">
            Captura tu formación académica principal.
          </p>

          <div class="mb-3">
            <label class="form-label fw-semibold">Institución</label>
            <input
              v-model="form.estudios.institucion"
              type="text"
              class="form-control"
            />
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">País</label>
              <input
                v-model="form.estudios.pais"
                type="text"
                class="form-control"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nivel máximo de estudios</label>
              <input
                v-model="form.estudios.nivel"
                type="text"
                class="form-control"
                placeholder="Licenciatura, Maestría, etc."
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Número de cédula</label>
              <input
                v-model="form.estudios.numero_cedula"
                type="text"
                class="form-control"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Área de estudios</label>
              <input
                v-model="form.estudios.area_estudios"
                type="text"
                class="form-control"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Carrera genérica</label>
              <input
                v-model="form.estudios.carrera_generica"
                type="text"
                class="form-control"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Carrera específica</label>
              <input
                v-model="form.estudios.carrera_especifica"
                type="text"
                class="form-control"
              />
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="paso = 4"
              :disabled="cargando"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="cargando"
              @click="guardarEstudios"
            >
              <span
                v-if="cargando"
                class="spinner-border spinner-border-sm me-2"
              ></span>
              Guardar y continuar
            </button>
          </div>
        </div>

        <!-- =========================
             PASO 6: Cursos
        ========================== -->
        <div v-else-if="paso === 6">
          <p class="text-muted mb-3">
            Registra de 1 a 3 cursos o capacitaciones relevantes para tu perfil.
          </p>

          <div
            v-for="(curso, index) in form.cursos"
            :key="index"
            class="card mb-3 border-0 shadow-sm"
          >
            <div class="card-body">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h4 class="card-title h5 mb-0">Curso #{{ index + 1 }}</h4>
                <button
                  v-if="form.cursos.length > 1"
                  type="button"
                  class="btn btn-link text-danger p-0"
                  @click="eliminarCurso(index)"
                >
                  Eliminar
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Periodo</label>
                <input
                  v-model="curso.periodo"
                  type="text"
                  class="form-control"
                  placeholder="Ej. Enero - Marzo 2024"
                />
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">
                  Nombre del curso o capacitación
                </label>
                <input
                  v-model="curso.nombre"
                  type="text"
                  class="form-control"
                />
              </div>
              <div class="mb-0">
                <label class="form-label fw-semibold">Nombre de la institución</label>
                <input
                  v-model="curso.institucion"
                  type="text"
                  class="form-control"
                />
              </div>
            </div>
          </div>

          <div
            class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2"
          >
            <button
              type="button"
              class="btn btn-outline-primary btn-sm"
              @click="agregarCurso"
              :disabled="form.cursos.length >= 3"
            >
              + Agregar curso
            </button>

            <div class="d-flex">
              <button
                type="button"
                class="btn btn-outline-secondary me-2"
                @click="paso = 5"
                :disabled="cargando"
              >
                ← Volver
              </button>
              <button
                type="button"
                class="btn btn-primary"
                :disabled="cargando"
                @click="finalizar"
              >
                <span
                  v-if="cargando"
                  class="spinner-border spinner-border-sm me-2"
                ></span>
                Finalizar
              </button>
            </div>
          </div>
        </div>

        <!-- Fallback -->
        <div v-else>
          <p class="text-muted mb-0">Paso no válido.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../../components/axios'

export default {
  name: 'RegistroWizard',
  data() {
    return {
      paso: 1,
      totalPasos: 6,
      cargando: false,
      alerta: null,
      tipoAlerta: 'success',
      empleadoCargado: null,
      form: {
        curp: '',
        correo: '',
        token: '',
        datosPersonales: {
          nombres: '',
          primer_apellido: '',
          segundo_apellido: '',
          puesto_actual: '',
          fecha_inicio: '',
          area_adscripcion: '',
        },
        experiencias: [
          {
            fecha_inicio: '',
            fecha_termino: '',
            sector: '',
            puesto: '',
            institucion: '',
            campo: '',
          },
        ],
        estudios: {
          institucion: '',
          pais: '',
          nivel: '',
          numero_cedula: '',
          carrera_generica: '',
          carrera_especifica: '',
          area_estudios: '',
        },
        cursos: [
          {
            periodo: '',
            nombre: '',
            institucion: '',
          },
        ],
      },
    }
  },
  computed: {
    progreso() {
      return Math.round((this.paso / this.totalPasos) * 100)
    },
    tituloPaso() {
      switch (this.paso) {
        case 1:
          return 'Verificación de identidad'
        case 2:
          return 'Validación de código'
        case 3:
          return 'Datos personales'
        case 4:
          return 'Experiencia laboral'
        case 5:
          return 'Estudios académicos'
        case 6:
          return 'Cursos y capacitaciones'
        default:
          return ''
      }
    },
    descripcionPaso() {
      switch (this.paso) {
        case 1:
          return 'Captura tu CURP y correo para enviar el código.'
        case 2:
          return 'Ingresa el código que recibiste en tu correo.'
        case 3:
          return 'Confirma tus datos generales y de puesto.'
        case 4:
          return 'Registra tu experiencia laboral relevante.'
        case 5:
          return 'Indica tu formación académica principal.'
        case 6:
          return 'Añade tus cursos y envía tu CV.'
        default:
          return ''
      }
    },
  },
  methods: {
    mostrarAlerta(tipo, mensaje) {
      this.tipoAlerta = tipo
      this.alerta = mensaje
      if (mensaje) {
        setTimeout(() => {
          this.alerta = null
        }, 5000)
      }
    },

    // Paso 1
    async enviarCodigo() {
      this.mostrarAlerta(null, null)
      if (!this.form.curp || !this.form.correo) {
        this.mostrarAlerta('error', 'Debes capturar CURP y correo electrónico.')
        return
      }
      this.cargando = true
      try {
        const { data } = await axios.post('/api/registro-cv/send-token', {
          curp: this.form.curp,
          correo: this.form.correo,
        })
        if (data.ok) {
          this.mostrarAlerta('success', data.message || 'Código enviado.')
          if (data.token_demo) {
            console.log('TOKEN DEMO:', data.token_demo)
          }
          this.paso = 2
        } else {
          this.mostrarAlerta('error', data.message || 'No se pudo enviar el código.')
        }
      } catch (e) {
        console.error(e)
        this.mostrarAlerta('error', 'Ocurrió un error al enviar el código.')
      } finally {
        this.cargando = false
      }
    },

    // Paso 2
    async validarCodigo() {
      this.mostrarAlerta(null, null)
      if (!this.form.token) {
        this.mostrarAlerta('error', 'Debes ingresar el código de verificación.')
        return
      }
      this.cargando = true
      try {
        const { data } = await axios.post('/api/registro-cv/validate-token', {
          curp: this.form.curp,
          correo: this.form.correo,
          token: this.form.token,
        })
        if (data.ok) {
          this.empleadoCargado = data.empleado || null
          if (this.empleadoCargado) {
            this.form.datosPersonales.nombres =
              this.empleadoCargado.nombre || ''
            this.form.datosPersonales.primer_apellido =
              this.empleadoCargado.primer_apellido || ''
            this.form.datosPersonales.segundo_apellido =
              this.empleadoCargado.segundo_apellido || ''
            this.form.datosPersonales.puesto_actual =
              this.empleadoCargado.puesto_actual || ''
            this.form.datosPersonales.fecha_inicio =
              this.empleadoCargado.fecha_inicio_puesto || ''
            this.form.datosPersonales.area_adscripcion =
              this.empleadoCargado.area_adscripcion || ''
          }
          this.mostrarAlerta('success', 'Código validado correctamente.')
          this.paso = 3
        } else {
          this.mostrarAlerta('error', data.message || 'Código inválido o expirado.')
        }
      } catch (e) {
        console.error(e)
        this.mostrarAlerta('error', 'Ocurrió un error al validar el código.')
      } finally {
        this.cargando = false
      }
    },

    // Paso 3
    async guardarDatosPersonales() {
      this.mostrarAlerta(null, null)
      this.cargando = true
      try {
        await axios.post('/api/registro-cv/datos-personales', {
          curp: this.form.curp,
          ...this.form.datosPersonales,
        })
        this.mostrarAlerta('success', 'Datos personales guardados.')
        this.paso = 4
      } catch (e) {
        console.error(e)
        this.mostrarAlerta(
          'error',
          'No se pudieron guardar los datos personales.'
        )
      } finally {
        this.cargando = false
      }
    },

    // Paso 4
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
      this.form.experiencias.splice(index, 1)
    },
    async guardarExperiencias() {
      this.mostrarAlerta(null, null)
      this.cargando = true
      try {
        await axios.post('/api/registro-cv/experiencias', {
          curp: this.form.curp,
          experiencias: this.form.experiencias.map((e) => ({
            fecha_inicio: e.fecha_inicio || null,
            fecha_termino: e.fecha_termino || null,
            sector: e.sector || null,
            puesto: e.puesto || null,
            institucion: e.institucion || null,
            campo: e.campo || null,
          })),
        })
        this.mostrarAlerta('success', 'Experiencias guardadas.')
        this.paso = 5
      } catch (e) {
        console.error(e)
        this.mostrarAlerta('error', 'No se pudieron guardar las experiencias.')
      } finally {
        this.cargando = false
      }
    },

    // Paso 5
    async guardarEstudios() {
      this.mostrarAlerta(null, null)
      this.cargando = true
      try {
        await axios.post('/api/registro-cv/estudios', {
          curp: this.form.curp,
          ...this.form.estudios,
        })
        this.mostrarAlerta('success', 'Estudios guardados.')
        this.paso = 6
      } catch (e) {
        console.error(e)
        this.mostrarAlerta('error', 'No se pudieron guardar los estudios.')
      } finally {
        this.cargando = false
      }
    },

    // Paso 6
    agregarCurso() {
      if (this.form.cursos.length >= 3) return
      this.form.cursos.push({
        periodo: '',
        nombre: '',
        institucion: '',
      })
    },
    eliminarCurso(index) {
      this.form.cursos.splice(index, 1)
    },
    async finalizar() {
      this.mostrarAlerta(null, null)
      this.cargando = true
      try {
        await axios.post('/api/registro-cv/cursos', {
          curp: this.form.curp,
          cursos: this.form.cursos,
          enviar: true,
        })
        this.mostrarAlerta(
          'success',
          'Tu CV se envió correctamente. Serás redirigido en unos segundos.'
        )

        const urlFin =
          window.CV_FINISH_URL || '/registro-personal-cv/public/login'
        setTimeout(() => {
          window.location.href = urlFin
        }, 2500)
      } catch (e) {
        console.error(e)
        this.mostrarAlerta('error', 'No se pudieron guardar los cursos.')
      } finally {
        this.cargando = false
      }
    },
  },
}
</script>
