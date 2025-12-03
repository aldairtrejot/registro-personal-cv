<template>
    <div>
        <pageAuth />

        <form id="data_form" enctype="multipart/form-data">

            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="text-center">
                    <h4 class="alert-heading">Vamos a actualizar tu contraseña</h4>
                    <div class="alert-description">Para mantener tu cuenta segura, te pedimos que actualices tu
                        contraseña.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="text" name="password" id="password" v-model="password" class="form-control"
                    placeholder="Tu contraseña" autocomplete="off" />
                <div id="error-password" class="text-danger text-error"
                    style="margin-top: 5px;color: #7a1b32 !important;"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirmar contraseña</label>
                <input type="text" name="newPassword" id="newPassword" v-model="newPassword" class="form-control"
                    placeholder="Tu contraseña" autocomplete="off" />
                <div id="error-newPassword" class="text-danger text-error"
                    style="margin-top: 5px;color: #7a1b32 !important;"></div>
            </div>

            <div class="form-footer">
                <button type="button" @click="sendData" class="btn btn-primary w-100" style="background: #7a1b32">
                    Continuar
                </button>
            </div>
        </form>

    </div>
</template>

<script setup>
import pageAuth from '@helpers/page/page-auth.vue'
import { clearErrors } from '@components/clearErrors.js';
import { handleErrors } from '@components/handleErrors.js';
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { notyf } from '@components/notyf.js';
import { ref } from 'vue'
import { BASE_URL } from '@/components/url.js';
import axios from '@axios'

const newPassword = ref('')
const password = ref('')


async function sendData() {
    try {
        showSpinner();
        clearErrors();

        const form = document.querySelector('#data_form');
        const formData = new FormData(form);

        const response = await axios.post('/credential/updateOnlyPassword', formData);

        if (!response.data.status) {
            notyf.error(response.data.message);
        }

        if (response.data.status) {
            window.location.href = `${BASE_URL}/dashboard`;
        }


    } catch (error) {
        clearErrors();

        if (error.response?.data?.errors) {
            handleErrors(error.response.data.errors);
        }


    } finally {
        hideSpinner();
    }
}

</script>
