<template>
    <div class="card shadow-lg">
        <div class="card-body">
            <tableTittle value="Ramas" />

            <tableSpinner ref="spinnerRef" />

            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped" id="table-default">
                    <thead>
                        <tr>
                           <!--<tableRow value="Acciones" />-->
                           
                            <tableRow value="Descripción" />
                            <tableRow value="Estatus" />
                        </tr>
                    </thead>
                    <tbody>
                        <tableEmpty v-if="item.length === 0" :colspan="4" />

                        <tr v-for="row in item" :key="row.id">
                            <!-- ACCIONES 
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a :href="`${BASE_URL}/role/branch/edit/${row.id}`"
                                        class="btn btn-6 btn-vimeo btn-icon shadow-sm" aria-label="Editar">
                                        <i class="ti ti-edit" style="font-size: 1.2rem;"></i>
                                    </a>
                                </div>
                            </td>-->

                            <!-- NOMBRE 
                            <tableItem :value="row.nombre" /> -->

                            <!-- DESCRIPCIÓN -->
                            <tableItem :value="row.descripcion" />

                            <!-- ESTATUS -->
                            <tableItem :value="row.estatus ? 'Activo' : 'Inactivo'" />
                        </tr>
                    </tbody>
                </table>
            </div>

            <tableFooter :row="row" :rowsAll="rowsAll" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BASE_URL } from '@/components/url.js'
import { notyf } from '@components/notyf.js'

import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'

import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableItem from '@helpers/table/table-item.vue'
import tableEmpty from '@helpers/table/table-empty.vue'

import axios from '@axios'

const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

const fetchTableData = async () => {
    const MIN_SPINNER_DURATION = 1000
    const startTime = Date.now()

    spinnerRef.value?.show()

    const offset = (currentPage.value - 1) * limit.value

    try {
        const { data } = await axios.post('/role/branch/table', {
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

onMounted(() => {
    fetchTableData()
    setupTableEvents({
        fetchTableData,
        searchTerm,
        currentPage,
        limit,
        handlePagination
    })
})
</script>
