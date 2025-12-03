<template>
  <div class="card shadow-lg">
    <div class="card-body">
      <div>
        <h4>Completa los campos para guardar la entidad.</h4>
      </div>

      <form role="form" id="data_form" enctype="multipart/form-data">
        <!-- el id lo inyectas desde Blade; en creación viene vacío -->
        <input type="hidden" id="id" :value="id" />

        <div class="row">
          <inputField label="Abrev." id="abrev" v-model="abrev" />
          <inputField label="Descripción" id="descripcion" v-model="descripcion" />
          <inputField label="Clave entidad" id="clave_entidad" v-model="clave_entidad" />
          <inputCheckbox v-model="estatus" :label="'Estatus'" :id="'estatus'" />
        </div>

        <footerButton :cancelUrl="`${BASE_URL}/role/entity`" :onSave="send_data_form" />
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

// state
const id = ref('')
const abrev = ref('')
const descripcion = ref('')
const clave_entidad = ref('')
const estatus = ref(false)

const normalizeStatus = v => v === true || v === 1 || v === '1'
const collapse = s => (s ?? '').toString().trim().replace(/\s+/g, ' ')

// cargar detalle si es edición
onMounted(async () => {
  try {
    showSpinner()
    const key = document.getElementById('id')?.value || ''
    id.value = key

    if (!key) return // creación: no hay nada que cargar

    const { data } = await axios.post('/role/entity/main', { id: key })
    if (!data?.status) {
      notyf.error(data?.message || 'No se pudo obtener la información.')
      return
    }
    const item = data.result || {}
    abrev.value = item.abrev ?? ''
    descripcion.value = item.descripcion ?? ''
    clave_entidad.value = item.clave_entidad ?? ''
    estatus.value = normalizeStatus(item.estatus)
  } catch (error) {
    notyf.error('No se pudo obtener la información.')
  } finally {
    hideSpinner()
  }
})

async function send_data_form() {
  try {
    // construir payload; SOLO incluir id si es edición
    const key = (id.value || '').toString().trim()
    const payload = {
      ...(key ? { id: key } : {}),
      abrev: collapse(abrev.value).toUpperCase(),
      descripcion: collapse(descripcion.value).toUpperCase(),
      clave_entidad: collapse(clave_entidad.value).toUpperCase(),
      estatus: estatus.value ? '1' : '0'
    }

    showSpinner()
    clearErrors()

    // usar FormData para que tu handleErrors pinte bien por campo
    const formData = new FormData()
    Object.entries(payload).forEach(([k, v]) => formData.set(k, v))

    const response = await axios.post('/role/entity/save', formData)

    if (!response.data.status) {
      clearErrors()
      if (response.data.errors) handleErrors(response.data.errors)
      notyf.error(response.data.message || 'No se pudo guardar.')
    } else {
      sessionStorage.setItem('notify_message', response.data.message || 'Guardado correctamente.')
      window.location.href = `${BASE_URL}/role/entity`
    }
  } catch (error) {
    clearErrors()
    if (error.response?.data?.errors) {
      handleErrors(error.response.data.errors)
    } else {
      notyf.error('Ocurrió un error inesperado.')
    }
  } finally {
    hideSpinner()
  }
}
</script>



