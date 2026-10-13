<template>
  <div class="container-xl py-4 imss-theme">
    <!-- SPINNER / PROGRESO GLOBAL ZIP -->
    <div v-if="zipDescargando" class="imss-loading-backdrop" role="status" aria-live="polite">
      <div class="imss-loading-card">
        <div class="imss-loading-spinner"></div>

        <div class="imss-loading-title">
          Generando ZIP de CV aprobados
        </div>

        <div class="imss-loading-text">
          {{ zipProgresoTexto }}
        </div>

        <div class="progress imss-progress mt-3" style="height: 18px;">
          <div
            class="progress-bar imss-progress-bar"
            role="progressbar"
            :style="{ width: zipProgreso + '%' }"
            :aria-valuenow="zipProgreso"
            aria-valuemin="0"
            aria-valuemax="100"
          >
            {{ zipProgreso }}%
          </div>
        </div>

        <div class="imss-loading-subtext">
          Si son muchos registros, el proceso puede tardar varios minutos.
          Por favor no cierres esta ventana.
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-12">
        <div class="card imss-card">
          <!-- HEADER -->
          <div class="card-header imss-card-header">
            <div class="imss-header-layout">
              <div class="min-w-0">
                <div class="imss-kicker">Módulo Revisor</div>
                <h3 class="imss-title mb-1">Revisión de CV</h3>
                <div class="imss-subtitle">
                  Consulta, valida y descarga fichas curriculares del personal.
                </div>
              </div>

              <div class="imss-actions">
                <div class="imss-action-group imss-action-group-period">
                  <div class="imss-action-title">Aprobados del periodo</div>

                  <div class="imss-action-buttons imss-download-grid">
                    <button
                      type="button"
                      class="imss-action-btn imss-action-btn-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#modalZipAprobados"
                      @click="abrirModalDescarga('zip')"
                      :disabled="descargaOcupada"
                      title="Configurar descarga ZIP"
                    >
                      <span class="imss-action-icon">
                        <span v-if="zipDescargando" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <i v-else class="ti ti-file-zip" aria-hidden="true"></i>
                      </span>
                      <span class="imss-action-text">
                        <span class="imss-action-label" v-if="zipDescargando">Generando ZIP...</span>
                        <span class="imss-action-label" v-else>ZIP</span>
                        <span class="imss-action-subtitle">PDF por periodo</span>
                      </span>
                    </button>

                    <button
                      type="button"
                      class="imss-action-btn imss-action-btn-secondary"
                      data-bs-toggle="modal"
                      data-bs-target="#modalZipAprobados"
                      @click="abrirModalDescarga('excel')"
                      :disabled="descargaOcupada"
                      title="Configurar descarga Excel"
                    >
                      <span class="imss-action-icon">
                        <span v-if="excelDescargando" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <i v-else class="ti ti-table-export" aria-hidden="true"></i>
                      </span>
                      <span class="imss-action-text">
                        <span class="imss-action-label" v-if="excelDescargando">Generando Excel...</span>
                        <span class="imss-action-label" v-else>Excel</span>
                        <span class="imss-action-subtitle">Formato oficial</span>
                      </span>
                    </button>
                  </div>
                </div>

                <div class="imss-action-group imss-action-group-list">
                  <div class="imss-action-title">Reporte del listado</div>

                  <div class="imss-action-buttons imss-report-action">
                    <button
                      type="button"
                      class="imss-action-btn imss-action-btn-soft"
                      @click="descargarReportePorEstatus"
                      :disabled="descargaOcupada"
                      title="Descargar reporte por estatus con los filtros actuales"
                    >
                      <span class="imss-action-icon">
                        <span v-if="reporteEstatusDescargando" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <i v-else class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                      </span>
                      <span class="imss-action-text">
                        <span class="imss-action-label" v-if="reporteEstatusDescargando">Generando...</span>
                        <span class="imss-action-label" v-else>Estatus</span>
                        <span class="imss-action-subtitle">Filtros actuales</span>
                      </span>
                    </button>
                  </div>
                </div>

                <div class="imss-action-group imss-action-group-import">
                  <div class="imss-action-title">Carga masiva</div>

                  <div class="imss-action-buttons imss-report-action">
                    <button
                      type="button"
                      class="imss-action-btn imss-action-btn-soft"
                      data-bs-toggle="modal"
                      data-bs-target="#modalCargaMasivaEmpleados"
                      @click="abrirModalCargaMasiva"
                      :disabled="descargaOcupada"
                      title="Cargar empleados desde Excel"
                    >
                      <span class="imss-action-icon">
                        <span v-if="importacionOcupada" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <i v-else class="ti ti-upload" aria-hidden="true"></i>
                      </span>
                      <span class="imss-action-text">
                        <span class="imss-action-label">Excel</span>
                        <span class="imss-action-subtitle">Alta masiva</span>
                      </span>
                    </button>
                  </div>
                </div>

                <div class="imss-curp-box">
                  <div class="imss-curp-head">
                    <label class="imss-label mb-0">Descarga por CURP</label>
                    <span>18 caracteres</span>
                  </div>
                  <div class="imss-curp-row">
                    <input
                      v-model.trim="curpDescarga"
                      type="text"
                      class="form-control form-control-sm imss-input"
                      placeholder="CURP (18)"
                      maxlength="18"
                      autocomplete="off"
                      :disabled="zipDescargando"
                    />
                    <button
                      type="button"
                      class="imss-curp-button"
                      @click="descargarPdfPorCurp"
                      :disabled="!curpValida || zipDescargando"
                      title="Descargar PDF por CURP"
                    >
                      <i class="ti ti-file-type-pdf" aria-hidden="true"></i>
                      <span>PDF</span>
                    </button>
                  </div>
                  <div class="imss-help" v-if="curpDescarga && !curpValida">
                    La CURP debe tener 18 caracteres.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- FILTROS -->
          <div class="card-body imss-filters">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-md-8">
                <label class="imss-label">Búsqueda</label>
                <div class="imss-input-wrap">
                  <span class="imss-icon" aria-hidden="true">🔎</span>
                  <input
                    v-model="filtros.busqueda"
                    type="text"
                    class="form-control form-control-sm imss-input imss-input-with-icon"
                    placeholder="Buscar por nombre, CURP o área"
                    :disabled="zipDescargando"
                  />
                </div>
              </div>

              <div class="col-12 col-md-4">
                <label class="imss-label">Estatus</label>
                <select
                  v-model="filtros.status"
                  class="form-select form-select-sm imss-select"
                  :disabled="zipDescargando"
                >
                  <option value="">Todos</option>
                  <option value="sin_estatus">Sin estatus</option>
                  <option value="edicion">En edición</option>
                  <option value="enviado">Enviado</option>
                  <option value="aprobado">Aprobado</option>
                  <option value="rechazado">Rechazado</option>
                </select>
              </div>
            </div>

            <!-- RESUMEN MEJORADO -->
            <div class="imss-summary-strip mt-3">
              <div class="imss-summary-item">
                <span>Total cargado</span>
                <strong>{{ empleados.length }}</strong>
              </div>

              <div class="imss-summary-item">
                <span>Filtrados</span>
                <strong>{{ totalRegistrosFiltrados }}</strong>
              </div>

              <div class="imss-summary-item">
                <span>Aprobados visibles</span>
                <strong>{{ totalAprobadosFiltrados }}</strong>
              </div>
            </div>
          </div>

          <!-- TABLA -->
          <div class="table-responsive imss-table-wrap">
            <table class="table align-middle mb-0 imss-table">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th style="width:170px;">CURP</th>
                  <th>Área</th>
                  <th style="width:150px;">Estatus</th>
                  <th class="text-end" style="width:220px;">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="loadingEmpleados">
                  <td colspan="5" class="text-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    Cargando empleados…
                  </td>
                </tr>

                <tr v-else-if="empleadosPaginados.length === 0">
                  <td colspan="5" class="text-center py-4 text-muted">
                    No hay registros que coincidan con los filtros.
                  </td>
                </tr>

                <tr v-for="(emp, index) in empleadosPaginados" :key="rowKey(emp, index)">
                  <td>
                    <div class="fw-semibold imss-name">{{ emp.nombre || 'N/D' }}</div>
                  </td>

                  <td>
                    <code class="imss-code">{{ emp.curp || 'N/D' }}</code>
                  </td>

                  <td>
                    {{ emp.area_adscripcion || emp.area || 'N/D' }}
                  </td>

                  <td>
                    <span class="imss-badge" :class="badgeClass(statusKey(emp))">
                      {{ statusLabel(statusKey(emp)) }}
                    </span>
                  </td>

                  <td class="text-end">
                    <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                      <a
                        :href="detalleUrl(getId(emp))"
                        class="imss-row-btn imss-row-btn-detail"
                        :class="{ disabled: zipDescargando }"
                      >
                        <i class="ti ti-eye me-1" aria-hidden="true"></i>
                        Ver detalle
                      </a>

                      <a
                        :href="pdfEmpleadoUrl(getId(emp))"
                        class="imss-row-btn imss-row-btn-pdf"
                        :class="{ disabled: zipDescargando }"
                      >
                        <i class="ti ti-file-type-pdf me-1" aria-hidden="true"></i>
                        PDF
                      </a>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- FOOTER / PAGINACIÓN -->
          <div class="card-footer imss-footer">
            <div class="imss-footer-grid">
              <div class="imss-footer-left">
                <label class="imss-footer-label">Mostrar</label>
                <select
                  v-model.number="rowsPerPage"
                  class="form-select form-select-sm imss-select imss-select-footer"
                  @change="onRowsPerPageChange"
                  :disabled="zipDescargando"
                >
                  <option v-for="opt in perPageOptions" :key="opt" :value="opt">
                    {{ opt }}
                  </option>
                </select>
                <span class="imss-footer-label">registros</span>
              </div>

              <div class="imss-footer-center text-muted small">
                <template v-if="totalRegistrosFiltrados > 0">
                  Mostrando <strong>{{ pageStart }}</strong> a <strong>{{ pageEnd }}</strong>
                  de <strong>{{ totalRegistrosFiltrados }}</strong> registro(s) filtrado(s).
                </template>
                <template v-else>
                  Mostrando <strong>0</strong> de <strong>0</strong> registro(s).
                </template>
              </div>

              <div class="imss-footer-right">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary imss-page-btn"
                  @click="goToPreviousPage"
                  :disabled="!canGoPrev || zipDescargando"
                >
                  Anterior
                </button>

                <div class="imss-page-group">
                  <button
                    v-for="page in visiblePages"
                    :key="page"
                    type="button"
                    class="btn btn-sm imss-page-btn"
                    :class="page === currentPageSafe ? 'imss-page-btn-active' : 'btn-outline-secondary'"
                    @click="goToPage(page)"
                    :disabled="zipDescargando"
                  >
                    {{ page }}
                  </button>
                </div>

                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary imss-page-btn"
                  @click="goToNextPage"
                  :disabled="!canGoNext || zipDescargando"
                >
                  Siguiente
                </button>
              </div>
            </div>

            <div class="mt-2 text-muted small">
              Página <strong>{{ currentPageSafe }}</strong> de <strong>{{ totalPages }}</strong>.
              Total cargado desde la base: <strong>{{ empleados.length }}</strong> registro(s).
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL ZIP -->
    <div class="modal modal-blur fade" id="modalZipAprobados" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 560px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ descargaModalTitulo }}</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
              :disabled="descargaOcupada"
            ></button>
          </div>

          <div class="modal-body">
            <div class="alert alert-info">
              Filtra {{ descargaModalFormato }} por la fecha de última actualización del CV.
            </div>

            <div class="alert alert-warning small mb-3">
              <span v-if="descargaModalTipo === 'zip'">
                Si hay muchos CV aprobados, el sistema puede tardar mientras genera los PDF y arma el ZIP.
              </span>
              <span v-else>
                Si hay muchos CV aprobados, el sistema puede tardar mientras genera el Excel.
              </span>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Modalidad</label>
                <select
                  class="form-select"
                  v-model="zipFiltro.modo"
                  :disabled="descargaOcupada"
                >
                  <option value="trimestre">Por trimestre</option>
                  <option value="rango">Por rango de fechas</option>
                </select>
              </div>

              <template v-if="zipFiltro.modo === 'trimestre'">
                <div class="col-12 col-md-6">
                  <label class="form-label">Ejercicio (año)</label>
                  <input
                    type="number"
                    class="form-control"
                    v-model.number="zipFiltro.ejercicio"
                    min="2000"
                    max="2100"
                    :disabled="descargaOcupada"
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">Trimestre</label>
                  <select
                    class="form-select"
                    v-model.number="zipFiltro.trimestre"
                    :disabled="descargaOcupada"
                  >
                    <option :value="1">1 (Ene-Mar)</option>
                    <option :value="2">2 (Abr-Jun)</option>
                    <option :value="3">3 (Jul-Sep)</option>
                    <option :value="4">4 (Oct-Dic)</option>
                  </select>
                </div>

                <div class="col-12 text-muted small">
                  Puedes elegir cualquier trimestre y año disponible; no se limita al periodo actual.
                </div>
              </template>

              <template v-else>
                <div class="col-12 col-md-6">
                  <label class="form-label">Fecha inicial</label>
                  <input
                    type="date"
                    class="form-control"
                    v-model="zipFiltro.fecha_inicio"
                    :disabled="descargaOcupada"
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">Fecha final</label>
                  <input
                    type="date"
                    class="form-control"
                    v-model="zipFiltro.fecha_fin"
                    :disabled="descargaOcupada"
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">Año asignado</label>
                  <input
                    type="number"
                    class="form-control"
                    v-model.number="zipFiltro.ejercicio"
                    min="2000"
                    max="2100"
                    :disabled="descargaOcupada"
                  />
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">Trimestre asignado</label>
                  <select
                    class="form-select"
                    v-model.number="zipFiltro.trimestre"
                    :disabled="descargaOcupada"
                  >
                    <option :value="1">T1 (Ene-Mar)</option>
                    <option :value="2">T2 (Abr-Jun)</option>
                    <option :value="3">T3 (Jul-Sep)</option>
                    <option :value="4">T4 (Oct-Dic)</option>
                  </select>
                </div>
              </template>
            </div>

            <div class="mt-3 text-muted small">
              * Se usa la fecha de última actualización para buscar los CV aprobados.
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn me-auto"
              data-bs-dismiss="modal"
              :disabled="descargaOcupada"
            >
              Cancelar
            </button>

            <button
              v-if="descargaModalTipo === 'excel'"
              type="button"
              class="btn btn-outline-success"
              @click="descargarReporteExcel"
              :disabled="descargaOcupada"
            >
              <span v-if="excelDescargando" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
              <span v-if="excelDescargando">Generando Excel…</span>
              <span v-else>Descargar Excel</span>
            </button>

            <button
              v-else
              type="button"
              class="btn btn-success"
              @click="descargarZipAprobados"
              :disabled="descargaOcupada"
            >
              <span v-if="zipDescargando" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
              <span v-if="zipDescargando">Generando ZIP…</span>
              <span v-else>Descargar ZIP</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CARGA MASIVA -->
    <div class="modal modal-blur fade" id="modalCargaMasivaEmpleados" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Carga masiva de empleados</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
              :disabled="importacionOcupada"
            ></button>
          </div>

          <div class="modal-body">
            <div class="alert alert-info">
              Sube un archivo Excel con las columnas nombre, primer apellido, segundo apellido y CURP.
            </div>

            <div class="row g-3 align-items-end">
              <div class="col-12 col-lg-8">
                <label class="form-label">Archivo Excel</label>
                <input
                  ref="archivoCargaMasiva"
                  type="file"
                  class="form-control"
                  accept=".xlsx,.xls"
                  :disabled="importacionOcupada"
                  @change="onArchivoCargaMasivaChange"
                />
              </div>

              <div class="col-12 col-lg-4 d-flex gap-2 flex-wrap">
                <button
                  type="button"
                  class="btn btn-success"
                  @click="analizarCargaMasiva"
                  :disabled="importacionOcupada || !importacion.archivo"
                >
                  <span v-if="importacion.procesando" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                  Analizar archivo
                </button>

                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  @click="limpiarCargaMasiva"
                  :disabled="importacionOcupada"
                >
                  Limpiar
                </button>
              </div>
            </div>

            <div v-if="importacion.advertencias.length" class="alert alert-warning small mt-3 mb-0">
              <div v-for="(warning, idx) in importacion.advertencias" :key="'warning-' + idx">
                {{ warning }}
              </div>
            </div>

            <div v-if="importacion.resumen" class="imss-import-summary mt-3">
              <div class="imss-import-summary-item">
                <span>Total</span>
                <strong>{{ importacion.resumen.total || 0 }}</strong>
              </div>
              <div class="imss-import-summary-item is-ok">
                <span>Validos</span>
                <strong>{{ importacion.resumen.validos || 0 }}</strong>
              </div>
              <div class="imss-import-summary-item is-dup">
                <span>Duplicados</span>
                <strong>{{ importacion.resumen.duplicados || 0 }}</strong>
              </div>
              <div class="imss-import-summary-item is-bad">
                <span>Rechazados</span>
                <strong>{{ importacion.resumen.rechazados || 0 }}</strong>
              </div>
              <div class="imss-import-summary-item is-inserted">
                <span>Insertados</span>
                <strong>{{ importacion.resumen.insertados || 0 }}</strong>
              </div>
            </div>

            <div v-if="importacion.resultados.length" class="mt-3">
              <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                <div class="text-muted small">
                  Archivo: <strong>{{ importacion.archivoNombre || 'N/D' }}</strong>
                </div>

                <select
                  v-model="importacion.filtro"
                  class="form-select form-select-sm imss-import-filter"
                  :disabled="importacionOcupada"
                >
                  <option value="todos">Todos</option>
                  <option value="valido">Validos</option>
                  <option value="duplicado">Duplicados</option>
                  <option value="rechazado">Rechazados</option>
                  <option value="insertado">Insertados</option>
                </select>
              </div>

              <div class="table-responsive imss-import-preview-wrap">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th style="width: 70px;">Fila</th>
                      <th style="width: 120px;">Estado</th>
                      <th>Motivo</th>
                      <th style="width: 180px;">CURP</th>
                      <th>Nombre</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in importacionResultadosVisibles" :key="'import-' + row.fila">
                      <td>{{ row.fila }}</td>
                      <td>
                        <span class="imss-import-badge" :class="cargaEstadoBadge(row.estado)">
                          {{ cargaEstadoLabel(row.estado) }}
                        </span>
                      </td>
                      <td>{{ row.motivo || 'Sin observaciones' }}</td>
                      <td><code class="imss-code">{{ row.curp || 'N/D' }}</code></td>
                      <td>
                        {{ nombreImportacion(row) }}
                      </td>
                    </tr>
                    <tr v-if="importacionResultadosFiltrados.length === 0">
                      <td colspan="5" class="text-center text-muted py-3">
                        No hay registros para el filtro seleccionado.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="importacionResultadosFiltrados.length > importacionPreviewLimit" class="text-muted small mt-2">
                Mostrando {{ importacionPreviewLimit }} de {{ importacionResultadosFiltrados.length }} registro(s) del filtro.
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn me-auto"
              data-bs-dismiss="modal"
              :disabled="importacionOcupada"
            >
              Cerrar
            </button>

            <button
              type="button"
              class="btn btn-outline-success"
              @click="descargarReporteCargaMasiva"
              :disabled="importacionOcupada || !importacion.token"
            >
              Descargar reporte
            </button>

            <button
              type="button"
              class="btn btn-success"
              @click="confirmarCargaMasiva"
              :disabled="importacionOcupada || !puedeConfirmarCargaMasiva"
            >
              <span v-if="importacion.confirmando" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
              Confirmar carga
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TOAST -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 4000;">
      <div
        id="toast_cv_zip"
        ref="toastEl"
        :class="[
          'toast',
          'align-items-center',
          'border-0',
          toastTipo === 'ok' ? 'text-bg-success' : 'text-bg-danger'
        ]"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
        data-bs-delay="6500"
      >
        <div class="d-flex">
          <div class="toast-body">
            {{ toastMsg || 'No se pudo completar la operación.' }}
          </div>

          <button
            type="button"
            class="btn-close btn-close-white me-2 m-auto"
            data-bs-dismiss="toast"
            aria-label="Close"
          ></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@axios'
import { BASE_URL } from '@/components/url.js'

const EJ_MIN = 2000
const EJ_MAX = 2100
const TRIM_OK = [1, 2, 3, 4]
const PER_PAGE_OPTIONS = [5, 10, 15, 20]

export default {
  name: 'RevisorEmpleadoList',

  data() {
    const now = new Date()
    const month = now.getMonth() + 1
    const trimestreActual = month <= 3 ? 1 : month <= 6 ? 2 : month <= 9 ? 3 : 4

    return {
      BASE_URL,

      filtros: {
        busqueda: '',
        status: '',
      },

      empleados: [],
      curpDescarga: '',

      loadingEmpleados: false,

      zipFiltro: {
        modo: 'trimestre',
        ejercicio: now.getFullYear(),
        trimestre: trimestreActual,
        fecha_inicio: '',
        fecha_fin: '',
      },

      descargaModalTipo: 'zip',
      zipDescargando: false,
      excelDescargando: false,
      reporteEstatusDescargando: false,
      importacion: {
        archivo: null,
        archivoNombre: '',
        token: null,
        procesando: false,
        confirmando: false,
        descargandoReporte: false,
        resumen: null,
        resultados: [],
        advertencias: [],
        filtro: 'todos',
        confirmado: false,
      },
      importacionPreviewLimit: 150,
      zipProgreso: 0,
      zipProgresoTexto: 'Preparando solicitud…',
      _zipProgressTimer: null,
      _zipStartedAt: null,

      // Toast
      toastMsg: '',
      toastTipo: 'error',
      _toastInstance: null,

      // Estabilidad
      _empReqId: 0,
      _debounceTimer: null,
      _debounceMs: 250,

      // Paginación
      currentPage: 1,
      rowsPerPage: 5,
      perPageOptions: PER_PAGE_OPTIONS,
    }
  },

  computed: {
    descargaOcupada() {
      return this.zipDescargando || this.excelDescargando || this.reporteEstatusDescargando || this.importacionOcupada
    },

    importacionOcupada() {
      return Boolean(this.importacion.procesando || this.importacion.confirmando || this.importacion.descargandoReporte)
    },

    puedeConfirmarCargaMasiva() {
      return Boolean(
        this.importacion.token &&
        !this.importacion.confirmado &&
        this.importacion.resumen &&
        Number(this.importacion.resumen.validos || 0) > 0
      )
    },

    importacionResultadosFiltrados() {
      const filtro = this.importacion.filtro || 'todos'
      const rows = Array.isArray(this.importacion.resultados) ? this.importacion.resultados : []

      if (filtro === 'todos') return rows

      return rows.filter((row) => row && row.estado === filtro)
    },

    importacionResultadosVisibles() {
      return this.importacionResultadosFiltrados.slice(0, this.importacionPreviewLimit)
    },

    descargaModalTitulo() {
      return this.descargaModalTipo === 'excel'
        ? 'Descargar Excel de aprobados'
        : 'Descargar ZIP de aprobados'
    },

    descargaModalFormato() {
      return this.descargaModalTipo === 'excel'
        ? 'el reporte Excel de CV aprobados'
        : 'el ZIP de CV aprobados'
    },

    curpValida() {
      const c = String(this.curpDescarga || '').trim().toUpperCase()
      return c.length === 18
    },

    empleadosFiltrados() {
      const texto = String(this.filtros.busqueda || '').trim().toLowerCase()
      const status = this.filtros.status

      return (this.empleados || []).filter((e) => {
        const st = this.statusKey(e)

        const nombre = String((e && e.nombre) || '').toLowerCase()
        const curp = String((e && e.curp) || '').toLowerCase()
        const area = String((e && (e.area_adscripcion || e.area)) || '').toLowerCase()
        const puesto = String((e && e.puesto_actual) || '').toLowerCase()

        const coincideTexto =
          !texto ||
          nombre.includes(texto) ||
          curp.includes(texto) ||
          area.includes(texto) ||
          puesto.includes(texto)

        const coincideStatus = !status || st === status

        return coincideTexto && coincideStatus
      })
    },

    totalAprobadosFiltrados() {
      return (this.empleadosFiltrados || []).filter((e) => this.statusKey(e) === 'aprobado').length
    },

    rowsPerPageSafe() {
      const n = Number(this.rowsPerPage || 5)
      return this.perPageOptions.includes(n) ? n : 5
    },

    totalRegistrosFiltrados() {
      return Array.isArray(this.empleadosFiltrados) ? this.empleadosFiltrados.length : 0
    },

    totalPages() {
      const total = Math.ceil(this.totalRegistrosFiltrados / this.rowsPerPageSafe)
      return total > 0 ? total : 1
    },

    currentPageSafe() {
      let page = Number(this.currentPage || 1)
      if (!Number.isFinite(page) || page < 1) page = 1
      if (page > this.totalPages) page = this.totalPages
      return page
    },

    pageStart() {
      if (this.totalRegistrosFiltrados === 0) return 0
      return ((this.currentPageSafe - 1) * this.rowsPerPageSafe) + 1
    },

    pageEnd() {
      if (this.totalRegistrosFiltrados === 0) return 0
      return Math.min(this.currentPageSafe * this.rowsPerPageSafe, this.totalRegistrosFiltrados)
    },

    empleadosPaginados() {
      const inicio = (this.currentPageSafe - 1) * this.rowsPerPageSafe
      const fin = inicio + this.rowsPerPageSafe
      return this.empleadosFiltrados.slice(inicio, fin)
    },

    canGoPrev() {
      return this.currentPageSafe > 1
    },

    canGoNext() {
      return this.currentPageSafe < this.totalPages
    },

    visiblePages() {
      const total = this.totalPages
      const current = this.currentPageSafe

      let start = Math.max(1, current - 2)
      let end = Math.min(total, start + 4)

      start = Math.max(1, end - 4)

      const pages = []
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      return pages
    },
  },

  methods: {
    abrirModalDescarga(tipo) {
      this.descargaModalTipo = tipo === 'excel' ? 'excel' : 'zip'
    },

    abrirModalCargaMasiva() {
      if (!this.importacion.token && !this.importacion.resultados.length) {
        this.importacion.filtro = 'todos'
      }
    },

    onArchivoCargaMasivaChange(event) {
      const file = event && event.target && event.target.files ? event.target.files[0] : null
      this.importacion.archivo = file || null
      this.importacion.archivoNombre = file ? file.name : ''
      this.importacion.token = null
      this.importacion.resumen = null
      this.importacion.resultados = []
      this.importacion.advertencias = []
      this.importacion.confirmado = false
      this.importacion.filtro = 'todos'
    },

    limpiarCargaMasiva() {
      this.importacion.archivo = null
      this.importacion.archivoNombre = ''
      this.importacion.token = null
      this.importacion.resumen = null
      this.importacion.resultados = []
      this.importacion.advertencias = []
      this.importacion.filtro = 'todos'
      this.importacion.confirmado = false

      if (this.$refs.archivoCargaMasiva) {
        this.$refs.archivoCargaMasiva.value = ''
      }
    },

    nombreImportacion(row) {
      return [
        row && row.nombre ? row.nombre : '',
        row && row.primer_apellido ? row.primer_apellido : '',
        row && row.segundo_apellido ? row.segundo_apellido : '',
      ].filter(Boolean).join(' ') || 'N/D'
    },

    cargaEstadoLabel(estado) {
      switch (estado) {
        case 'valido':
          return 'Valido'
        case 'duplicado':
          return 'Duplicado'
        case 'rechazado':
          return 'Rechazado'
        case 'insertado':
          return 'Insertado'
        default:
          return 'Pendiente'
      }
    },

    cargaEstadoBadge(estado) {
      switch (estado) {
        case 'valido':
          return 'is-ok'
        case 'duplicado':
          return 'is-dup'
        case 'rechazado':
          return 'is-bad'
        case 'insertado':
          return 'is-inserted'
        default:
          return ''
      }
    },

    _csrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },

    async analizarCargaMasiva() {
      if (this.importacionOcupada) return

      const file = this.importacion.archivo
      if (!file) {
        this.showToast('Selecciona un archivo Excel.')
        return
      }

      const form = new FormData()
      form.append('archivo', file)

      this.importacion.procesando = true

      try {
        const { data } = await axios.post(this._url('/api/revisor/empleados/carga-masiva/preview'), form, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': this._csrfToken(),
          },
        })

        this.importacion.token = data.token || null
        this.importacion.archivoNombre = data.archivo_original || file.name
        this.importacion.resumen = data.resumen || null
        this.importacion.resultados = Array.isArray(data.resultados) ? data.resultados : []
        this.importacion.advertencias = Array.isArray(data.advertencias) ? data.advertencias : []
        this.importacion.confirmado = Boolean(data.confirmado)
        this.importacion.filtro = 'todos'

        this.showToast('Archivo analizado. Revisa la vista previa antes de confirmar.', 'ok')
      } catch (e) {
        const msg = e && e.response && e.response.data && e.response.data.message
          ? e.response.data.message
          : 'No se pudo analizar el archivo Excel.'

        this.importacion.token = null
        this.importacion.resumen = null
        this.importacion.resultados = []
        this.importacion.advertencias = []
        this.importacion.confirmado = false
        this.showToast(msg)
      } finally {
        this.importacion.procesando = false
      }
    },

    async confirmarCargaMasiva() {
      if (this.importacionOcupada || !this.puedeConfirmarCargaMasiva) return

      this.importacion.confirmando = true

      try {
        const { data } = await axios.post(this._url('/api/revisor/empleados/carga-masiva/confirmar'), {
          token: this.importacion.token,
        }, {
          headers: {
            'X-CSRF-TOKEN': this._csrfToken(),
          },
        })

        this.importacion.resumen = data.resumen || null
        this.importacion.resultados = Array.isArray(data.resultados) ? data.resultados : []
        this.importacion.advertencias = Array.isArray(data.advertencias) ? data.advertencias : []
        this.importacion.confirmado = Boolean(data.confirmado)

        const insertados = Number((this.importacion.resumen && this.importacion.resumen.insertados) || 0)
        this.showToast('Carga confirmada. Insertados: ' + insertados + '.', 'ok')
        this.cargarEmpleados()
      } catch (e) {
        const msg = e && e.response && e.response.data && e.response.data.message
          ? e.response.data.message
          : 'No se pudo confirmar la carga masiva.'

        this.showToast(msg)
      } finally {
        this.importacion.confirmando = false
      }
    },

    async descargarReporteCargaMasiva() {
      if (this.importacionOcupada || !this.importacion.token) return

      this.importacion.descargandoReporte = true

      try {
        const resp = await fetch(this._url('/api/revisor/empleados/carga-masiva/reporte/' + encodeURIComponent(this.importacion.token)), {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {
            Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream, application/json, text/plain, */*',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })

        if (!resp.ok) {
          const msgServidor = await this._leerMensajeErrorResponse(resp)
          this.showToast(msgServidor || 'No se pudo descargar el reporte de carga.')
          return
        }

        const blob = await resp.blob()
        if (!blob || blob.size === 0) {
          this.showToast('El reporte de carga se genero vacio.')
          return
        }

        const filename = this._obtenerFilenameDesdeHeaders(resp, 'resultado_carga_empleados.xlsx')
        const objectUrl = window.URL.createObjectURL(blob)

        const a = document.createElement('a')
        a.href = objectUrl
        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()

        window.URL.revokeObjectURL(objectUrl)
        this.showToast('Reporte de carga generado correctamente.', 'ok')
      } catch (e) {
        console.error('Error al descargar reporte de carga:', e)
        this.showToast('No se pudo descargar el reporte de carga.')
      } finally {
        this.importacion.descargandoReporte = false
      }
    },

    // ---------------------------------
    // URLs
    // ---------------------------------
    _joinUrl(base, path) {
      const b = String(base || '').replace(/\/+$/, '')
      const p = String(path || '')
      if (!b) return p
      return b + (p.startsWith('/') ? p : '/' + p)
    },

    _url(path) {
      if (this.BASE_URL) return this._joinUrl(this.BASE_URL, path)
      return path
    },

    // ---------------------------------
    // Toast Bootstrap
    // ---------------------------------
    _getToastCtor() {
      const b = window && window.bootstrap ? window.bootstrap : null
      return b && b.Toast ? b.Toast : null
    },

    showToast(message, tipo = 'error') {
      this.toastMsg = message || 'No se pudo completar la operación.'
      this.toastTipo = tipo === 'ok' ? 'ok' : 'error'

      this.$nextTick(() => {
        const Toast = this._getToastCtor()
        const el = this.$refs.toastEl

        if (!Toast || !el) {
          window.alert(this.toastMsg)
          return
        }

        try {
          if (this._toastInstance) {
            this._toastInstance.dispose()
            this._toastInstance = null
          }
        } catch (_) {}

        try {
          this._toastInstance = Toast.getOrCreateInstance(el)
          this._toastInstance.show()
        } catch (_) {
          window.alert(this.toastMsg)
        }
      })
    },

    // ---------------------------------
    // Helpers
    // ---------------------------------
    getId(emp) {
      if (emp && typeof emp === 'object') {
        if (emp.id_tbl_empleados != null) return emp.id_tbl_empleados
        if (emp.id != null) return emp.id
      }

      return null
    },

    rowKey(emp, index) {
      const id = this.getId(emp)
      if (id != null) return String(id)

      const curp = emp && emp.curp ? String(emp.curp) : ''
      if (curp) return curp

      return 'row-' + String(index)
    },

    statusKey(emp) {
      if (emp && emp.status) {
        return emp.status === 'sin_cv' ? 'sin_estatus' : emp.status
      }

      const n = Number(emp && emp.estatus_cv != null ? emp.estatus_cv : null)

      switch (n) {
        case 0:
          return 'sin_estatus'
        case 1:
          return 'edicion'
        case 2:
          return 'enviado'
        case 3:
          return 'aprobado'
        case 4:
          return 'rechazado'
        default:
          return 'sin_estatus'
      }
    },

    statusLabel(status) {
      switch (status) {
        case 'sin_estatus':
        case 'sin_cv':
          return 'Sin estatus'
        case 'edicion':
          return 'En edición'
        case 'enviado':
          return 'Enviado'
        case 'aprobado':
          return 'Aprobado'
        case 'rechazado':
          return 'Rechazado'
        default:
          return 'Sin estatus'
      }
    },

    badgeClass(status) {
      switch (status) {
        case 'sin_estatus':
        case 'sin_cv':
          return 'imss-badge-neutral'
        case 'edicion':
          return 'imss-badge-neutral'
        case 'enviado':
          return 'imss-badge-warning'
        case 'aprobado':
          return 'imss-badge-success'
        case 'rechazado':
          return 'imss-badge-danger'
        default:
          return 'imss-badge-neutral'
      }
    },

    detalleUrl(id) {
      if (id == null) return '#'
      return this._url('/revisor/empleados/' + encodeURIComponent(String(id)))
    },

    pdfEmpleadoUrl(id) {
      if (id == null) return '#'
      return this._url('/revisor/empleados/' + encodeURIComponent(String(id)) + '/pdf')
    },

    descargarPdfPorCurp() {
      if (this.zipDescargando) return

      const curp = String(this.curpDescarga || '').trim().toUpperCase()
      if (curp.length !== 18) return

      window.location.href = this._url('/revisor/pdf/curp/' + encodeURIComponent(curp))
    },

    // ---------------------------------
    // Paginación
    // ---------------------------------
    _syncCurrentPage() {
      if (!Number.isFinite(Number(this.currentPage))) {
        this.currentPage = 1
        return
      }

      if (this.currentPage < 1) {
        this.currentPage = 1
        return
      }

      if (this.currentPage > this.totalPages) {
        this.currentPage = this.totalPages
      }
    },

    onRowsPerPageChange() {
      const n = Number(this.rowsPerPage || 5)
      this.rowsPerPage = this.perPageOptions.includes(n) ? n : 5
      this.currentPage = 1
      this._syncCurrentPage()
    },

    goToPage(page) {
      const target = Number(page || 1)
      if (!Number.isFinite(target)) return

      if (target < 1) {
        this.currentPage = 1
      } else if (target > this.totalPages) {
        this.currentPage = this.totalPages
      } else {
        this.currentPage = target
      }
    },

    goToPreviousPage() {
      if (!this.canGoPrev) return
      this.currentPage = this.currentPageSafe - 1
    },

    goToNextPage() {
      if (!this.canGoNext) return
      this.currentPage = this.currentPageSafe + 1
    },

    // ---------------------------------
    // Modal safe close + unlock
    // ---------------------------------
    _getModalCtor() {
      const b = window && window.bootstrap ? window.bootstrap : null
      return b && b.Modal ? b.Modal : null
    },

    _forceUnlockModals() {
      try {
        document.body.classList.remove('modal-open')
        document.body.style.removeProperty('overflow')
        document.body.style.removeProperty('padding-right')
      } catch (_) {}

      try {
        const backdrops = document.querySelectorAll('.modal-backdrop')
        for (let i = 0; i < backdrops.length; i++) {
          try {
            backdrops[i].remove()
          } catch (_) {}
        }
      } catch (_) {}
    },

    _closeZipModalSafe() {
      const el = document.getElementById('modalZipAprobados')
      if (!el) return

      const Modal = this._getModalCtor()

      try {
        if (Modal && typeof Modal.getInstance === 'function') {
          const inst = Modal.getInstance(el)
          if (inst && typeof inst.hide === 'function') {
            inst.hide()
          }
        } else {
          const btn = el.querySelector('[data-bs-dismiss="modal"]')
          if (btn && typeof btn.click === 'function') {
            btn.click()
          }
        }
      } catch (_) {}

      window.setTimeout(() => this._forceUnlockModals(), 250)
    },

    // ---------------------------------
    // ZIP helpers
    // ---------------------------------
    _isZipContentType(ct = '') {
      const t = String(ct || '').toLowerCase()

      return (
        t.includes('application/zip') ||
        t.includes('application/x-zip-compressed') ||
        t.includes('application/octet-stream') ||
        t.includes('binary/octet-stream')
      )
    },

    _descargaQuery(periodo) {
      const params = new URLSearchParams()

      params.set('modo', periodo.modo)

      if (periodo.modo === 'rango') {
        params.set('fecha_inicio', periodo.fecha_inicio)
        params.set('fecha_fin', periodo.fecha_fin)
      }

      params.set('ejercicio', String(periodo.ejercicio))
      params.set('trimestre', String(periodo.trimestre))
      params.set('_ts', String(Date.now()))

      return params.toString()
    },

    _zipUrl(periodo) {
      const base = this._url('/revisor/pdf/aprobados.zip')

      return base + '?' + this._descargaQuery(periodo)
    },

    _excelUrl(periodo) {
      const base = this._url('/cv/reportes/empleados-terminados')

      return base + '?' + this._descargaQuery(periodo)
    },

    _reporteEstatusUrl() {
      const base = this._url('/cv/reportes/empleados-por-estatus')
      const params = new URLSearchParams()

      if (this.filtros.status) params.set('status', this.filtros.status)
      if (this.filtros.busqueda) params.set('q', this.filtros.busqueda)
      params.set('_ts', String(Date.now()))

      return base + '?' + params.toString()
    },

    _periodoDescargaValido() {
      const modo = this.zipFiltro.modo === 'rango' ? 'rango' : 'trimestre'
      const ejercicio = Number(this.zipFiltro.ejercicio || new Date().getFullYear())
      const trimestre = Number(this.zipFiltro.trimestre || 1)

      if (!(ejercicio >= EJ_MIN && ejercicio <= EJ_MAX)) {
        this.showToast('Ejercicio invalido. Debe estar entre ' + EJ_MIN + ' y ' + EJ_MAX + '.')
        return null
      }

      if (TRIM_OK.indexOf(trimestre) === -1) {
        this.showToast('Trimestre invalido. Debe ser 1, 2, 3 o 4.')
        return null
      }

      if (modo === 'rango') {
        const fechaInicio = String(this.zipFiltro.fecha_inicio || '').trim()
        const fechaFin = String(this.zipFiltro.fecha_fin || '').trim()

        if (!fechaInicio || !fechaFin) {
          this.showToast('Selecciona la fecha inicial y la fecha final.')
          return null
        }

        if (fechaFin < fechaInicio) {
          this.showToast('La fecha final no puede ser menor que la fecha inicial.')
          return null
        }

        return {
          modo,
          fecha_inicio: fechaInicio,
          fecha_fin: fechaFin,
          ejercicio,
          trimestre,
        }
      }

      return { modo, ejercicio, trimestre }
    },

    _setZipProgress(value, texto = null) {
      const n = Number(value || 0)
      this.zipProgreso = Math.max(0, Math.min(100, Math.round(n)))

      if (texto) {
        this.zipProgresoTexto = texto
      }
    },

    _startZipProgress() {
      this._stopZipProgress()

      this._zipStartedAt = Date.now()

      this._setZipProgress(3, 'Preparando solicitud de descarga…')

      this._zipProgressTimer = window.setInterval(() => {
        const elapsed = Date.now() - this._zipStartedAt
        const seconds = elapsed / 1000

        let objetivo = 8
        let texto = 'Conectando con el servidor…'

        if (seconds > 3) {
          objetivo = 18
          texto = 'Buscando CV aprobados del periodo seleccionado…'
        }

        if (seconds > 8) {
          objetivo = 32
          texto = 'Generando documentos PDF…'
        }

        if (seconds > 20) {
          objetivo = 48
          texto = 'Generando PDF y preparando archivos temporales…'
        }

        if (seconds > 45) {
          objetivo = 62
          texto = 'Comprimiendo archivos en ZIP…'
        }

        if (seconds > 75) {
          objetivo = 76
          texto = 'El proceso sigue activo. Son muchos PDF, por favor espera…'
        }

        if (seconds > 120) {
          objetivo = 86
          texto = 'El servidor continúa generando el ZIP. No cierres esta ventana…'
        }

        if (seconds > 180) {
          objetivo = 92
          texto = 'Casi listo. Esperando respuesta del servidor…'
        }

        if (this.zipProgreso < objetivo) {
          this._setZipProgress(this.zipProgreso + 1, texto)
        } else {
          this.zipProgresoTexto = texto
        }
      }, 900)
    },

    _stopZipProgress() {
      try {
        if (this._zipProgressTimer) {
          clearInterval(this._zipProgressTimer)
          this._zipProgressTimer = null
        }
      } catch (_) {}
    },

    async _leerMensajeErrorResponse(resp) {
      try {
        const ct = (resp.headers.get('Content-Type') || resp.headers.get('content-type') || '').toLowerCase()

        if (ct.includes('application/json')) {
          const payload = await resp.json()

          return (
            payload?.message ||
            payload?.mensaje ||
            payload?.error ||
            'No se pudo generar el ZIP.'
          )
        }

        const text = await resp.text()

        if (text && String(text).trim() !== '') {
          const limpio = String(text)
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()

          return limpio || 'No se pudo generar el ZIP.'
        }
      } catch (_) {}

      return 'No se pudo generar el ZIP.'
    },

    _isExcelContentType(ct = '') {
      const t = String(ct || '').toLowerCase()

      return (
        t.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') ||
        t.includes('application/octet-stream') ||
        t.includes('binary/octet-stream')
      )
    },

    _obtenerFilenameDesdeHeaders(resp, defaultFilename = 'aprobados.zip') {
      let filename = defaultFilename

      const cd = resp.headers.get('Content-Disposition') || resp.headers.get('content-disposition') || ''

      const matchUtf8 = cd.match(/filename\*=UTF-8''([^;]+)/i)
      if (matchUtf8 && matchUtf8[1]) {
        try {
          filename = decodeURIComponent(matchUtf8[1].replace(/["']/g, ''))
          return filename
        } catch (_) {}
      }

      const match = cd.match(/filename="([^"]+)"/i)
      if (match && match[1]) {
        filename = match[1]
      }

      return filename
    },

    async descargarReportePorEstatus() {
      if (this.descargaOcupada) return

      this.reporteEstatusDescargando = true

      try {
        const resp = await fetch(this._reporteEstatusUrl(), {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {
            Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream, application/json, text/plain, */*',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })

        if (!resp.ok) {
          const msgServidor = await this._leerMensajeErrorResponse(resp)

          if (resp.status === 404) {
            this.showToast(msgServidor || 'No hay empleados para exportar con los filtros seleccionados.')
          } else if (resp.status === 422) {
            this.showToast(msgServidor || 'Parámetros inválidos para el reporte.')
          } else {
            this.showToast(msgServidor || 'No se pudo descargar el reporte por estatus.')
          }

          return
        }

        const ct = (resp.headers.get('Content-Type') || resp.headers.get('content-type') || '').toLowerCase()

        if (ct.includes('application/json') || ct.includes('text/plain') || ct.includes('text/html')) {
          const msg = await this._leerMensajeErrorResponse(resp)
          this.showToast(msg || 'El servidor no devolvió un archivo Excel válido.')
          return
        }

        if (ct && !this._isExcelContentType(ct)) {
          this.showToast('El servidor respondió, pero no devolvió un archivo Excel válido.')
          return
        }

        const blob = await resp.blob()

        if (!blob || blob.size === 0) {
          this.showToast('El reporte por estatus se generó vacío.')
          return
        }

        try {
          const headBuf = await blob.slice(0, 2).arrayBuffer()
          const sig = new Uint8Array(headBuf)
          const isZipBasedXlsx = sig[0] === 0x50 && sig[1] === 0x4b

          if (!isZipBasedXlsx) {
            this.showToast('El archivo recibido no parece ser un Excel válido.')
            return
          }
        } catch (_) {}

        const filename = this._obtenerFilenameDesdeHeaders(resp, 'reporte_cv_por_estatus.xlsx')
        const objectUrl = window.URL.createObjectURL(blob)

        const a = document.createElement('a')
        a.href = objectUrl
        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()

        window.URL.revokeObjectURL(objectUrl)

        this.showToast('Reporte por estatus generado correctamente. La descarga debe iniciar automáticamente.', 'ok')
      } catch (e) {
        console.error('Error al descargar reporte por estatus:', e)
        this.showToast('No se pudo descargar el reporte por estatus. Puede ser un error de red o tiempo de espera.')
      } finally {
        window.setTimeout(() => {
          this.reporteEstatusDescargando = false
          this._forceUnlockModals()
        }, 500)
      }
    },

    async descargarReporteExcel() {
      if (this.descargaOcupada) return

      const periodo = this._periodoDescargaValido()
      if (!periodo) return

      this.excelDescargando = true
      this._closeZipModalSafe()

      try {
        const resp = await fetch(this._excelUrl(periodo), {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {
            Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream, application/json, text/plain, */*',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })

        if (!resp.ok) {
          const msgServidor = await this._leerMensajeErrorResponse(resp)

          if (resp.status === 404) {
            this.showToast(msgServidor || 'No hay CV aprobados para exportar en el periodo seleccionado.')
          } else if (resp.status === 422) {
            this.showToast(msgServidor || 'Parámetros inválidos. Verifica el periodo seleccionado.')
          } else {
            this.showToast(msgServidor || 'No se pudo descargar el reporte Excel.')
          }

          return
        }

        const ct = (resp.headers.get('Content-Type') || resp.headers.get('content-type') || '').toLowerCase()

        if (ct.includes('application/json') || ct.includes('text/plain') || ct.includes('text/html')) {
          const msg = await this._leerMensajeErrorResponse(resp)
          this.showToast(msg || 'El servidor no devolvió un archivo Excel válido.')
          return
        }

        if (ct && !this._isExcelContentType(ct)) {
          this.showToast('El servidor respondió, pero no devolvió un archivo Excel válido.')
          return
        }

        const blob = await resp.blob()

        if (!blob || blob.size === 0) {
          this.showToast('El reporte Excel se generó vacío.')
          return
        }

        try {
          const headBuf = await blob.slice(0, 2).arrayBuffer()
          const sig = new Uint8Array(headBuf)
          const isZipBasedXlsx = sig[0] === 0x50 && sig[1] === 0x4b

          if (!isZipBasedXlsx) {
            this.showToast('El archivo recibido no parece ser un Excel válido.')
            return
          }
        } catch (_) {}

        const filename = this._obtenerFilenameDesdeHeaders(resp, 'reporte_cv_aprobados.xlsx')
        const objectUrl = window.URL.createObjectURL(blob)

        const a = document.createElement('a')
        a.href = objectUrl
        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()

        window.URL.revokeObjectURL(objectUrl)

        this.showToast('Reporte Excel generado correctamente. La descarga debe iniciar automáticamente.', 'ok')
      } catch (e) {
        console.error('Error al descargar Excel:', e)
        this.showToast('No se pudo descargar el reporte Excel. Puede ser un error de red o tiempo de espera.')
      } finally {
        window.setTimeout(() => {
          this.excelDescargando = false
          this._forceUnlockModals()
        }, 500)
      }
    },

    async _blobDesdeResponseConProgreso(resp) {
      const ct = resp.headers.get('Content-Type') || resp.headers.get('content-type') || 'application/zip'
      const contentLength = Number(resp.headers.get('Content-Length') || resp.headers.get('content-length') || 0)

      if (!resp.body || !contentLength) {
        this._setZipProgress(96, 'Descargando ZIP generado…')
        return await resp.blob()
      }

      const reader = resp.body.getReader()
      const chunks = []
      let recibido = 0

      while (true) {
        const { done, value } = await reader.read()

        if (done) {
          break
        }

        chunks.push(value)
        recibido += value.length

        const porcentajeDescarga = Math.round((recibido / contentLength) * 8)
        const progreso = Math.min(99, 92 + porcentajeDescarga)

        this._setZipProgress(progreso, 'Descargando ZIP generado…')
      }

      return new Blob(chunks, { type: ct })
    },

    async descargarZipAprobados() {
      if (this.descargaOcupada) return

      const periodo = this._periodoDescargaValido()
      if (!periodo) return

      this.zipDescargando = true
      this._startZipProgress()

      const url = this._zipUrl(periodo)

      this._closeZipModalSafe()

      try {
        const resp = await fetch(url, {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {
            Accept: 'application/zip, application/octet-stream, application/json, text/plain, */*',
            'X-Requested-With': 'XMLHttpRequest',
          },
        })

        if (!resp.ok) {
          const msgServidor = await this._leerMensajeErrorResponse(resp)

          this._setZipProgress(100, 'No se pudo generar el ZIP.')

          if (resp.status === 404) {
            this.showToast(msgServidor || 'No hay CV aprobados para exportar en el periodo seleccionado.')
          } else if (resp.status === 422) {
            this.showToast(msgServidor || 'Parámetros inválidos. Verifica el periodo seleccionado.')
          } else if (resp.status === 500) {
            this.showToast(msgServidor || 'No se pudo generar el ZIP. Revisa el servidor.')
          } else {
            this.showToast(msgServidor || 'No se pudo descargar el ZIP.')
          }

          return
        }

        if (resp.status === 204) {
          this._setZipProgress(100, 'No hay CV aprobados para exportar.')
          this.showToast('No hay CV aprobados para exportar en el periodo seleccionado.')
          return
        }

        const ct = (resp.headers.get('Content-Type') || resp.headers.get('content-type') || '').toLowerCase()

        if (ct.includes('application/json') || ct.includes('text/plain') || ct.includes('text/html')) {
          const msg = await this._leerMensajeErrorResponse(resp)
          this._setZipProgress(100, 'El servidor no devolvió un ZIP válido.')
          this.showToast(msg || 'El servidor no devolvió un archivo ZIP válido.')
          return
        }

        if (ct && !this._isZipContentType(ct)) {
          this._setZipProgress(100, 'El servidor no devolvió un ZIP válido.')
          this.showToast('El servidor respondió, pero no devolvió un archivo ZIP válido.')
          return
        }

        this._setZipProgress(92, 'ZIP generado. Iniciando descarga…')

        const blob = await this._blobDesdeResponseConProgreso(resp)

        if (!blob || blob.size === 0) {
          this._setZipProgress(100, 'El ZIP se generó vacío.')
          this.showToast('El ZIP se generó vacío. Revisa si existen CV aprobados en el periodo.')
          return
        }

        try {
          const headBuf = await blob.slice(0, 2).arrayBuffer()
          const sig = new Uint8Array(headBuf)
          const isPK = sig[0] === 0x50 && sig[1] === 0x4b

          if (!isPK) {
            this._setZipProgress(100, 'El archivo recibido no es un ZIP válido.')
            this.showToast('El archivo recibido no parece ser un ZIP válido.')
            return
          }
        } catch (_) {}

        const filename = this._obtenerFilenameDesdeHeaders(resp)
        const objectUrl = window.URL.createObjectURL(blob)

        const a = document.createElement('a')
        a.href = objectUrl
        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()

        window.URL.revokeObjectURL(objectUrl)

        this._setZipProgress(100, 'ZIP generado correctamente.')
        this.showToast('ZIP generado correctamente. La descarga debe iniciar automáticamente.', 'ok')
      } catch (e) {
        console.error('Error al descargar ZIP:', e)

        this._setZipProgress(100, 'No se pudo descargar el ZIP.')

        this.showToast(
          'No se pudo descargar el ZIP. Puede ser un error de red, tiempo de espera o que el servidor tardó demasiado en generar los PDF.'
        )
      } finally {
        window.setTimeout(() => {
          this._stopZipProgress()
          this.zipDescargando = false
          this.zipProgreso = 0
          this.zipProgresoTexto = 'Preparando solicitud…'
          this._forceUnlockModals()
        }, 900)
      }
    },

    // ---------------------------------
    // Data
    // ---------------------------------
    scheduleCargarEmpleados() {
      try {
        if (this._debounceTimer) clearTimeout(this._debounceTimer)
      } catch (_) {}

      this._debounceTimer = setTimeout(() => {
        this.cargarEmpleados()
      }, this._debounceMs)
    },

    async cargarEmpleados() {
      const reqId = ++this._empReqId

      try {
        this.loadingEmpleados = true

        const apiUrl = this._url('/api/revisor/empleados')

        const { data } = await axios.get(apiUrl, {
          params: {
            q: this.filtros.busqueda || undefined,
            status: this.filtros.status || undefined,
          },
        })

        if (reqId !== this._empReqId) return

        this.empleados = Array.isArray(data) ? data : []

        this.$nextTick(() => {
          this._syncCurrentPage()
        })
      } catch (e) {
        if (reqId !== this._empReqId) return

        console.error('Error al cargar empleados:', e)

        this.empleados = []
        this.showToast('No se pudo cargar la lista de empleados.')

        this.$nextTick(() => {
          this._syncCurrentPage()
        })
      } finally {
        if (reqId === this._empReqId) {
          this.loadingEmpleados = false
        }
      }
    },

    _cleanupTimers() {
      try {
        if (this._debounceTimer) clearTimeout(this._debounceTimer)
      } catch (_) {}

      try {
        this._stopZipProgress()
      } catch (_) {}

      try {
        if (this._toastInstance) {
          this._toastInstance.dispose()
          this._toastInstance = null
        }
      } catch (_) {}
    },
  },

  watch: {
    filtros: {
      deep: true,
      handler() {
        this.currentPage = 1
        this.scheduleCargarEmpleados()
      },
    },
  },

  mounted() {
    this.cargarEmpleados()
  },

  beforeUnmount() {
    this._cleanupTimers()
  },

  beforeDestroy() {
    this._cleanupTimers()
  },
}
</script>

<style scoped>
.imss-theme {
  --imss-green: #006341;
  --imss-green-2: #0b7a53;
  --imss-wine: #691c32;
  --imss-red: #9f2241;
  --imss-gold: #bc955c;
  --imss-ink: #10312b;
  --imss-muted: #6b7280;
  --imss-bg: #f6f8f7;
  --imss-border: #e5e7eb;
  --imss-soft: #eef5f2;
}

.imss-card {
  border: 1px solid var(--imss-border);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 18px 45px rgba(16, 49, 43, 0.08);
}

.imss-card-header {
  position: relative;
  background:
    linear-gradient(90deg, rgba(16, 49, 43, 0.05), rgba(188, 149, 92, 0.08)),
    #ffffff;
  border-bottom: 1px solid var(--imss-border);
  padding: 22px 20px 20px;
}

.imss-card-header::before {
  content: "";
  position: absolute;
  inset: 0 0 auto;
  height: 5px;
  background: linear-gradient(90deg, var(--imss-wine), var(--imss-red), var(--imss-green), var(--imss-gold));
}

.imss-kicker {
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--imss-muted);
  margin-bottom: 4px;
}

.imss-title {
  color: var(--imss-ink);
  font-weight: 800;
  font-size: 1.22rem;
}

.imss-subtitle {
  color: var(--imss-muted);
  font-size: 0.9rem;
  max-width: 60ch;
}

.imss-header-layout {
  display: grid;
  grid-template-columns: minmax(280px, 1fr) auto;
  gap: 18px;
  align-items: center;
  width: 100%;
}

.imss-actions {
  display: flex;
  gap: 10px;
  align-items: stretch;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.imss-action-group {
  min-height: auto;
  padding: 10px 12px;
  border: 1px solid rgba(16, 49, 43, 0.11);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 10px 24px rgba(16, 49, 43, 0.05);
}

.imss-action-group-period {
  width: 260px;
}

.imss-action-group-list {
  width: 178px;
}

.imss-action-group-import {
  width: 178px;
}

.imss-action-title {
  color: var(--imss-ink);
  font-size: 0.72rem;
  font-weight: 800;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  white-space: nowrap;
}

.imss-action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.imss-download-grid {
  display: flex;
  flex-wrap: nowrap;
}

.imss-report-action {
  display: flex;
}

.imss-action-btn {
  min-width: 108px;
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid transparent;
  border-radius: 9px;
  padding: 7px 10px;
  font: inherit;
  text-align: center;
  cursor: pointer;
  transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, background-color .15s ease;
}

.imss-action-group-list .imss-action-btn {
  width: 100%;
}

.imss-action-group-import .imss-action-btn {
  width: 100%;
}

.imss-action-btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px rgba(16, 49, 43, 0.13);
}

.imss-action-btn:disabled {
  cursor: not-allowed;
  opacity: 0.68;
}

.imss-action-btn-primary {
  background: var(--imss-green);
  border-color: var(--imss-green);
  color: #ffffff;
}

.imss-action-btn-secondary {
  background: #ffffff;
  border-color: rgba(0, 99, 65, 0.35);
  color: var(--imss-green);
}

.imss-action-btn-soft {
  background: #f4faf7;
  border-color: rgba(0, 99, 65, 0.22);
  color: var(--imss-ink);
}

.imss-action-icon {
  width: 28px;
  min-width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.18);
  font-size: 0.98rem;
}

.imss-action-btn-secondary .imss-action-icon,
.imss-action-btn-soft .imss-action-icon {
  background: rgba(0, 99, 65, 0.09);
  color: var(--imss-green);
}

.imss-action-text {
  min-width: 0;
  display: flex;
  flex-direction: row;
  line-height: 1.15;
}

.imss-action-label {
  font-size: 0.82rem;
  font-weight: 800;
  white-space: nowrap;
}

.imss-action-subtitle {
  display: none;
}

.imss-curp-box {
  width: 286px;
  min-height: auto;
  padding: 10px 12px;
  border: 1px solid rgba(16, 49, 43, 0.11);
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 10px 24px rgba(16, 49, 43, 0.05);
}

.imss-curp-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.imss-curp-head span {
  color: var(--imss-muted);
  font-size: 0.72rem;
  font-weight: 700;
}

.imss-curp-row {
  display: grid;
  grid-template-columns: minmax(150px, 1fr) auto;
  gap: 8px;
  align-items: center;
}

.imss-curp-button {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: 1px solid rgba(0, 99, 65, 0.38);
  border-radius: 9px;
  background: #ffffff;
  color: var(--imss-green);
  font-size: 0.82rem;
  font-weight: 800;
  padding: 7px 10px;
  transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
}

.imss-curp-button:not(:disabled):hover {
  background: var(--imss-green);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(0, 99, 65, 0.16);
}

.imss-curp-button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.imss-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--imss-ink);
  margin-bottom: 6px;
}

.imss-help {
  margin-top: 6px;
  font-size: 0.78rem;
  color: #b91c1c;
}

.imss-filters {
  background: linear-gradient(180deg, #f9fbfa 0%, var(--imss-bg) 100%);
  border-bottom: 1px solid var(--imss-border);
  padding: 16px 18px;
}

.imss-summary-strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.imss-summary-item {
  background: #ffffff;
  border: 1px solid var(--imss-border);
  border-radius: 10px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  box-shadow: 0 8px 18px rgba(16, 49, 43, 0.04);
}

.imss-summary-item span {
  color: var(--imss-muted);
  font-size: 0.82rem;
  font-weight: 600;
}

.imss-summary-item strong {
  color: var(--imss-ink);
  font-size: 1.12rem;
  font-variant-numeric: tabular-nums;
}

.imss-input-wrap {
  position: relative;
}

.imss-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.9rem;
  opacity: 0.75;
}

.imss-input,
.imss-select {
  border-radius: 8px;
  border-color: var(--imss-border);
  min-height: 36px;
}

.imss-input-with-icon {
  padding-left: 34px;
}

.imss-input:focus,
.imss-select:focus {
  border-color: rgba(0, 99, 65, 0.55);
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.12);
}

.imss-table-wrap {
  background: #ffffff;
}

.imss-import-summary {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px;
}

.imss-import-summary-item {
  border: 1px solid var(--imss-border);
  border-radius: 8px;
  background: #ffffff;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.imss-import-summary-item span {
  color: var(--imss-muted);
  font-size: 0.78rem;
  font-weight: 700;
}

.imss-import-summary-item strong {
  color: var(--imss-ink);
  font-size: 1.05rem;
  font-variant-numeric: tabular-nums;
}

.imss-import-summary-item.is-ok {
  border-color: rgba(0, 99, 65, 0.25);
  background: #f1f8f5;
}

.imss-import-summary-item.is-dup {
  border-color: rgba(180, 83, 9, 0.25);
  background: #fff7ed;
}

.imss-import-summary-item.is-bad {
  border-color: rgba(159, 34, 65, 0.25);
  background: #fff1f2;
}

.imss-import-summary-item.is-inserted {
  border-color: rgba(14, 116, 144, 0.25);
  background: #ecfeff;
}

.imss-import-filter {
  width: 180px;
}

.imss-import-preview-wrap {
  max-height: 380px;
  border: 1px solid var(--imss-border);
  border-radius: 8px;
  background: #ffffff;
}

.imss-import-preview-wrap thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #f8fafc;
  color: var(--imss-ink);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.imss-import-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 86px;
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 0.72rem;
  font-weight: 800;
  border: 1px solid var(--imss-border);
  color: var(--imss-muted);
  background: #f9fafb;
}

.imss-import-badge.is-ok {
  border-color: rgba(0, 99, 65, 0.28);
  color: #006341;
  background: #eef8f3;
}

.imss-import-badge.is-dup {
  border-color: rgba(180, 83, 9, 0.28);
  color: #92400e;
  background: #fff7ed;
}

.imss-import-badge.is-bad {
  border-color: rgba(159, 34, 65, 0.28);
  color: #9f2241;
  background: #fff1f2;
}

.imss-import-badge.is-inserted {
  border-color: rgba(14, 116, 144, 0.28);
  color: #0e7490;
  background: #ecfeff;
}

.imss-table thead th {
  background: var(--imss-ink);
  color: #ffffff;
  font-size: 0.76rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  border-bottom: 0;
  padding: 13px 14px;
}

.imss-table tbody td {
  padding: 15px 14px;
  border-color: var(--imss-border);
}

.imss-table tbody tr:hover {
  background: var(--imss-soft);
}

.imss-table tbody tr {
  transition: background-color .15s ease;
}

.imss-name {
  color: var(--imss-ink);
  line-height: 1.2;
}

.imss-code {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 0.78rem;
}

.imss-row-btn {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 0.78rem;
  font-weight: 800;
  text-decoration: none;
  line-height: 1;
  transition: background-color .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.imss-row-btn-detail {
  border: 1px solid rgba(0, 99, 65, 0.45);
  color: var(--imss-green);
  background: #ffffff;
}

.imss-row-btn-pdf {
  border: 1px solid rgba(0, 99, 65, 0.30);
  color: var(--imss-green);
  background: #f4faf7;
}

.imss-row-btn:hover,
.imss-row-btn:focus {
  border-color: var(--imss-green);
  background: var(--imss-green);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(0, 99, 65, 0.14);
}

.imss-row-btn.disabled {
  pointer-events: none;
  opacity: 0.48;
}

.imss-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.78rem;
  font-weight: 700;
  border: 1px solid transparent;
}

.imss-badge-neutral {
  background: #f3f4f6;
  color: #374151;
  border-color: #e5e7eb;
}

.imss-badge-warning {
  background: #fff7ed;
  color: #9a3412;
  border-color: #fed7aa;
}

.imss-badge-success {
  background: #ecfdf5;
  color: #065f46;
  border-color: #a7f3d0;
}

.imss-badge-danger {
  background: #fef2f2;
  color: #991b1b;
  border-color: #fecaca;
}

.imss-footer {
  background: #fbfcfc;
  border-top: 1px solid var(--imss-border);
  padding: 14px 18px;
}

.imss-footer-grid {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 12px;
  align-items: center;
}

.imss-footer-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.imss-footer-center {
  text-align: center;
}

.imss-footer-right {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.imss-footer-label {
  font-size: 0.82rem;
  color: var(--imss-muted);
  font-weight: 600;
}

.imss-select-footer {
  width: 84px;
  min-width: 84px;
}

.imss-page-group {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.imss-page-btn {
  min-width: 38px;
  border-radius: 8px;
  font-weight: 700;
  min-height: 32px;
}

.imss-page-btn-active {
  background: var(--imss-green) !important;
  border: 1px solid var(--imss-green) !important;
  color: #ffffff !important;
  box-shadow: 0 8px 16px rgba(0, 99, 65, 0.14);
}

.imss-btn-fixed {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  min-height: 34px;
}

.btn-imss {
  background: var(--imss-green) !important;
  border-color: var(--imss-green) !important;
  color: #ffffff !important;
  border-radius: 8px;
  font-weight: 700;
  padding: 8px 12px;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.btn-imss:hover,
.btn-imss:focus,
.btn-imss:active {
  background: var(--imss-green-2) !important;
  border-color: var(--imss-green-2) !important;
  color: #ffffff !important;
  opacity: 1 !important;
}

.btn-imss:focus {
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.14) !important;
}

.btn-outline-imss {
  border-color: rgba(0, 99, 65, 0.55) !important;
  color: var(--imss-green) !important;
  border-radius: 8px;
  font-weight: 700;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
}

.btn-outline-imss:hover,
.btn-outline-imss:focus,
.btn-outline-imss:active {
  background: var(--imss-green) !important;
  border-color: var(--imss-green) !important;
  color: #ffffff !important;
  opacity: 1 !important;
}

.btn-outline-imss:focus {
  box-shadow: 0 0 0 3px rgba(0, 99, 65, 0.14) !important;
}

.imss-loading-backdrop {
  position: fixed;
  inset: 0;
  z-index: 3500;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(3px);
}

.imss-loading-card {
  width: min(460px, 100%);
  background: #ffffff;
  border: 1px solid var(--imss-border);
  border-radius: 16px;
  box-shadow: 0 18px 50px rgba(16, 49, 43, 0.18);
  padding: 24px 22px;
  text-align: center;
}

.imss-loading-spinner {
  width: 52px;
  height: 52px;
  margin: 0 auto 14px;
  border: 4px solid #e5e7eb;
  border-top-color: var(--imss-green);
  border-radius: 999px;
  animation: imss-spin 0.8s linear infinite;
}

.imss-loading-title {
  color: var(--imss-ink);
  font-weight: 800;
  font-size: 1.05rem;
  margin-bottom: 6px;
}

.imss-loading-text {
  color: var(--imss-muted);
  font-size: 0.9rem;
  line-height: 1.45;
}

.imss-loading-subtext {
  color: #9a3412;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 10px;
  margin-top: 12px;
  padding: 8px 10px;
  font-size: 0.82rem;
  font-weight: 600;
}

.imss-progress {
  border-radius: 999px;
  background: #e5e7eb;
  overflow: hidden;
}

.imss-progress-bar {
  background: var(--imss-green);
  font-size: 0.72rem;
  font-weight: 800;
  transition: width 0.35s ease;
}

@keyframes imss-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 992px) {
  .imss-header-layout {
    grid-template-columns: 1fr;
  }

  .imss-actions {
    grid-template-columns: 1fr;
    width: 100%;
  }

  .imss-footer-grid {
    grid-template-columns: 1fr;
  }

  .imss-footer-center {
    text-align: left;
  }

  .imss-footer-right {
    justify-content: flex-start;
  }

  .imss-summary-strip {
    grid-template-columns: 1fr;
  }

  .imss-import-summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .imss-curp-box {
    width: 100%;
    min-width: 0;
  }

  .imss-actions {
    width: 100%;
  }

  .imss-action-group {
    width: 100%;
  }

  .imss-action-buttons {
    display: grid;
    grid-template-columns: 1fr;
  }

  .imss-download-grid {
    grid-template-columns: 1fr;
  }

  .imss-curp-row {
    grid-template-columns: 1fr;
  }

  .imss-action-btn,
  .imss-curp-button {
    width: 100%;
  }

  .imss-import-summary {
    grid-template-columns: 1fr;
  }

  .imss-import-filter {
    width: 100%;
  }
}
</style>
