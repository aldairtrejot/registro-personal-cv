<template>
    <div>
        <form id="data_form" enctype="multipart/form-data">
            <!-- Correo electrónico -->
            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" v-model="email" class="form-control"
                    placeholder="usuario@email.com" autocomplete="off" />
                <div id="error-email" class="text-error text-danger" style="margin-top: 5px;color: #7a1b32 !important;">
                </div>
            </div>

            <!-- Password con icono FontAwesome -->
            <div class="mb-2">
                <label class="form-label">
                    Contraseña
                    <span class="form-label-description">
                        <a :href="`${BASE_URL}/recover`">¿Has olvidado tu contraseña?</a>
                    </span>
                </label>

                <div class="input-group">
                    <input :type="showPassword ? 'text' : 'password'" name="password" id="password" v-model="password"
                        class="form-control" placeholder="Tu contraseña" autocomplete="off" />
                    <span class="input-group-text" @click="togglePassword"
                        style="cursor: pointer; background-color: white; border-left: 0; border-color: #ced4da; font-size: 1rem;">
                        <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-lock'" style="color: gray;"></i>
                    </span>
                </div>

                <div id="error-password" class="text-danger text-error"
                    style="margin-top: 5px;color: #7a1b32 !important;"></div>
            </div>

            <!-- Captcha -->
            <div class="mb-3">
                <img :src="captchaUrl" alt="captcha" id="captcha-img" class="img-fluid d-block mx-auto"
                    style="width: 300px; height: auto;" />
            </div>

            <div class="mb-3">
                <label class="form-label">Captcha</label>
                <input type="text" name="captcha" id="captcha" v-model="captcha" class="form-control"
                    placeholder="Captcha" autocomplete="off" />
                <div id="error-captcha" class="text-error text-danger"
                    style="margin-top: 5px;color: #7a1b32 !important;"></div>
            </div>

            <!-- Botón de enviar -->
            <div class="form-footer">
                <button type="button" @click="sendData" class="btn btn-primary w-100" style="background: #7a1b32">
                    Continuar
                </button>
            </div>
        </form>

        <!-- Links -->
        <div class="text-center text-secondary mt-3">
            ¿No tienes cuenta? <a :href="`${BASE_URL}/create`" tabindex="-1">Crea una ahora</a>
        </div>

        <div class="text-center text-secondary mt-3">
            <a :href="`${BASE_URL}/information`" tabindex="-1">¿Problemas al iniciar sesión?</a>
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
      refreshCaptcha(); // recargar captcha si falla
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
