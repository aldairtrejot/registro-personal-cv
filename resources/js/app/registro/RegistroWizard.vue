<template>
  <div class="cv-wrapper">
    <div class="cv-card shadow-sm">
      <!-- Encabezado -->
      <header class="cv-header">
        <div class="cv-logo-block">
          <div class="cv-logo-circle">
            <img
              src="/img/imss-logo.png"
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
            PASO {{ pasoActual }} DE {{ totalPasos }}
          </span>
          <div class="cv-stepper-track">
            <div
              class="cv-stepper-bar"
              :style="{ width: porcentajeProgreso + '%' }"
            ></div>
          </div>
        </div>
        <div class="cv-stepper-dots">
          <div
            v-for="n in totalPasos"
            :key="n"
            class="cv-dot"
            :class="{ 'is-active': n === pasoActual, 'is-done': n < pasoActual }"
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
          <p class="cv-section-subtitle">
            Ingresa tu CURP y un correo electrónico donde recibirás un código de
            verificación para continuar con el registro de tu CV.
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
            <div class="col-12">
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
            <li>Verifica que el correo esté escrito correctamente.</li>
            <li>La CURP debe coincidir con la registrada en Recursos Humanos.</li>
          </ul>

          <div class="cv-actions">
            <button
              type="button"
              class="btn btn-primary w-100"
              :disabled="loading"
              @click="enviarToken"
            >
              <span v-if="!loading">Enviar código</span>
              <span v-else>Enviando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 2 -->
        <div v-else-if="pasoActual === 2">
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
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading"
              @click="irPaso(1)"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="loading"
              @click="validarToken"
            >
              <span v-if="!loading">Validar código</span>
              <span v-else>Validando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 3 -->
        <div v-else-if="pasoActual === 3">
          <h3 class="cv-section-title">3. Datos personales</h3>
          <p class="cv-section-subtitle">
            Verifica o completa tus datos tal y como deben aparecer en tu CV.
          </p>

          <div class="row g-3 mt-1">
            <div class="col-12">
              <label class="form-label">Nombre(s)</label>
              <input
                v-model.trim="form.nombres"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Primer apellido</label>
              <input
                v-model.trim="form.primer_apellido"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Segundo apellido</label>
              <input
                v-model.trim="form.segundo_apellido"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Puesto actual</label>
              <input
                v-model.trim="form.puesto_actual"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Fecha de inicio en el puesto</label>
              <input
                v-model="form.fecha_inicio"
                type="date"
                class="form-control"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Área de adscripción</label>
              <input
                v-model.trim="form.area_adscripcion"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
          </div>

          <div class="cv-actions cv-actions-two">
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading"
              @click="irPaso(2)"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="loading"
              @click="guardarDatosPersonales"
            >
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

          <div
            v-for="(exp, index) in form.experiencias"
            :key="index"
            class="cv-block"
          >
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
                <input
                  v-model="exp.fecha_inicio"
                  type="date"
                  class="form-control"
                />
              </div>
              <div class="col-12">
                <label class="form-label">Fecha de término</label>
                <input
                  v-model="exp.fecha_termino"
                  type="date"
                  class="form-control"
                />
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
                <input
                  v-model.trim="exp.puesto"
                  type="text"
                  class="form-control"
                  maxlength="150"
                />
              </div>
              <div class="col-12">
                <label class="form-label">Institución</label>
                <input
                  v-model.trim="exp.institucion"
                  type="text"
                  class="form-control"
                  maxlength="200"
                />
              </div>
              <div class="col-12">
                <label class="form-label">Campo de experiencia</label>
                <input
                  v-model.trim="exp.campo"
                  type="text"
                  class="form-control"
                  maxlength="100"
                />
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
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading"
              @click="irPaso(3)"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="loading"
              @click="guardarExperiencias"
            >
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
              <input
                v-model.trim="form.estudios.institucion"
                type="text"
                class="form-control"
                maxlength="200"
              />
            </div>
            <div class="col-12">
              <label class="form-label">País</label>
              <input
                v-model.trim="form.estudios.pais"
                type="text"
                class="form-control"
                maxlength="100"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Nivel de estudios</label>
              <input
                v-model.trim="form.estudios.nivel"
                type="text"
                class="form-control"
                maxlength="100"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Número de cédula</label>
              <input
                v-model.trim="form.estudios.numero_cedula"
                type="text"
                class="form-control"
                maxlength="50"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Carrera genérica</label>
              <input
                v-model.trim="form.estudios.carrera_generica"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Carrera específica</label>
              <input
                v-model.trim="form.estudios.carrera_especifica"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
            <div class="col-12">
              <label class="form-label">Área de estudios</label>
              <input
                v-model.trim="form.estudios.area_estudios"
                type="text"
                class="form-control"
                maxlength="150"
              />
            </div>
          </div>

          <div class="cv-actions cv-actions-two">
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading"
              @click="irPaso(4)"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="loading"
              @click="guardarEstudios"
            >
              <span v-if="!loading">Guardar y continuar</span>
              <span v-else>Guardando...</span>
            </button>
          </div>
        </div>

        <!-- PASO 6 -->
        <div v-else-if="pasoActual === 6">
          <h3 class="cv-section-title">6. Cursos y capacitaciones</h3>
          <p class="cv-section-subtitle">
            Agrega de 1 a 3 cursos recientes que apoyen tu perfil profesional.
          </p>

          <div
            v-for="(curso, index) in form.cursos"
            :key="index"
            class="cv-block"
          >
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
                  placeholder="Ej. Ene - Mar 2024"
                  maxlength="100"
                />
              </div>
              <div class="col-12">
                <label class="form-label">Nombre del curso</label>
                <input
                  v-model.trim="curso.nombre"
                  type="text"
                  class="form-control"
                  maxlength="200"
                />
              </div>
              <div class="col-12">
                <label class="form-label">Institución</label>
                <input
                  v-model.trim="curso.institucion"
                  type="text"
                  class="form-control"
                  maxlength="200"
                />
              </div>
            </div>
          </div>

          <button
            type="button"
            class="btn btn-link p-0 mt-2 cv-link-add"
            :disabled="form.cursos.length >= 3"
            @click="agregarCurso"
          >
            + Agregar otro curso (máx. 3)
          </button>

          <div class="cv-actions cv-actions-three">
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="loading"
              @click="irPaso(5)"
            >
              ← Volver
            </button>
            <button
              type="button"
              class="btn btn-outline-primary"
              :disabled="loading"
              @click="guardarCursos(false)"
            >
              <span v-if="!loading">Guardar borrador</span>
              <span v-else>Guardando…</span>
            </button>
            <button
              type="button"
              class="btn btn-success"
              :disabled="loading"
              @click="guardarCursos(true)"
            >
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

export default {
  name: 'RegistroWizard',
  data() {
    return {
      pasoActual: 1,
      totalPasos: 6,
      loading: false,
      mensaje: null,
      form: {
        curp: '',
        correo: '',
        token: '',
        // datos personales
        nombres: '',
        primer_apellido: '',
        segundo_apellido: '',
        puesto_actual: '',
        fecha_inicio: '',
        area_adscripcion: '',
        // experiencias
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
        // estudios
        estudios: {
          institucion: '',
          pais: '',
          nivel: '',
          numero_cedula: '',
          carrera_generica: '',
          carrera_especifica: '',
          area_estudios: '',
        },
        // cursos
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
    porcentajeProgreso() {
      if (this.totalPasos <= 1) return 0
      return ((this.pasoActual - 1) / (this.totalPasos - 1)) * 100
    },
    mensajeClase() {
      if (!this.mensaje) return ''
      return this.mensaje.tipo === 'ok' ? 'alert-success' : 'alert-danger'
    },
  },
  methods: {
    mostrarMensaje(tipo, texto) {
      this.mensaje = { tipo, texto }
      setTimeout(() => {
        this.mensaje = null
      }, 5000)
    },
    irPaso(n) {
      if (n >= 1 && n <= this.totalPasos) {
        this.pasoActual = n
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    },

    // --- Paso 1 ---
    async enviarToken() {
      this.loading = true
      try {
        const payload = {
          curp: this.form.curp,
          correo: this.form.correo,
        }
        const { data } = await axios.post('/api/cv/send-token', payload)
        this.mostrarMensaje('ok', data.message || 'Se envió el código a tu correo.')
        console.log('TOKEN DEMO (solo local):', data.token_demo)
        this.irPaso(2)
      } catch (error) {
        const msg =
          error?.response?.data?.message ||
          'No se pudo enviar el código. Verifica los datos e inténtalo de nuevo.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    // --- Paso 2 ---
    async validarToken() {
      this.loading = true
      try {
        const payload = {
          curp: this.form.curp,
          correo: this.form.correo,
          token: this.form.token,
        }
        const { data } = await axios.post('/api/cv/validate-token', payload)

        if (data.empleado) {
          this.form.nombres = data.empleado.nombre || ''
          this.form.primer_apellido = data.empleado.primer_apellido || ''
          this.form.segundo_apellido = data.empleado.segundo_apellido || ''
          this.form.puesto_actual = data.empleado.puesto_actual || ''
          this.form.fecha_inicio = data.empleado.fecha_inicio_puesto || ''
          this.form.area_adscripcion = data.empleado.area_adscripcion || ''
        }

        this.mostrarMensaje('ok', 'Código validado correctamente.')
        this.irPaso(3)
      } catch (error) {
        const msg =
          error?.response?.data?.message ||
          'El código es inválido o ha expirado.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    // --- Paso 3 ---
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
        }
        await axios.post('/api/cv/datos-personales', payload)
        this.mostrarMensaje('ok', 'Datos personales guardados.')
        this.irPaso(4)
      } catch (error) {
        const msg =
          error?.response?.data?.message ||
          'No se pudieron guardar los datos personales.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    // --- Paso 4 ---
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
    async guardarExperiencias() {
      this.loading = true
      try {
        const payload = {
          curp: this.form.curp,
          experiencias: this.form.experiencias,
        }
        await axios.post('/api/cv/experiencias', payload)
        this.mostrarMensaje('ok', 'Experiencia laboral guardada.')
        this.irPaso(5)
      } catch (error) {
        const msg =
          error?.response?.data?.message ||
          'No se pudo guardar la experiencia laboral.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    // --- Paso 5 ---
    async guardarEstudios() {
      this.loading = true
      try {
        const payload = {
          curp: this.form.curp,
          ...this.form.estudios,
        }
        await axios.post('/api/cv/estudios', payload)
        this.mostrarMensaje('ok', 'Estudios académicos guardados.')
        this.irPaso(6)
      } catch (error) {
        const msg =
          error?.response?.data?.message ||
          'No se pudieron guardar los estudios académicos.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },

    // --- Paso 6 ---
    agregarCurso() {
      if (this.form.cursos.length >= 3) return
      this.form.cursos.push({
        periodo: '',
        nombre: '',
        institucion: '',
      })
    },
    eliminarCurso(index) {
      if (this.form.cursos.length <= 1) return
      this.form.cursos.splice(index, 1)
    },
    async guardarCursos(enviar) {
      this.loading = true
      try {
        const payload = {
          curp: this.form.curp,
          cursos: this.form.cursos,
          enviar: enviar ? 1 : 0,
        }
        await axios.post('/api/cv/cursos', payload)

        if (enviar) {
          this.mostrarMensaje(
            'ok',
            'Tu CV fue enviado para revisión. Serás redirigido a la pantalla principal.'
          )

          setTimeout(() => {
            window.location.href = './login'
          }, 2500)
        } else {
          this.mostrarMensaje('ok', 'Cursos guardados como borrador.')
        }
      } catch (error) {
        const msg =
          error?.response?.data?.message ||
          'No se pudieron guardar los cursos.'
        this.mostrarMensaje('error', msg)
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
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

/* Header */
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

/* Stepper */
.cv-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 8px;
}

.cv-stepper-left {
  flex: 1;
}

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

.cv-stepper-dots {
  display: flex;
  gap: 4px;
}

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

.cv-dot.is-active {
  border-color: #006341;
  color: #006341;
  font-weight: 600;
}

.cv-dot.is-done {
  border-color: #006341;
  background: #006341;
  color: #ffffff;
}

/* Contenido */
.cv-content {
  margin-top: 4px;
}

.cv-section-title {
  font-size: 1.05rem;
  margin-bottom: 4px;
  color: #111827;
}

.cv-section-subtitle {
  font-size: 0.9rem;
  color: #6b7280;
  margin-bottom: 12px;
}

/* Listas de ayuda */
.cv-helper-list {
  margin-top: 12px;
  margin-bottom: 0;
  padding-left: 18px;
  font-size: 0.85rem;
  color: #4b5563;
}

.cv-helper-list li + li {
  margin-top: 4px;
}

/* Bloques repetibles */
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

.cv-block-title {
  font-size: 0.95rem;
  margin: 0;
}

/* Campos de formulario (más aire y bordes suaves) */
.cv-card .form-label {
  font-size: 0.86rem;
  margin-bottom: 4px;
  color: #374151;
}

.cv-card .form-control,
.cv-card .form-select {
  font-size: 0.9rem;
  padding: 0.44rem 0.75rem;
  border-radius: 8px;
  border-color: #d1d5db;
}

.cv-card .form-control:focus,
.cv-card .form-select:focus {
  border-color: #006341;
  box-shadow: 0 0 0 1px rgba(0, 99, 65, 0.15);
}

/* Acciones */
.cv-actions {
  margin-top: 20px;
}

.cv-actions-two,
.cv-actions-three {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 20px;
}

.cv-actions-three .btn {
  flex: 1 1 auto;
}

/* Botones con colores institucionales */
.cv-card .btn-primary {
  background: #006341;
  border-color: #006341;
}

.cv-card .btn-primary:hover {
  background: #004b2e;
  border-color: #004b2e;
}

.cv-card .btn-outline-primary {
  color: #006341;
  border-color: #006341;
}

.cv-card .btn-outline-primary:hover {
  background: #006341;
  color: #ffffff;
  border-color: #006341;
}

.cv-card .btn-success {
  background: #0b8450;
  border-color: #0b8450;
}

.cv-card .btn-success:hover {
  background: #08663e;
  border-color: #08663e;
}

/* Links dentro del card (evitar azul) */
.cv-card a,
.cv-card .btn-link {
  color: #006341;
  text-decoration: none;
}

.cv-card a:hover,
.cv-card .btn-link:hover {
  color: #004b2e;
  text-decoration: underline;
}

/* Link para agregar items */
.cv-link-add {
  font-size: 0.88rem;
  font-weight: 500;
}

/* Link eliminar, respetando rojo pero sin azul */
.cv-link-remove {
  text-decoration: none;
}

/* Responsive */
@media (max-width: 576px) {
  .cv-card {
    padding: 18px 16px 22px;
  }
  .cv-stepper {
    flex-direction: column;
    align-items: flex-start;
  }
  .cv-stepper-dots {
    margin-left: 2px;
  }
}
</style>
