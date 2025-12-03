<template>
    <div>

        <form id="data_form" enctype="multipart/form-data">

            <div class="text-center">
                <span class="badge badge-outline text-default">Registro de nuevos usuarios</span>
            </div>
            <br>

            <div class="alert alert-minor alert-info alert-dismissible" role="alert">
                <div class="alert-icon">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/info-circle -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon alert-icon icon-2">
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                        <path d="M12 9h.01"></path>
                        <path d="M11 12h1v4h1"></path>
                    </svg>
                </div>
                <div>
                    <div class="alert-description" style="text-align: justify;">
                        Por favor, usa un correo al que tenga acceso para recibir los datos de inicio de sesión.
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" v-model="email" class="form-control"
                    placeholder="usuario@email.com" autocomplete="off" />
                <div id="error-email" class="text-error text-danger" style="margin-top: 5px;color: #7a1b32 !important;">
                </div>

                <br>
                <label class="form-label">Confirmar correo electrónico</label>
                <input type="email" name="confirm_email" id="confirm_email" v-model="confirm_email" class="form-control"
                    placeholder="usuario@email.com" autocomplete="off" />
                <div id="error-confirm_email" class="text-error text-danger"
                    style="margin-top: 5px;color: #7a1b32 !important;">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">RFC</label>
                <input type="rfc" name="rfc" id="rfc" v-model="rfc" class="form-control" placeholder="RFC"
                    autocomplete="off" />
                <div id="error-rfc" class="text-error text-danger" style="margin-top: 5px;color: #7a1b32 !important;">
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


        <modalTemplate modal-id="modal_cofirm_create" :on-confirm="sendData"
            dialog-class="modal-dialog modal-dialog-centered modal-sm">
            <div class="text-center">
                <i class="ti ti-alert-triangle text-warning" style="font-size: 48px;"></i>
                <h3 class="mt-3">¿Está seguro que desea continuar?</h3>
                <p class="text-muted">
                    Esta acción <strong>no se puede deshacer</strong>. Por favor confirme para proceder.
                </p>
            </div>
        </modalTemplate>

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
import modalTemplate from '@helpers/modal/modal-template.vue';

const email = ref('')
const rfc = ref('')
const confirm_email = ref('')

async function sendData() {
    try {
        $('#modal_cofirm_create').modal('hide');
        showSpinner();
        clearErrors();

        const form = document.querySelector('#data_form');
        const formData = new FormData(form);

        const response = await axios.post('/create/user', formData);

        if (!response.data.status) {
            notyf.error(response.data.message);
        }

        if (response.data.status) {
            notyf.success(response.data.message);
            email.value = ''
            rfc.value = ''
            confirm_email.value = ''
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

function openModal() {
    $('#modal_cofirm_create').modal('show');
}
</script>
