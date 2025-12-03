<template>
    <div>

        <form id="data_form" enctype="multipart/form-data">

            <div class="text-center">
                <span class="badge badge-outline text-default">Recuperar mi contraseña</span>
            </div>
            <br>

            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" v-model="email" class="form-control"
                    placeholder="usuario@email.com" autocomplete="off" />
                <div id="error-email" class="text-error text-danger" style="margin-top: 5px;color: #7a1b32 !important;">
                </div>
            </div>

            <div class="form-footer">
                <button type="button" @click="sendData" class="btn btn-primary w-100" style="background: #7a1b32">
                    Continuar
                </button>
            </div>
        </form>

        <div class="text-center text-secondary mt-3">
            <a :href="`${BASE_URL}/login`" tabindex="-1">Regresar</a>
        </div>

    </div>
</template>

<script setup>
import { clearErrors } from '@components/clearErrors.js';
import { handleErrors } from '@components/handleErrors.js';
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { notyf } from '@components/notyf.js';
import { ref } from 'vue'
import { BASE_URL } from '@/components/url.js';
import axios from '@axios'

const email = ref('')
const captchaUrl = ref(`${BASE_URL}/captcha/_white?` + Date.now())

function refreshCaptcha() {
    captchaUrl.value = `${BASE_URL}/captcha/_white?` + Date.now()
}

async function sendData() {
    try {
        showSpinner();
        clearErrors();

        const form = document.querySelector('#data_form');
        const formData = new FormData(form);

        const response = await axios.post('/recover/setPassword', formData);

        if (!response.data.status) {
            notyf.error(response.data.message);
            refreshCaptcha(); // recargar si falla
        }

        if (response.data.status) {
            email.value = ''
            notyf.success(response.data.message);
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
