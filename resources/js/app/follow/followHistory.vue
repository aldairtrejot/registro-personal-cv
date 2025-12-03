<template>
    <modalTemplate modal-id="modal_follow_history" :on-confirm="follow_history_hide"
        dialog-class="modal-dialog modal-dialog-centered modal-lg">
        <h2>Historial de movimientos</h2>
        <br>

        <!-- Contenedor con scroll -->
        <div style="max-height: 400px; overflow-y: auto;">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Estatus</th>
                        <th>Fecha</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in history" :key="item.id_ctrl_historia_profesionalizacion">
                        <td>
                            <span class="badge me-1" :class="{
                                'bg-danger': item.estatus === 'RECHAZADO',
                                'bg-success': item.estatus === 'COMPLETADO POR DGCES',
                                'bg-warning': item.estatus !== 'RECHAZADO' && item.estatus !== 'COMPLETADO POR DGCES'
                            }">
                            </span>{{ item.estatus }}
                        </td>
                        <td>{{ item.actualizado_en }}</td>
                        <td>{{ item.observaciones }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </modalTemplate>
</template>

<script setup>
import { notyf } from '@components/notyf.js';
import axios from '@axios'
import modalTemplate from '@helpers/modal/modal-template.vue';
import { ref, onMounted } from 'vue'

const history = ref([])

onMounted(() => {
    const modal = document.getElementById('modal_follow_history')
    if (modal) {
        modal.addEventListener('shown.bs.modal', fetchEmployeeData)
    }
})

async function fetchEmployeeData() {
    try {
        const response = await axios.post('/follow/history')
        const result = response.data.data

        if (response.data.status) {
            history.value = result
        } else {
            notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
        }

    } catch (error) {
        notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
    }
}

function follow_history_hide() {
    $('#modal_follow_history').modal('hide');
}
</script>
