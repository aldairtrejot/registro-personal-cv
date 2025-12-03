{{-- resources/views/administration/designarch/list.blade.php --}}
<x-pages.page-app>
  <x-title.title-header title="Diseño de subir archivos" />

  <div class="page-body">
    <div class="container-xl">

      {{-- Alertas de resultado --}}
      @if (session('design_success'))
        <div class="alert alert-success alert-dismissible" role="alert">
          <div class="d-flex">
            <div><i class="ti ti-check me-2"></i>{{ session('design_success') }}</div>
          </div>
          <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger" role="alert">
          <i class="ti ti-alert-triangle me-2"></i>Corrige los errores e inténtalo de nuevo.
          <ul class="mt-2 mb-0 small">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Instrucciones --}}
      <div class="card shadow-sm mb-4">
        <div class="card-header py-2">
          <h3 class="card-title mb-0 fs-6">Instrucciones</h3>
        </div>
        <div class="card-body p-3">
          <ul class="list-unstyled small mb-0">
            <li class="mb-2 d-flex"><i class="ti ti-check me-2 text-success"></i> Usa nombres claros y descriptivos (p. ej., <span class="ms-1 text-secondary">CURP_Nombre.pdf</span>)</li>
            <li class="mb-2 d-flex"><i class="ti ti-check me-2 text-success"></i> Evita caracteres especiales en nombres de archivo</li>
            <li class="mb-2 d-flex"><i class="ti ti-check me-2 text-success"></i> Tamaño sugerido menor a <span class="ms-1 text-secondary">5 MB</span></li>
            <li class="mb-2 d-flex"><i class="ti ti-check me-2 text-success"></i> Prefiere <span class="ms-1 text-secondary">PDF</span> para documentos formales</li>
          </ul>
          <div class="alert alert-info mt-3 small p-2" role="alert">
            <i class="ti ti-info-circle me-1"></i> Esta es una maqueta de diseño funcional para subir archivos.
          </div>
        </div>
      </div>

      {{-- Formulario --}}
      <form action="{{ route('design.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Grid responsive: 1 col (xs), 2 cols (sm), 4 cols (lg) --}}
        <div class="row g-3">

          {{-- =============== CURP =============== --}}
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
              <div class="card-header py-2 d-flex align-items-center">
                <i class="ti ti-id me-2"></i>
                <h4 class="card-title mb-0 fs-6">CURP <span class="text-danger">*</span></h4>
              </div>
              <div class="card-body p-2">
                {{-- Vacío --}}
                <label for="file_curp" id="ui_curp_empty"
                       class="dropzone dz-clickable border-2 border-dashed rounded-2 p-2 text-center cursor-pointer w-100"
                       style="min-height: 90px; display:flex; align-items:center; justify-content:center;">
                  <div>
                    <i class="ti ti-cloud-upload text-secondary"></i>
                    <div class="small">Seleccionar archivo</div>
                  </div>
                </label>

                {{-- Cargado --}}
                <div id="ui_curp_loaded" class="d-none">
                  <div class="p-2 bg-success-lt rounded d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                      <i class="ti ti-folder-check text-success fs-5 me-2"></i>
                      <div class="min-w-0" style="min-width:0;">
                        <div class="fw-bold text-success small">Archivo cargado</div>
                        <div id="file_curp_name" class="text-secondary small text-truncate" style="max-width:100%;">—</div>
                      </div>
                    </div>
                    <div class="btn-group btn-group-sm flex-shrink-0 ms-2">
                      <button type="button" class="btn btn-link text-secondary px-1" title="Ver"><i class="ti ti-eye"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Descargar"><i class="ti ti-download"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Eliminar"><i class="ti ti-trash"></i></button>
                    </div>
                  </div>
                </div>

                <input type="file" name="file_curp" id="file_curp" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file_curp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

          {{-- =============== INE =============== --}}
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
              <div class="card-header py-2 d-flex align-items-center">
                <i class="ti ti-id me-2"></i>
                <h4 class="card-title mb-0 fs-6">INE <span class="text-danger">*</span></h4>
              </div>
              <div class="card-body p-2">
                {{-- Vacío --}}
                <label for="file_ine" id="ui_ine_empty"
                       class="dropzone dz-clickable border-2 border-dashed rounded-2 p-2 text-center cursor-pointer w-100"
                       style="min-height: 90px; display:flex; align-items:center; justify-content:center;">
                  <div>
                    <i class="ti ti-cloud-upload text-secondary"></i>
                    <div class="small">Seleccionar archivo</div>
                  </div>
                </label>

                {{-- Cargado --}}
                <div id="ui_ine_loaded" class="d-none">
                  <div class="p-2 bg-success-lt rounded d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                      <i class="ti ti-folder-check text-success fs-5 me-2"></i>
                      <div class="min-w-0" style="min-width:0;">
                        <div class="fw-bold text-success small">Archivo cargado</div>
                        <div id="file_ine_name" class="text-secondary small text-truncate" style="max-width:100%;">—</div>
                      </div>
                    </div>
                    <div class="btn-group btn-group-sm flex-shrink-0 ms-2">
                      <button type="button" class="btn btn-link text-secondary px-1" title="Ver"><i class="ti ti-eye"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Descargar"><i class="ti ti-download"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Eliminar"><i class="ti ti-trash"></i></button>
                    </div>
                  </div>
                </div>

                <input type="file" name="file_ine" id="file_ine" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file_ine') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

          {{-- =============== CÉDULA =============== --}}
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
              <div class="card-header py-2 d-flex align-items-center">
                <i class="ti ti-id me-2"></i>
                <h4 class="card-title mb-0 fs-6">CÉDULA <span class="text-danger">*</span></h4>
              </div>
              <div class="card-body p-2">
                {{-- Vacío --}}
                <label for="file_cedula" id="ui_cedula_empty"
                       class="dropzone dz-clickable border-2 border-dashed rounded-2 p-2 text-center cursor-pointer w-100"
                       style="min-height: 90px; display:flex; align-items:center; justify-content:center;">
                  <div>
                    <i class="ti ti-cloud-upload text-secondary"></i>
                    <div class="small">Seleccionar archivo</div>
                  </div>
                </label>

                {{-- Cargado --}}
                <div id="ui_cedula_loaded" class="d-none">
                  <div class="p-2 bg-success-lt rounded d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                      <i class="ti ti-folder-check text-success fs-5 me-2"></i>
                      <div class="min-w-0" style="min-width:0;">
                        <div class="fw-bold text-success small">Archivo cargado</div>
                        <div id="file_cedula_name" class="text-secondary small text-truncate" style="max-width:100%;">—</div>
                      </div>
                    </div>
                    <div class="btn-group btn-group-sm flex-shrink-0 ms-2">
                      <button type="button" class="btn btn-link text-secondary px-1" title="Ver"><i class="ti ti-eye"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Descargar"><i class="ti ti-download"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Eliminar"><i class="ti ti-trash"></i></button>
                    </div>
                  </div>
                </div>

                <input type="file" name="file_cedula" id="file_cedula" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file_cedula') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

          {{-- =============== RFC =============== --}}
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
              <div class="card-header py-2 d-flex align-items-center">
                <i class="ti ti-file-certificate me-2"></i>
                <h4 class="card-title mb-0 fs-6">RFC <span class="text-danger">*</span></h4>
              </div>
              <div class="card-body p-2">
                {{-- Vacío --}}
                <label for="file_rfc" id="ui_rfc_empty"
                       class="dropzone dz-clickable border-2 border-dashed rounded-2 p-2 text-center cursor-pointer w-100"
                       style="min-height: 90px; display:flex; align-items:center; justify-content:center;">
                  <div>
                    <i class="ti ti-cloud-upload text-secondary"></i>
                    <div class="small">Seleccionar archivo</div>
                  </div>
                </label>

                {{-- Cargado --}}
                <div id="ui_rfc_loaded" class="d-none">
                  <div class="p-2 bg-success-lt rounded d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                      <i class="ti ti-folder-check text-success fs-5 me-2"></i>
                      <div class="min-w-0" style="min-width:0;">
                        <div class="fw-bold text-success small">Archivo cargado</div>
                        <div id="file_rfc_name" class="text-secondary small text-truncate" style="max-width:100%;">—</div>
                      </div>
                    </div>
                    <div class="btn-group btn-group-sm flex-shrink-0 ms-2">
                      <button type="button" class="btn btn-link text-secondary px-1" title="Ver"><i class="ti ti-eye"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Descargar"><i class="ti ti-download"></i></button>
                      <button type="button" class="btn btn-link text-secondary px-1" title="Eliminar"><i class="ti ti-trash"></i></button>
                    </div>
                  </div>
                </div>

                <input type="file" name="file_rfc" id="file_rfc" class="d-none" accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file_rfc') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

        </div>

        {{-- Barra acciones inferior --}}
        <div class="card shadow-sm mt-4">
          <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center">
              <i class="ti ti-folder-check me-2"></i>
              <div>
                <div class="small text-secondary">Progreso</div>
                <div id="progress_text" class="fw-semibold">0 / 4 seleccionados</div>
              </div>
            </div>

            <div class="vr mx-2 d-none d-lg-block"></div>

            <div class="d-flex align-items-center small text-secondary">
              <i class="ti ti-info-circle me-2"></i>
              Sube los 4 documentos requeridos para habilitar el envío.
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
              <button type="submit"
                      id="btn_submit"
                      class="btn btn-primary btn-sm disabled"
                      aria-disabled="true"
                      style="background-color: rgb(85, 88, 90); border-color: rgb(85, 88, 90);">
                <i class="ti ti-upload me-1"></i> Enviar
              </button>
            </div>
          </div>
        </div>

      </form>
    </div>
  </div>

  {{-- Script: 4 obligatorios + UI responsiva --}}
  <script>
    (function () {
      const items = [
        { key: 'curp'   },
        { key: 'ine'    },
        { key: 'cedula' },
        { key: 'rfc'    },
      ];

      const progressText = document.getElementById('progress_text');
      const btnSubmit    = document.getElementById('btn_submit');

      function updateProgress() {
        let count = 0;
        items.forEach(({ key }) => {
          const input = document.getElementById(`file_${key}`);
          if (input && input.files && input.files.length > 0) count++;
        });
        progressText.textContent = `${count} / 4 seleccionados`;

        const allSelected = (count === 4);
        if (allSelected) {
          btnSubmit.classList.remove('disabled');
          btnSubmit.removeAttribute('aria-disabled');
          btnSubmit.style.backgroundColor = 'rgb(85, 88, 90)';
          btnSubmit.style.borderColor = 'rgb(85, 88, 90)';
          btnSubmit.style.cursor = 'pointer';
          btnSubmit.title = 'Enviar';
        } else {
          btnSubmit.classList.add('disabled');
          btnSubmit.setAttribute('aria-disabled', 'true');
          btnSubmit.style.backgroundColor = 'rgb(85, 88, 90)';
          btnSubmit.style.borderColor = 'rgb(85, 88, 90)';
          btnSubmit.style.cursor = 'not-allowed';
          btnSubmit.title = 'Selecciona los 4 archivos';
        }
      }

      function toggleUI(key, hasFile, fileName = '') {
        const empty = document.getElementById(`ui_${key}_empty`);
        const loaded = document.getElementById(`ui_${key}_loaded`);
        const nameEl = document.getElementById(`file_${key}_name`);

        if (hasFile) {
          if (nameEl) nameEl.textContent = fileName || 'archivo_seleccionado';
          empty?.classList.add('d-none');
          loaded?.classList.remove('d-none');
        } else {
          empty?.classList.remove('d-none');
          loaded?.classList.add('d-none');
          if (nameEl) nameEl.textContent = '—';
        }
      }

      items.forEach(({ key }) => {
        const input = document.getElementById(`file_${key}`);
        if (!input) return;

        input.addEventListener('change', function () {
          if (this.files && this.files.length > 0) {
            toggleUI(key, true, this.files[0].name);
          } else {
            toggleUI(key, false);
          }
          updateProgress();
        });
      });

      updateProgress();
    })();
  </script>
</x-pages.page-app>




