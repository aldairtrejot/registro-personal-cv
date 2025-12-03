<template>
  <modalTemplate
    modal-id="modal_doc_history"
    :on-confirm="closeModal"
    dialog-class="modal-dialog modal-dialog-centered modal-lg"
  >
    <h2>Historia de documento</h2>
    <br />

    <div style="max-height: 400px; overflow-y: auto;">
      <table class="table table-vcenter card-table">
        <thead>
          <tr><th>Estatus</th><th>Fecha</th><th>Observaciones</th></tr>
        </thead>

        <tbody v-if="loading">
          <tr><td colspan="3">
            <div class="p-4 text-center">
              <div class="spinner-border" role="status" aria-hidden="true"></div>
              <div class="mt-2">Cargando historial…</div>
            </div>
          </td></tr>
        </tbody>

        <tbody v-else>
          <tr v-if="!history.length"><td colspan="3"><div class="p-3">Aún no hay movimientos para este documento.</div></td></tr>
          <tr v-for="item in history" :key="item.id">
            <td>
              <span class="badge me-1" :class="{
                'bg-danger': item.estatus === 'RECHAZADO',
                'bg-success': String(item.estatus || '').includes('COMPLETADO'),
                'bg-warning': item.estatus !== 'RECHAZADO' && !String(item.estatus || '').includes('COMPLETADO')
              }"></span>
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

const loading = ref(false)
const history = ref([])
const currentDocId = ref(null)

let openHandler = null

onMounted(() => {
  openHandler = (ev) => {
    currentDocId.value = Number(ev.detail?.docId || 0)
    if (!currentDocId.value) { notyf.error('No pudimos abrir la historia de este documento.'); return }
    showModal()
    fetchHistory()
  }
  window.addEventListener('open-doc-history', openHandler)
})

onUnmounted(() => {
  if (openHandler) window.removeEventListener('open-doc-history', openHandler)
})

function showModal () {
  const el = document.getElementById('modal_doc_history')
  if (el && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(el).show()
  else if (window.$?.fn?.modal) window.$('#modal_doc_history').modal('show')
}

async function fetchHistory () {
  try {
    loading.value = true
    history.value = []

    const tmpl = window.FOLLOW_PROPS?.routes?.docHistoryTmpl || '/employee/document/__ID__/history'
    const url  = tmpl.replace('__ID__', String(currentDocId.value))

    const { data } = await axios.get(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    if (data?.status) history.value = data.data || []
    else {
      console.error('Backend doc history:', data)
      notyf.error(data?.message || 'No pudimos cargar el historial del documento. Intenta de nuevo.')
    }
  } catch (e) {
    console.error('fetchHistory error:', e?.response?.data || e)
    notyf.error('Revisa tu conexión e inténtalo de nuevo.')
  } finally {
    loading.value = false
  }
}

function closeModal () {
  if (window.$?.fn?.modal) window.$('#modal_doc_history').modal('hide')
}
</script>