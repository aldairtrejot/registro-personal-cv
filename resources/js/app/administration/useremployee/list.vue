<template>
  <div class="card shadow-lg">
    <div class="card-body">
      <tableTittle value="Usuario Empleados" />

      <tableSpinner ref="spinnerRef" />

      <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped" id="table-default">
          <thead>
            <tr>
              <!-- <tableRow value="Acciones" /> -->
              <tableRow value="Nombre" />
              <tableRow value="Correo" />
              <tableRow value="Estatus" />
            </tr>
          </thead>
          <tbody>
            <tableEmpty v-if="item.length === 0" :colspan="3" />

            <tr v-for="row in item" :key="row.id">
              <!--
              <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <tableButtonEdit
                    :href="`${BASE_URL}/employee/edit/${row.id}`"
                    color="#479cc4"
                    icon="ti ti-edit"
                    tooltip="Modificar"
                  />
                </div>
              </td>
              -->

              <tableItem :value="row.nombre" />
              <tableItem :value="row.email" />

              <!-- Switch editable de estatus -->
              <td>
                <div class="form-check form-switch d-flex justify-content-center">
                  <input
                    class="form-check-input custom-switch"
                    type="checkbox"
                    role="switch"
                    :checked="row.estatus"
                    :disabled="isUpdating"
                    @change="toggleStatus(row)"
                  >
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <tableFooter :row="row" :rowsAll="rowsAll" />
    </div>
  </div>
</template>

<script setup>
// Vue core
import { ref, onMounted, watch } from 'vue'

// Utilidades
import { BASE_URL } from '@/components/url.js'
import { notyf } from '@components/notyf.js'

// Funciones tabla
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'

// Componentes personalizados
import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableItem from '@helpers/table/table-item.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import { showSpinner, hideSpinner } from '@components/spinner.js'
// import tableButtonEdit from '@helpers/table/table-button-edit.vue'

// Axios
import axios from '@axios'

// Reactividad
const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)
const isUpdating = ref(false)
let debounceTimer = null

// Fetch data
const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()

  spinnerRef.value?.show()

  const offset = (currentPage.value - 1) * limit.value

  try {
    const { data } = await axios.post('/useremployee/table', {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      select: parseInt(document.getElementById('footer-filter')?.value || 5),
    })

    item.value = data.list
    rowsAll.value = data.allRow
    row.value = data.row
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0

    setTimeout(() => {
      spinnerRef.value?.hide()
    }, delay)
  }
}

// Toggle estatus
const toggleStatus = async (row) => {
  const nuevoEstatus = !row.estatus
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()

  showSpinner();
  isUpdating.value = true

  try {
    const { data } = await axios.post('/useremployee/toggle-status', {
      id: row.id,
      estatus: nuevoEstatus,
    })

    if (data.status) {
      row.estatus = nuevoEstatus
      notyf.success('Estatus actualizado correctamente.')
    } else {
      notyf.error('No se pudo actualizar el estatus.')
    }
  } catch (error) {
    notyf.error('Error de conexión al actualizar estatus.')
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0

    setTimeout(() => {
      hideSpinner();
      isUpdating.value = false
    }, delay)
  }
}

// Búsqueda con debounce
watch(searchTerm, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    fetchTableData()
  }, 500)
})

// Montaje inicial
onMounted(() => {
  fetchTableData()
  setupTableEvents({
    fetchTableData,
    searchTerm,
    currentPage,
    limit,
    handlePagination,
  })
})
</script>






