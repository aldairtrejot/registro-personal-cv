<template>
    <modalTemplate modal-id="modal_logout" :on-confirm="send_form"
        dialog-class="modal-dialog modal-dialog-centered modal-sm">
        <h3>¿Está seguro que desea cerrar la sesión?</h3>
    </modalTemplate>
</template>

<script setup>
import { notyf } from '@components/notyf.js';
import { BASE_URL } from '@/components/url.js';
import axios from '@axios'
import modalTemplate from '@helpers/modal/modal-template.vue';
import { showSpinner, hideSpinner } from '@components/spinner.js'

async function send_form() {
    try {
        $('#modal_logout').modal('hide');
        showSpinner();

        const result = await axios.post('/logout')

        if (result.data.status) {
            window.location.href = `${BASE_URL}/login`;
        } else {
            notyf.error(result.data.message)
        }

    } catch (error) {
        notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
    } finally {
        hideSpinner();
    }
}

</script>