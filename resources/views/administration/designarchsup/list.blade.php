{{-- resources/views/administration/designarchsup/list.blade.php --}}
<x-pages.page-app>
  {{-- Título --}}
  <x-title.title-header title="Diseño archivo de lo que verá el supervisor" />

  <div class="page-body theme-gray">
    <div class="container-xl">

      {{-- Card principal con botón Visualizar --}}
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col">
              <h3 class="card-title mb-1">Previsualización de documento</h3>
              <div class="text-secondary">Diseño de modal con PDF, notas y estatus</div>
            </div>
            <div class="col-auto">
              <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalVisualizarPDF"
              >
                <i class="ti ti-eye me-1"></i> Visualizar
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- Modal: tamaño pequeño (modal-lg), centrado, tema claro --}}
      <div class="modal modal-blur fade" id="modalVisualizarPDF" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content border-0" style="background-color:#f8f9fa;"> {{-- cuerpo gris MUY claro --}}
            <div class="modal-header" style="background-color:#ffffff; color:#212529; border-bottom:1px solid #eceeef;">
              <h3 class="modal-title" style="color:#212529;">
                <i class="ti ti-file-description me-2"></i> Vista previa del archivo (PDF)
              </h3>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <div class="row g-3">
                {{-- Columna izquierda: Observaciones y Estatus --}}
                <div class="col-12 col-lg-4">
                  <div class="card h-100 shadow-sm card-gray-border">
                    <div class="card-header" style="background:#fff;">
                      <h3 class="card-title mb-0" style="color:#212529;">Información del supervisor</h3>
                    </div>
                    <div class="card-body">
                      <div class="mb-3">
                        <label class="form-label required">Observaciones</label>
                        <textarea class="form-control" rows="6" placeholder="Escribe tus observaciones..."></textarea>
                        <small class="form-hint">Agrega comentarios o hallazgos relevantes.</small>
                      </div>

                      <div class="mb-3">
                        <label class="form-label required">Estatus</label>
                        <select class="form-select">
                          <option value="" selected>Selecciona un estatus</option>
                          <option value="pendiente">Pendiente</option>
                          <option value="validado">Validado</option>
                          <option value="rechazado">Rechazado</option>
                        </select>
                      </div>

                      <div class="d-grid">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                          <i class="ti ti-check me-1"></i> Guardar y cerrar
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- Columna derecha: Visor PDF (sin PDF.js) + controles abajo --}}
                <div class="col-12 col-lg-8">
                  <div class="card shadow-sm card-gray-border">
                    <div class="card-header d-flex align-items-center justify-content-between" style="background:#fff;">
                      <h3 class="card-title mb-0" style="color:#212529;">Documento PDF</h3>
                      <span class="badge bg-gray-soft text-dark">Visor integrado</span>
                    </div>
                    <div class="card-body">
                      {{-- Viewport del PDF con scroll; dentro va el contenedor escalable --}}
                      <div id="pdfViewport" class="pdf-viewport border rounded">
                        <div id="pdfScaleContainer" class="pdf-scale">
                          <iframe
                            class="w-100 h-100"
                            style="border:0;"
                            src="{{ asset('pdfs/manual_actualizado_rclone_onedrive.pdf') }}#toolbar=1&view=FitH"
                            title="Previsualización PDF"
                          ></iframe>
                        </div>
                      </div>

                      {{-- Controles de zoom ABAJO del PDF (centrados) --}}
                      <div class="zoom-controls-bottom d-flex justify-content-center align-items-center gap-2 mt-3">
                        <button id="btnZoomOut" type="button" class="btn btn-zoom btn-icon" title="Reducir">
                          <i class="ti ti-zoom-out"></i>
                        </button>
                        <span id="zoomPct" class="zoom-badge-gray"></span>
                        <button id="btnZoomIn" type="button" class="btn btn-zoom btn-icon" title="Ampliar">
                          <i class="ti ti-zoom-in"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div> {{-- /row --}}
            </div> {{-- /modal-body --}}
          </div>
        </div>
      </div> {{-- /modal --}}
    </div>
  </div>

  {{-- Estilos (paleta gris clara y tipografía oscura) --}}
  <style>
    /* Botón primario y acentos en gris medio */
    .theme-gray .btn-primary {
      background-color: #98989a;
      border-color: #98989a;
      color: #fff;
    }
    .theme-gray .btn-primary:hover {
      background-color: #848487;
      border-color: #848487;
      color: #fff;
    }

    /* Bordes y badges suaves */
    .card-gray-border { border: 1px solid #E4E4E7; }
    .bg-gray-soft { background-color: #F2F2F3 !important; }

    /* Asterisco requerido en gris */
    .theme-gray .form-label.required::after {
      content: " *";
      color: #6F6F73;
      font-weight: 600;
    }

    /* Visor PDF con zoom visual */
    .pdf-viewport {
      height: 60vh;
      overflow: auto;
      background: #F8FAFC;           /* muy claro */
      border-color: #E4E4E7 !important;
    }
    .pdf-scale {
      width: 100%;
      height: 100%;
      transform-origin: 0 0;         /* crecer desde esquina sup-izq */
    }

    /* Controles de zoom (abajo, centrados) */
    .btn-icon {
      width: 40px;
      height: 40px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: .5rem;
      font-size: 1.05rem;
    }
    .btn-zoom {
      background-color: #E4E4E7;
      border-color: #E4E4E7;
      color: #3D3D3D;
    }
    .btn-zoom:hover {
      background-color: #848487;
      border-color: #848487;
      color: #fff;
    }
    .zoom-badge-gray {
      display: inline-block;
      padding: .2rem .6rem;
      border-radius: .5rem;
      background: #F2F2F3;
      color: #3D3D3D;
      font-size: .9rem;
      font-weight: 600;
      min-width: 3.2rem;
      text-align: center;
      border: 1px solid #E4E4E7;
    }
  </style>

  {{-- JS del zoom visual (sin PDF.js) --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const scaleContainer = document.getElementById('pdfScaleContainer');
      const btnIn  = document.getElementById('btnZoomIn');
      const btnOut = document.getElementById('btnZoomOut');
      const pctEl  = document.getElementById('zoomPct');

      let scale = 1.0;           // 100%
      const MIN = 0.5;           // 50%
      const MAX = 2.0;           // 200%
      const STEP = 0.1;

      function applyScale() {
        scaleContainer.style.transform = `scale(${scale})`;
        // Expandimos ancho/alto para que el scroll coincida con el zoom
        scaleContainer.style.width  = `${scale * 100}%`;
        scaleContainer.style.height = `${scale * 100}%`;
        pctEl.textContent = Math.round(scale * 100) + '%';
      }

      btnIn.addEventListener('click', () => {
        scale = Math.min(MAX, +(scale + STEP).toFixed(2));
        applyScale();
      });

      btnOut.addEventListener('click', () => {
        scale = Math.max(MIN, +(scale - STEP).toFixed(2));
        applyScale();
      });

      // Inicial
      applyScale();
    });
  </script>
</x-pages.page-app>






