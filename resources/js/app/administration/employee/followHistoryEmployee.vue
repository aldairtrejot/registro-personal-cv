<template>
  <modalTemplate
    modal-id="modal_employee_history"
    :on-confirm="closeModal"
    dialog-class="modal-dialog modal-dialog-centered modal-lg"
  >
    <h2>Historial de movimientos</h2>
    <br />

    <div style="max-height: 400px; overflow-y: auto;">
      <table class="table table-vcenter card-table">
        <thead>
          <tr>
            <th>Estatus</th>
            <th>Fecha</th>
            <th>Observaciones</th>
          </tr>
        </thead>

        <tbody v-if="loading">
          <tr>
            <td colspan="3">
              <div class="p-4 text-center">
                <div class="spinner-border" role="status" aria-hidden="true"></div>
                <div class="mt-2">Cargando historial…</div>
              </div>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr v-if="!history.length">
            <td colspan="3">
              <div class="p-3">Aún no hay movimientos en el historial.</div>
            </td>
          </tr>

          <tr v-for="item in history" :key="item.id_ctrl_historia_profesionalizacion">
            <td>
              <span
                class="badge me-1"
                :class="{
                  'bg-danger': item.estatus === 'RECHAZADO',
                  'bg-success': item.estatus === 'COMPLETADO POR DGCES',
                  'bg-warning': item.estatus !== 'RECHAZADO' && item.estatus !== 'COMPLETADO POR DGCES'
                }"
              ></span>
              {{ item.estatus }}
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
import { ref, onMounted, onUnmounted } from 'vue'
import axios from '@axios'
import { notyf } from '@components/notyf.js'
import modalTemplate from '@helpers/modal/modal-template.vue'

const history = ref([])
const loading = ref(false)

let handler = null

onMounted(() => {
  const modal = document.getElementById('modal_employee_history')
  if (modal && !modal.dataset.historyBound) {
    handler = fetchEmployeeHistory
    modal.addEventListener('shown.bs.modal', handler)
    modal.dataset.historyBound = '1' // evita listeners duplicados
  }
})

onUnmounted(() => {
  const modal = document.getElementById('modal_employee_history')
  if (modal && handler) {
    modal.removeEventListener('shown.bs.modal', handler)
  }
})

async function fetchEmployeeHistory () {
  try {
    loading.value = true
    history.value = []

    const profId = (document.getElementById('prof_id')?.value || '').trim()
    if (!profId) {
      notyf.error('No pudimos identificar el expediente. Vuelve a intentar.')
      return
    }

    const tmpl = window.FOLLOW_PROPS?.routes?.employeeHistoryTmpl || '/employee/__ID__/history'
    const url  = tmpl.replace('__ID__', profId)

    const { data } = await axios.get(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    if (data?.status) {
      history.value = data.data || []
    } else {
      console.error('Respuesta backend:', data)
      notyf.error(data?.message || 'No pudimos cargar el historial. Intenta de nuevo.')
    }
  } catch (e) {
    console.error('Error fetchEmployeeHistory:', e?.response?.data || e)
    notyf.error('Revisa tu conexión e inténtalo de nuevo.')
  } finally {
    loading.value = false
  }
}

function closeModal () {
  if (window.$?.fn?.modal) {
    window.$('#modal_employee_history').modal('hide')
  }
}
</script>