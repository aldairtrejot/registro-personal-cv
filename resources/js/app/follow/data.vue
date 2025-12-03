<template>
    <div class="card shadow-lg">
        <div class="card-body">
            <h3>Mi información</h3>
            <br>

            <div class="datagrid">
                <div class="datagrid-item">
                    <div class="datagrid-title">Nombre (s)</div>
                    <div class="datagrid-content">{{ nombre }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">Apellidos</div>
                    <div class="datagrid-content">{{ apellido }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">RFC</div>
                    <div class="datagrid-content">{{ rfc }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">CURP</div>
                    <div class="datagrid-content">{{ curp }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">Zona económica</div>
                    <div class="datagrid-content">{{ zona_economica }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">Rama</div>
                    <div class="datagrid-content">{{ cat_rama }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">Puesto</div>
                    <div class="datagrid-content">{{ ant_puesto }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">CLUES</div>
                    <div class="datagrid-content">{{ clave_clues }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">Entidad</div>
                    <div class="datagrid-content">{{ entidad }}</div>
                </div>
                <div class="datagrid-item">
                    <div class="datagrid-title">Puesto a postularse</div>
                    <div class="datagrid-content">{{ max_puesto_ob }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@axios'

const nombre = ref('')
const apellido = ref('')
const rfc = ref('')
const curp = ref('')
const zona_economica = ref('')
const cat_rama = ref('')
const ant_puesto = ref('')
const clave_clues = ref('')
const entidad = ref('')
const max_puesto_ob = ref('')



onMounted(async () => {

    try {
        const data = await axios.post('/follow/data')
        const item = data.data.data

        nombre.value = item.nombre
        apellido.value = item.apellido
        rfc.value = item.rfc
        curp.value = item.curp
        zona_economica.value = item.zona_economica
        cat_rama.value = item.cat_rama
        ant_puesto.value = item.ant_puesto
        clave_clues.value = item.clave_clues
        entidad.value = item.entidad
        max_puesto_ob.value = item.max_puesto_ob

    } catch (error) {
        notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
    } finally {
    }

})


</script>