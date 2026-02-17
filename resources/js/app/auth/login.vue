<template>
  <div
    class="d-flex align-items-start justify-content-center"
    style="min-height: 100vh; padding: 60px 16px 24px;"
  >
    <div
      class="card shadow-sm border-0"
      style="width: 100%; max-width: 420px; border-radius: 16px; overflow: hidden;"
    >
      <!-- Header compacto -->
      <div style="background:#7a1b32; padding: 18px 20px;">
        <div class="d-flex align-items-center gap-3">
          <div
            class="d-flex align-items-center justify-content-center"
            style="width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.15);"
          >
            <img
              :src="`${BASE_URL}/assets/images/imss_logo.png`"
              alt="IMSS"
              style="max-width: 30px; height: auto;"
              onerror="this.style.display='none'"
            />
          </div>

          <div>
            <div style="color:#fff; font-weight:700; line-height: 1.1;">Proceso Curricular</div>
            <div style="color: rgba(255,255,255,.85); font-size: .9rem;">Inicio de sesión</div>
          </div>
        </div>
      </div>

      <div class="card-body" style="padding: 18px 20px;">
        <form id="data_form" enctype="multipart/form-data">
          <!-- Correo electrónico -->
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
              class="text-error text-danger"
              style="margin-top: 6px; color: #7a1b32 !important;"
            ></div>
          </div>

          <!-- Password -->
          <div class="mb-2">
            <label class="form-label d-flex align-items-center justify-content-between">
              <span>Contraseña</span>
              <a :href="`${BASE_URL}/recover`" style="font-size: .85rem; text-decoration: none;">
                ¿Has olvidado tu contraseña?
              </a>
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
                style="border-left: 0;"
                aria-label="Mostrar u ocultar contraseña"
              >
                <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'" style="color: gray;"></i>
              </button>
            </div>

            <div
              id="error-password"
              class="text-danger text-error"
              style="margin-top: 6px; color: #7a1b32 !important;"
            ></div>
          </div>

          <!-- Captcha -->
          <div class="mt-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label m-0">Captcha</label>

              <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                @click="refreshCaptcha"
                style="border-radius: 10px;"
                title="Actualizar captcha"
              >
                <i class="fa fa-rotate-right"></i>
              </button>
            </div>

            <div
              class="d-flex align-items-center justify-content-center p-2"
              style="background:#fff; border:1px solid #e5e7eb; border-radius: 12px;"
            >
              <img
                :src="captchaUrl"
                alt="captcha"
                id="captcha-img"
                class="img-fluid"
                style="max-width: 280px; height: auto;"
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
                class="text-error text-danger"
                style="margin-top: 6px; color: #7a1b32 !important;"
              ></div>
            </div>
          </div>

          <!-- Botón -->
          <div class="mt-4">
            <button
              type="button"
              @click="sendData"
              class="btn btn-primary w-100"
              style="background: #7a1b32; border-color:#7a1b32; border-radius: 12px; padding: 10px 14px;"
            >
              Continuar
            </button>
          </div>
        </form>

        <!-- Links -->
        <div class="text-center text-secondary mt-3" style="font-size: .92rem;">
          ¿No tienes cuenta? <a :href="`${BASE_URL}/create`" tabindex="-1" style="text-decoration:none;">Crea una ahora</a>
        </div>

        <div class="text-center text-secondary mt-2" style="font-size: .92rem;">
          <a :href="`${BASE_URL}/information`" tabindex="-1" style="text-decoration:none;">¿Problemas al iniciar sesión?</a>
        </div>
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
      window.location.href = `${BASE_URL}/dashboard`;
    }

  } catch (error) {
    clearErrors();
    refreshCaptcha();

    if (error.response?.data?.errors) {
      handleErrors(error.response.data.errors);
    }

  } finally {
    hideSpinner();
  }
}
</script>