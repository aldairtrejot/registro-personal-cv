<template>
    <div class="card shadow-lg">
        <div class="card-body">
            <div>
                <h4>Completa los campos para guardar la zona.</h4>
            </div>
 
            <form role="form" id="data_form" enctype="multipart/form-data">
                <input type="hidden" id="id" :value="id" />
 
                <div class="row">
                    <inputField label="Descripción" id="descripcion" v-model="descripcion" />
                    <inputCheckbox v-model="estatus" :label="'Estatus'" :id="'estatus'" />
                </div>
 
                <footerButton :cancelUrl="`${BASE_URL}/role/zone`" :onSave="send_data_form" />
            </form>
        </div>
    </div>
</template>
 
<script setup>
import { ref, onMounted } from 'vue'
import { notyf } from '@components/notyf.js'
import { BASE_URL } from '@/components/url.js'
import axios from '@axios'
 
import footerButton from '@helpers/form/footer-button.vue'
import inputField from '@helpers/form/input-field.vue'
import inputCheckbox from '@helpers/form/input-checkbox.vue'
 
import { clearErrors } from '@components/clearErrors.js'
import { handleErrors } from '@components/handleErrors.js'
import { showSpinner, hideSpinner } from '@components/spinner.js'
 
// Campos del formulario
const id = ref('')
const descripcion = ref('')
const estatus = ref(false)
 
onMounted(async () => {
    try {
        showSpinner()
        const key = document.getElementById('id')?.value
        id.value = key
 
        if (!key) return // Si no hay ID, es formulario de creación
 
        // Si hay ID, es edición. Aquí deberías tener un endpoint para traer datos si lo haces con Vue
        const response = await axios.post('/role/zone/main', { id: key }) // <- solo si decides hacer todo en Vue
        const item = response.data.result
 
        descripcion.value = item.descripcion
        estatus.value = item.estatus === true
 
    } catch (error) {
        notyf.error('No se pudo obtener la información.')
    } finally {
        hideSpinner()
    }
})
 
async function send_data_form() {
    try {
        const key = id.value
        const form = document.querySelector('#data_form')
        const formData = new FormData(form)
 
        formData.set('id_cat_zona', key)
        formData.set('descripcion', descripcion.value)
        formData.set('estatus', estatus.value ? '1' : '0')
 
        showSpinner()
        clearErrors()
 
        const response = await axios.post('/role/zone/save', formData)
 
        if (!response.data.status) {
            clearErrors()
            notyf.error(response.data.message)
        } else {
            sessionStorage.setItem('nofify_message', response.data.message)
            window.location.href = `${BASE_URL}/role/zone`
        }
 
    } catch (error) {
        clearErrors()
        if (error.response && error.response.data.errors) {
            handleErrors(error.response.data.errors)
        } else {
            notyf.error('Ocurrió un error inesperado.')
        }
    } finally {
        hideSpinner()
    }
}
</script>