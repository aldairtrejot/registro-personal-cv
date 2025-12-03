<template>
  <div class="registro-wizard">
    <!-- Encabezado dinámico -->
    <div class="text-center mb-3">
      <h2 class="h2 mb-1">{{ tituloPaso }}</h2>
      <p class="text-muted mb-0">{{ subtituloPaso }}</p>
    </div>

    <div v-if="mensajeGlobal" class="alert alert-success" role="alert">
      {{ mensajeGlobal }}
    </div>

    <!-- PASO 1: Acceso al sistema -->
    <form v-if="step === 1" @submit.prevent="handleAcceso">
      <div class="mb-3">
        <label class="form-label">CURP</label>
        <input
          type="text"
          v-model="acceso.curp"
          maxlength="18"
          class="form-control"
          placeholder="Ingresa tu CURP"
          autocomplete="off"
        />
      </div>
      <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input
          type="email"
          v-model="acceso.email"
          class="form-control"
          placeholder="ejemplo@correo.com"
          autocomplete="off"
        />
      </div>

      <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary">
          Enviar token
        </button>
      </div>
    </form>

    <!-- PASO 2: Validar token -->
    <form v-if="step === 2" @submit.prevent="handleValidarToken">
      <div class="card card-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <span class="text-muted fw-semibold">CURP:</span>
            <span class="fw-semibold">{{ acceso.curp || '---' }}</span>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span class="text-muted fw-semibold">Correo:</span>
            <span class="fw-semibold">{{ acceso.email || '---' }}</span>
          </div>
        </div>
      </div>

      <div class="mb-2">
        <label class="form-label">Token de verificación</label>
        <input
          type="text"
          v-model="token.code"
          class="form-control"
          placeholder="Código enviado a tu correo"
          autocomplete="off"
        />
        <small class="form-hint">Código enviado a tu correo electrónico.</small>
      </div>

      <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-secondary" @click="prevStep">
          ← Regresar
        </button>
        <button type="submit" class="btn btn-primary">
          Ingresar
        </button>
      </div>
    </form>

    <!-- PASO 3: Datos personales -->
    <form v-if="step === 3" @submit.prevent="nextStep">
      <div class="mb-3">
        <label class="form-label">Nombre(s)</label>
        <input type="text" v-model="datosPersonales.nombres" class="form-control" />
      </div>
      <div class="mb-3">
        <label class="form-label">Primer apellido</label>
        <input type="text" v-model="datosPersonales.primerApellido" class="form-control" />
      </div>
      <div class="mb-3">
        <label class="form-label">Segundo apellido</label>
        <input type="text" v-model="datosPersonales.segundoApellido" class="form-control" />
      </div>
      <div class="mb-3">
        <label class="form-label">Puesto actual</label>
        <input type="text" v-model="datosPersonales.puestoActual" class="form-control" />
      </div>
      <div class="mb-3">
        <label class="form-label">Fecha de inicio</label>
        <input type="date" v-model="datosPersonales.fechaInicio" class="form-control" />
      </div>
      <div class="mb-4">
        <label class="form-label">Área de adscripción</label>
        <select v-model="datosPersonales.areaAdscripcion" class="form-select">
          <option value="">Selecciona un área</option>
          <option value="Marketing">Marketing</option>
          <option value="Administración">Administración</option>
          <option value="Operaciones">Operaciones</option>
          <option value="Otro">Otro</option>
        </select>
      </div>

      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" @click="prevStep">
          ← Regresar
        </button>
        <button type="submit" class="btn btn-primary">
          Continuar →
        </button>
      </div>
    </form>

    <!-- PASO 4: Experiencia laboral -->
    <form v-if="step === 4" @submit.prevent="nextStep">
      <p class="text-muted mb-1">
        Agrega de 1 a 3 experiencias laborales
      </p>
      <p class="text-muted small mb-3">Registros: {{ experiencias.length }}/3</p>

      <div v-for="(exp, index) in experiencias" :key="exp.id" class="card card-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Experiencia #{{ index + 1 }}</span>
          <button
            v-if="experiencias.length > 1"
            type="button"
            class="btn btn-link text-danger p-0"
            @click="removeExperiencia(index)"
          >
            Eliminar
          </button>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Fecha de inicio</label>
            <input type="date" v-model="exp.fechaInicio" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Fecha de término</label>
            <input type="date" v-model="exp.fechaTermino" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Sector</label>
            <select v-model="exp.sector" class="form-select">
              <option value="">Selecciona sector</option>
              <option value="publico">Público</option>
              <option value="privado">Privado</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Cargo o puesto</label>
            <input type="text" v-model="exp.puesto" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Denominación de la institución o empresa</label>
            <input type="text" v-model="exp.institucion" class="form-control" />
          </div>
          <div class="mb-0">
            <label class="form-label">Campo de experiencia (máx. 100 caracteres)</label>
            <textarea
              v-model="exp.campo"
              maxlength="100"
              rows="2"
              class="form-control"
            ></textarea>
            <small class="form-hint">
              {{ exp.campo.length }}/100 caracteres
            </small>
          </div>
        </div>
      </div>

      <button
        type="button"
        class="btn btn-outline-primary w-100 mb-3"
        :disabled="experiencias.length >= 3"
        @click="addExperiencia"
      >
        + Agregar experiencia
      </button>

      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" @click="prevStep">
          ← Regresar
        </button>
        <button type="submit" class="btn btn-primary">
          Siguiente →
        </button>
      </div>
    </form>

    <!-- PASO 5: Estudios académicos -->
    <form v-if="step === 5" @submit.prevent="nextStep">
      <div class="mb-3">
        <label class="form-label">Institución</label>
        <input
          type="text"
          v-model="estudios.institucion"
          class="form-control"
          placeholder="Nombre de la institución educativa"
        />
      </div>
      <div class="mb-3">
        <label class="form-label">País</label>
        <select v-model="estudios.pais" class="form-select">
          <option value="">Selecciona un país</option>
          <option value="México">México</option>
          <option value="España">España</option>
          <option value="Estados Unidos">Estados Unidos</option>
          <option value="Otro">Otro</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Nivel máximo de estudios</label>
        <select v-model="estudios.nivel" class="form-select">
          <option value="">Selecciona nivel</option>
          <option value="Licenciatura">Licenciatura</option>
          <option value="Maestría">Maestría</option>
          <option value="Doctorado">Doctorado</option>
          <option value="Otro">Otro</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Número de cédula</label>
        <input
          type="text"
          v-model="estudios.cedula"
          class="form-control"
          placeholder="Ej: 12345678"
        />
      </div>
      <div class="mb-3">
        <label class="form-label">Carrera genérica</label>
        <select v-model="estudios.carreraGenerica" class="form-select">
          <option value="">Selecciona carrera genérica</option>
          <option value="Economía y Administración">Economía y Administración</option>
          <option value="Ingeniería">Ingeniería</option>
          <option value="Salud">Salud</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Carrera específica</label>
        <select v-model="estudios.carreraEspecifica" class="form-select">
          <option value="">Selecciona carrera específica</option>
          <option value="Arquitectura">Arquitectura</option>
          <option value="Contaduría">Contaduría</option>
          <option value="Otra">Otra</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="form-label">Área de estudios</label>
        <select v-model="estudios.area" class="form-select">
          <option value="">Selecciona área de estudios</option>
          <option value="Económico-Administrativas">Económico-Administrativas</option>
          <option value="Ingeniería y Tecnología">Ingeniería y Tecnología</option>
        </select>
      </div>

      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" @click="prevStep">
          ← Regresar
        </button>
        <button type="submit" class="btn btn-primary">
          Siguiente →
        </button>
      </div>
    </form>

    <!-- PASO 6: Cursos y capacitaciones -->
    <form v-if="step === 6" @submit.prevent="finalizar">
      <p class="text-muted mb-1">
        Agrega de 1 a 3 cursos o capacitaciones
      </p>
      <p class="text-muted small mb-3">Registros: {{ cursos.length }}/3</p>

      <div v-for="(curso, index) in cursos" :key="curso.id" class="card card-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Curso #{{ index + 1 }}</span>
          <button
            v-if="cursos.length > 1"
            type="button"
            class="btn btn-link text-danger p-0"
            @click="removeCurso(index)"
          >
            Eliminar
          </button>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Período</label>
            <input
              type="text"
              v-model="curso.periodo"
              class="form-control"
              placeholder="Ej: Enero - Marzo 2024"
            />
          </div>
          <div class="mb-3">
            <label class="form-label">Nombre del curso o capacitación</label>
            <input
              type="text"
              v-model="curso.nombre"
              class="form-control"
              placeholder="Ej: Gestión de Proyectos Ágiles"
            />
          </div>
          <div class="mb-0">
            <label class="form-label">Nombre de la institución</label>
            <input
              type="text"
              v-model="curso.institucion"
              class="form-control"
              placeholder="Ej: Universidad Nacional"
            />
          </div>
        </div>
      </div>

      <button
        type="button"
        class="btn btn-outline-primary w-100 mb-3"
        :disabled="cursos.length >= 3"
        @click="addCurso"
      >
        + Agregar curso
      </button>

      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" @click="prevStep">
          ← Regresar
        </button>
        <button type="submit" class="btn btn-primary">
          Finalizar
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  name: 'RegistroWizard',
  data() {
    return {
      step: 1,
      maxStep: 6,
      mensajeGlobal: '',
      acceso: {
        curp: '',
        email: '',
      },
      token: {
        code: '',
      },
      datosPersonales: {
        nombres: '',
        primerApellido: '',
        segundoApellido: '',
        puestoActual: '',
        fechaInicio: '',
        areaAdscripcion: '',
      },
      experiencias: [createExperiencia()],
      estudios: {
        institucion: '',
        pais: '',
        nivel: '',
        cedula: '',
        carreraGenerica: '',
        carreraEspecifica: '',
        area: '',
      },
      cursos: [createCurso()],
    };
  },
  computed: {
    tituloPaso() {
      switch (this.step) {
        case 1:
          return 'Acceso al Sistema';
        case 2:
          return 'Validar Token';
        case 3:
          return 'Datos Personales';
        case 4:
          return 'Experiencia Laboral';
        case 5:
          return 'Estudios Académicos';
        case 6:
          return 'Cursos y Capacitaciones';
        default:
          return 'Registro de CV';
      }
    },
    subtituloPaso() {
      switch (this.step) {
        case 1:
          return 'Ingresa tus datos para continuar';
        case 2:
          return 'Revisa tus datos e ingresa el token';
        case 3:
          return 'Completa tu información personal';
        case 4:
          return 'Agrega tu experiencia laboral';
        case 5:
          return 'Completa tu información académica';
        case 6:
          return 'Agrega tus cursos o capacitaciones';
        default:
          return '';
      }
    },
  },
  methods: {
    nextStep() {
      if (this.step < this.maxStep) {
        this.step++;
        this.mensajeGlobal = '';
      }
    },
    prevStep() {
      if (this.step > 1) {
        this.step--;
        this.mensajeGlobal = '';
      }
    },
    handleAcceso() {
      if (!this.acceso.curp || !this.acceso.email) {
        alert('Por favor captura CURP y correo.');
        return;
      }
      // Demo: solo simulamos envío de token
      alert('Token enviado (simulado).');
      this.nextStep();
    },
    handleValidarToken() {
      if (!this.token.code) {
        alert('Ingresa el token de verificación.');
        return;
      }
      // Demo: simulamos token correcto
      this.nextStep();
    },
    addExperiencia() {
      if (this.experiencias.length >= 3) return;
      this.experiencias.push(createExperiencia());
    },
    removeExperiencia(index) {
      if (this.experiencias.length === 1) return;
      this.experiencias.splice(index, 1);
    },
    addCurso() {
      if (this.cursos.length >= 3) return;
      this.cursos.push(createCurso());
    },
    removeCurso(index) {
      if (this.cursos.length === 1) return;
      this.cursos.splice(index, 1);
    },
    finalizar() {
      this.mensajeGlobal =
        '¡Registro completado exitosamente! Todos los datos han sido guardados (demo).';
      alert('En el futuro aquí se enviará al backend todo el CV.');
    },
  },
};

let uid = 1;
function createExperiencia() {
  return {
    id: uid++,
    fechaInicio: '',
    fechaTermino: '',
    sector: '',
    puesto: '',
    institucion: '',
    campo: '',
  };
}
function createCurso() {
  return {
    id: uid++,
    periodo: '',
    nombre: '',
    institucion: '',
  };
}
</script>

<style scoped>
.registro-wizard {
  max-width: 480px;
  margin: 0 auto;
}
</style>
