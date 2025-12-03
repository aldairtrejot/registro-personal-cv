<template>
    <div class="card shadow-lg">
        <div class="card-body">
            <div>
                <h4>Completa los campos para guardar la información.</h4>
            </div>

            <div class="alert alert-warning alert-dismissible" role="alert">
                <div class="alert-icon">
                    <i style="font-size: 1.2rem;" class="ti ti-info-triangle"></i>
                </div>
                <div>
                <h4 class="alert-heading"><b>Selecciona el puesto al que deseas postularte</b></h4>
                <div class="alert-description">
                Para continuar con el <b>proceso de profesionalización</b>, es necesario
                seleccionar el <b>puesto</b> al que deseas postularte.
                Ten en cuenta que, dependiendo del <b>puesto elegido</b>, los <b>documentos requeridos</b>
                para la actualización variarán.
                <b>Este proceso solo se puede realizar una vez</b>, por lo que es importante asegurarte de tu
                    elección antes de continuar.  
                    <br><br>
                <b>La fecha de inicio deberá corresponder al puesto que actualmente ocupa conforme a su contrato vigente.</b>
                </div>
                </div>
                </div>
            <form role="form" id="data_form" enctype="multipart/form-data">
                <div class="row">
                    <inputSelect v-model="listSelectPosition" :options="listOptionsPosition" id="id_cat_sig_puesto"
                        label="Siguiente puesto" :multiple="false" grid="col-sm-12" :required="true" />
                </div>
                <br>
                <div class="row">
                    <inputField type="date" label="Fecha de inicio" id="fecha_inicio" v-model="fecha_inicio"
                        :required="true" grid="col-sm-12" />
                </div>
                <br>

                <footerButton :cancelUrl="`${BASE_URL}/dashboard`" :onSave="openModal" />

            </form>

            <modalTemplate modal-id="modal_add_employee" :on-confirm="send_data_form"
                dialog-class="modal-dialog modal-dialog-centered modal-sm">
                <div class="text-center">
                    <i class="ti ti-alert-triangle text-warning" style="font-size: 48px;"></i>
                    <h3 class="mt-3">Aviso de confidencialidad</h3>
                    <p class="text-muted">
                        <strong>Acepto las bases de la Convocatoria del Programa de Promoción por
                            Profesionalización</strong> de
                        Enfermería, Trabajo Social y Terapia Física y Rehabilitación 2025.<br><br>

                        <strong>Sujeto a disponibilidad de plazas</strong> ante la Secretaría de Hacienda y Crédito
                        Público.<br><br>

                        Por favor, confirme para proceder.
                    </p>
                </div>
            </modalTemplate>

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { notyf } from '@components/notyf.js';
import { BASE_URL } from '@/components/url.js';
import axios from '@axios'
import footerButton from '@helpers/form/footer-button.vue';
import { clearErrors } from '@components/clearErrors.js'; // Importing function to clear previous errors
import { handleErrors } from '@components/handleErrors.js'; // Importing function to handle and display validation errors
import inputSelect from '@helpers/form/input-select.vue';
import { showSpinner, hideSpinner } from '@components/spinner.js'
import modalTemplate from '@helpers/modal/modal-template.vue';
import inputField from '@helpers/form/input-field.vue';

const listOptionsPosition = ref([])
const listSelectPosition = ref([])
const fecha_inicio = ref('')

onMounted(async () => {

    try {
        showSpinner();

        const request = await axios.post('/follow/max/position')
        const select = request.data.result

        listOptionsPosition.value = select.listOptionsPosition ?? []
        listSelectPosition.value = (select.listSelectPosition ?? [])[0] ?? null

    } catch (error) {
        notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
    } finally {
        hideSpinner();
    }

})


async function send_data_form() {
    try {
        $('#modal_add_employee').modal('hide');

        const form = document.querySelector('#data_form'); // Select the login form
        const formData = new FormData(form); // Create a FormData object with the form data

        formData.append('id_cat_sig_puesto', listSelectPosition.value?.id ?? '');

        showSpinner(); // Start the loader to indicate processing
        clearErrors(); // Clear any previous errors

        // Send a POST request to the backend with the form data
        const response = await axios.post('/follow/check/position', formData);

        if (!response.data.status) {
            clearErrors(); // Clear errors if there is an issue
            notyf.error(response.data.message); // Show the error message using the notification system
        }

        if (response.data.status) {
            sessionStorage.setItem('nofify_message', response.data.message);
            window.location.href = `${BASE_URL}/follow`;
        }

    } catch (error) {
        // Handle any errors that occur during the request
        clearErrors(); // Clear previous errors
        if (error.response && error.response.data.errors) {
            handleErrors(error.response.data.errors); // Display validation errors using the handleErrors function
        }

    } finally {
        hideSpinner(); // Stop the loader after the request is finished
    }
}

function openModal() {
    $('#modal_add_employee').modal('show');
}
</script>
