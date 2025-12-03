<template>
    <div class="card shadow-lg">
        <div class="card-body">
            <div>
                <h4>Completa los campos para guardar la información.</h4>
            </div>

            <form role="form" id="data_form" enctype="multipart/form-data">
                <div class="row">
                    <inputField label="Nombre" id="name" v-model="name" :uppercase="true" :required="true" />
                    <inputField label="Correo electrónico" id="email" v-model="email" autocomplete="username"
                        :required="true" />
                </div>

                <div class="row">
                    <inputField label="Contraseña" id="password" v-model="password" autocomplete="password"
                        :disabled="isPasswordDisabled" :required="true" />

                    <inputField type="date" label="Fecha de bloqueo" id="fecha_bloqueo" v-model="fecha_bloqueo"
                        :required="true" />

                </div>

                <div class="row">
                    <inputSelect v-model="listSelectRole" :options="listOptionsRole" name="role" id="role" label="Roles"
                        :multiple="true" :required="true" />
                </div>
                <br>

                <div class="row">
                    <inputSelect v-model="listSelectArea" :options="listOptionsArea" id="id_cat_zona" label="Zona"
                        :multiple="false" grid="col-md-6 col-sm-12" @onChange="handleEntityChange" :required="true" />

                    <inputSelect v-model="listSelectEntity" :options="listOptionsEntity" id="id_cat_entidad"
                        label="Entidad de pago" :multiple="false" grid="col-md-6 col-sm-12" :required="true" />
                </div>
                <br>

                <div class="row">
                    <inputSelect v-model="listSelectDepartment" :options="listOptionsDepartment" id="id_cat_rama"
                        label="Rama" :multiple="false" grid="col-md-6 col-sm-12" :required="true" />

                    <inputCheckbox v-model="estatus" :label="'Estatus'" :id="'estatus'" />
                </div>
                <br>

                <footerButton :cancelUrl="`${BASE_URL}/user`" :onSave="send_data_form" />

            </form>
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
import inputField from '@helpers/form/input-field.vue';
import inputSelect from '@helpers/form/input-select.vue';
import { showSpinner, hideSpinner } from '@components/spinner.js'
import inputCheckbox from '@helpers/form/input-checkbox.vue';

const name = ref('')
const email = ref('')
const password = ref('')
const fecha_bloqueo = ref('')
const estatus = ref(false)
const isPasswordDisabled = ref(false);
const listSelectRole = ref([])
const listOptionsRole = ref([])
const listOptionsEntity = ref([])
const listSelectEntity = ref([])
const listOptionsArea = ref([])
const listSelectArea = ref([])
const listOptionsDepartment = ref([])
const listSelectDepartment = ref([])

onMounted(async () => {

    try {
        showSpinner();
        const key = document.getElementById('id').value

        if (key) {
            isPasswordDisabled.value = true;
        }

        const request = await axios.post('/user/main', { id: key })
        const item = request.data.result.data
        const select = request.data.result

        name.value = item.name
        email.value = item.email
        password.value = item.password
        fecha_bloqueo.value = item.fecha_bloqueo
        estatus.value = item.estatus === true
        listOptionsRole.value = select.listOptionsRole ?? []
        listSelectRole.value = select.listSelectRole ?? []
        listOptionsEntity.value = select.listOptionsEntity ?? []
        listSelectEntity.value = (select.listSelectEntity ?? [])[0] ?? null
        listOptionsDepartment.value = select.listOptionsDepartment ?? []
        listSelectDepartment.value = (select.listSelectDepartment ?? [])[0] ?? null
        listOptionsArea.value = select.listOptionsArea ?? []
        listSelectArea.value = (select.listSelectArea ?? [])[0] ?? null

    } catch (error) {
        notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
    } finally {
        hideSpinner();
    }

})


async function send_data_form() {
    try {
        const key = document.getElementById('id').value
        const form = document.querySelector('#data_form'); // Select the login form
        const formData = new FormData(form); // Create a FormData object with the form data

        formData.set('estatus', estatus.value ? '1' : '0');

        (Array.isArray(listSelectRole.value) ? listSelectRole.value : []).forEach((r, index) => {
            formData.append(`role[${index}]`, r.id); // IDs de los roles seleccionados
        });

        formData.append('id_cat_entidad', listSelectEntity.value?.id ?? '');
        formData.append('id_cat_rama', listSelectDepartment.value?.id ?? '');
        formData.append('id_cat_zona', listSelectArea.value?.id ?? '');

        showSpinner(); // Start the loader to indicate processing
        clearErrors(); // Clear any previous errors

        // Send a POST request to the backend with the form data
        formData.append('id', key);
        const response = await axios.post('/user/save', formData);

        if (!response.data.status) {
            clearErrors(); // Clear errors if there is an issue
            notyf.error(response.data.message); // Show the error message using the notification system
        }

        if (response.data.status) {
            sessionStorage.setItem('nofify_message', response.data.message);
            window.location.href = `${BASE_URL}/user`;
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

async function handleEntityChange(pk) {
    try {
        showSpinner();

        const resut = await axios.post('/user/collection/entity', { id: pk })

        if (!resut.data.status) {
            notyf.error(resut.data.message);
        }

        const select = resut.data

        listOptionsEntity.value = select.listOptionsEntity ?? []
        listSelectEntity.value = (select.listSelectEntity ?? [])[0] ?? null

    } catch (error) {
        notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
    } finally {
        hideSpinner();
    }
}
</script>
