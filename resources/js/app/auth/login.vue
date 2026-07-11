<template>
  <div class="login-shell">
    <div class="card login-card">
      <div class="login-card__header">
        <div class="d-flex align-items-center gap-3">
          <div class="login-card__mark">
            <img
              :src="`${BASE_URL}/assets/images/imss_logo.png`"
              alt="IMSS"
              class="login-card__logo"
              onerror="this.style.display='none'"
            />
          </div>

          <div>
            <div class="login-card__title">Proceso Curricular</div>
            <div class="login-card__subtitle">Inicio de sesión</div>
          </div>
        </div>
      </div>

      <div class="card-body login-card__body">
        <form id="data_form" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input
              type="email"
              name="email"
              id="email"
              v-model="email"
              class="form-control"
              placeholder="usuario@email.com"
              autocomplete="off"
            />
            <div
              id="error-email"
              class="text-error login-error"
            ></div>
          </div>

          <div class="mb-2">
            <label class="form-label">
              <span>Contraseña</span>
            </label>

            <div class="input-group">
              <input
                :type="showPassword ? 'text' : 'password'"
                name="password"
                id="password"
                v-model="password"
                class="form-control"
                placeholder="Tu contraseña"
                autocomplete="off"
              />
              <button
                class="btn btn-outline-secondary"
                type="button"
                @click="togglePassword"
                aria-label="Mostrar u ocultar contraseña"
              >
                <i :class="showPassword ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
              </button>
            </div>

            <div
              id="error-password"
              class="text-error login-error"
            ></div>
          </div>

          <div class="mt-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label m-0">Captcha</label>

              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="refreshCaptcha"
                title="Actualizar captcha"
              >
                <i class="ti ti-refresh"></i>
              </button>
            </div>

            <div class="login-captcha">
              <img
                :src="captchaUrl"
                alt="captcha"
                id="captcha-img"
                class="img-fluid"
              />
            </div>

            <div class="mt-2">
              <input
                type="text"
                name="captcha"
                id="captcha"
                v-model="captcha"
                class="form-control"
                placeholder="Escribe el captcha"
                autocomplete="off"
              />
              <div
                id="error-captcha"
                class="text-error login-error"
              ></div>
            </div>
          </div>

          <div class="mt-4">
            <button
              type="button"
              @click="sendData"
              class="btn btn-primary w-100 login-submit"
            >
              <i class="ti ti-login-2 me-1" aria-hidden="true"></i>
              Continuar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { clearErrors } from '@components/clearErrors.js';
import { handleErrors } from '@components/handleErrors.js';
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { notyf } from '@components/notyf.js';
import axios from '@axios'
import { BASE_URL } from '@/components/url.js';

// Campos del formulario
const email = ref('')
const password = ref('')
const captcha = ref('')

// Mostrar/ocultar contraseña
const showPassword = ref(false)
function togglePassword() {
  showPassword.value = !showPassword.value
}

// Captcha
const captchaUrl = ref(`${BASE_URL}/captcha/_white?` + Date.now())
function refreshCaptcha() {
  captchaUrl.value = `${BASE_URL}/captcha/_white?` + Date.now()
}

// Inicialización
onMounted(() => {
  try {
    showSpinner();
    refreshCaptcha()
  } finally {
    hideSpinner();
  }
})

// Enviar datos
async function sendData() {
  try {
    showSpinner();
    clearErrors();

    const form = document.querySelector('#data_form');
    const formData = new FormData(form);

    const response = await axios.post('auth/authentication', formData);

    if (!response.data.status) {
      notyf.error(response.data.message);
      refreshCaptcha();
    }

    if (response.data.status) {
      window.location.href = `${BASE_URL}/revisor/empleados`;
    }

  } catch (error) {
    clearErrors();
    refreshCaptcha();

    if (error.response?.data?.message) {
      notyf.error(error.response.data.message);
    }

    if (error.response?.data?.errors) {
      handleErrors(error.response.data.errors);
    }

  } finally {
    hideSpinner();
  }
}
</script>

<style scoped>
.login-shell {
  min-height: 100vh;
  padding: 64px 16px 28px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
}

.login-card {
  width: 100%;
  max-width: 430px;
  border: 0;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 14px 34px rgba(16, 49, 43, 0.12);
}

.login-card__header {
  position: relative;
  padding: 20px;
  background: #691c32;
}

.login-card__header::after {
  content: "";
  position: absolute;
  inset: auto 0 0;
  height: 4px;
  background: linear-gradient(90deg, #9f2241, #006341, #bc955c);
}

.login-card__mark {
  width: 46px;
  height: 46px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-card__logo {
  max-width: 32px;
  height: auto;
}

.login-card__title {
  color: #ffffff;
  font-weight: 800;
  line-height: 1.1;
}

.login-card__subtitle {
  color: rgba(255, 255, 255, 0.84);
  font-size: 0.9rem;
}

.login-card__body {
  padding: 20px;
  background: #ffffff;
}

.login-card :deep(.form-control),
.login-card :deep(.btn) {
  border-radius: 8px;
}

.login-card :deep(.form-control:focus) {
  border-color: rgba(0, 99, 65, 0.55);
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.12);
}

.login-card :deep(.input-group .btn) {
  border-left: 0;
  color: #5f6b72;
}

.login-captcha {
  min-height: 64px;
  padding: 10px;
  background: #f8faf9;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-captcha img {
  max-width: 280px;
  height: auto;
}

.login-error {
  margin-top: 6px;
  color: #9f2241;
  font-size: 0.84rem;
  font-weight: 600;
}

.login-submit {
  background: #691c32 !important;
  border-color: #691c32 !important;
  border-radius: 8px;
  padding: 10px 14px;
  font-weight: 800;
}

.login-submit:hover,
.login-submit:focus {
  background: #7a1b32 !important;
  border-color: #7a1b32 !important;
}
</style>
