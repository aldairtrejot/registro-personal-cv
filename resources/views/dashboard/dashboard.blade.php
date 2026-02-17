<x-pages.page-app title="Dashboard">
    <x-title.title-header title="Dashboard" />

    <style>
        .card.card-link.card-link-pop { transition: transform .15s ease, box-shadow .15s ease; }
        .card.card-link.card-link-pop:hover { transform: translateY(-2px); }
        .card-link-pop:hover .ribbon { filter: brightness(.9); }
        .ribbon .ti { color: #ffffff !important; font-size: 20px; line-height: 1; }
        @media (min-width: 992px) { .ribbon .ti { font-size: 25px; } }
    </style>

    <div class="container-xl my-3">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">

            {{-- Revisión de CV (ADMIN y REVISOR) --}}
            @hasrole(1, 3)
            <div class="col">
                <a href="{{ route('revisor.empleados') }}" class="card card-link card-link-pop h-100 shadow-sm">
                    <div style="background-color:#BC955C" class="ribbon ribbon-top">
                        <i class="ti ti-file-text" aria-hidden="true"></i>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title mb-2">Revisión de CV</h3>
                        <p class="text-secondary mb-0">Consulta y revisa los CV capturados por el personal.</p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <span class="btn btn-secondary w-100" role="button">IR</span>
                    </div>
                </a>
            </div>
            @endhasrole

            {{-- ✅ Reporte de CV (ADMIN) --}}
            @hasrole(1)
            <div class="col">
                <a href="javascript:void(0)"
                   class="card card-link card-link-pop h-100 shadow-sm"
                   onclick="window.openReporteCvModal()">
                    <div style="background-color:#235B4E" class="ribbon ribbon-top">
                        <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title mb-2">Reporte de CV</h3>
                        <p class="text-secondary mb-0">Descarga en Excel los empleados que han concluido su registro.</p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <span class="btn btn-secondary w-100" role="button">DESCARGAR</span>
                    </div>
                </a>
            </div>
            @endhasrole

            {{-- ✅ Reporte de CV (REVISOR) --}}
            @hasrole(3)
            <div class="col">
                <a href="javascript:void(0)"
                   class="card card-link card-link-pop h-100 shadow-sm"
                   onclick="window.openReporteCvModal()">
                    <div style="background-color:#235B4E" class="ribbon ribbon-top">
                        <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title mb-2">Reporte de CV (Revisor)</h3>
                        <p class="text-secondary mb-0">Descarga en Excel los registros concluidos para su análisis.</p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <span class="btn btn-secondary w-100" role="button">DESCARGAR</span>
                    </div>
                </a>
            </div>
            @endhasrole

        </div>
    </div>

    {{-- ✅ MODAL PARA PARAMETROS --}}
    <div class="modal modal-blur fade" id="modal_reporte_cv" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Descargar reporte de CV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        Selecciona el <b>ejercicio</b> y el <b>trimestre</b> que quieres exportar.
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Ejercicio (año)</label>
                            <input type="number" class="form-control" id="rep_ejercicio" min="2000" max="2100" />
                            <div class="text-danger small mt-1 d-none" id="err_rep_ejercicio">Este campo es obligatorio.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Trimestre</label>
                            <select class="form-select" id="rep_trimestre">
                                <option value="">Selecciona…</option>
                                <option value="1">1 (Ene–Mar)</option>
                                <option value="2">2 (Abr–Jun)</option>
                                <option value="3">3 (Jul–Sep)</option>
                                <option value="4">4 (Oct–Dic)</option>
                            </select>
                            <div class="text-danger small mt-1 d-none" id="err_rep_trimestre">Este campo es obligatorio.</div>
                        </div>
                    </div>

                    <div class="mt-3 text-muted small">
                        * Si no seleccionas nada, se usará el trimestre/año actual (pero aquí lo vamos a pedir para evitar errores).
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="window.descargarReporteCv()">
                        Descargar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ TOAST (mensaje emergente) --}}
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="toast_cv_reporte"
             class="toast align-items-center text-bg-danger border-0"
             role="alert" aria-live="assertive" aria-atomic="true"
             data-bs-delay="4500">
            <div class="d-flex">
                <div class="toast-body">
                    No hay CV aprobados para exportar con el rango de fecha seleccionado.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            function nowYear() { return new Date().getFullYear(); }
            function nowQuarter() { return Math.floor((new Date().getMonth()) / 3) + 1; }

            function showNoDataToast(){
                const el = document.getElementById('toast_cv_reporte');
                if (!el) return;
                const toast = bootstrap.Toast.getOrCreateInstance(el);
                toast.show();
            }

            window.openReporteCvModal = function () {
                const y = nowYear();
                const q = nowQuarter();

                const inYear = document.getElementById('rep_ejercicio');
                const inQ = document.getElementById('rep_trimestre');

                if (inYear && !inYear.value) inYear.value = y;
                if (inQ && !inQ.value) inQ.value = String(q);

                document.getElementById('err_rep_ejercicio')?.classList.add('d-none');
                document.getElementById('err_rep_trimestre')?.classList.add('d-none');

                const el = document.getElementById('modal_reporte_cv');
                const modal = new bootstrap.Modal(el);
                modal.show();
            }

            window.descargarReporteCv = async function () {
                const y = (document.getElementById('rep_ejercicio')?.value || '').trim();
                const q = (document.getElementById('rep_trimestre')?.value || '').trim();

                let ok = true;

                if (!y) { document.getElementById('err_rep_ejercicio')?.classList.remove('d-none'); ok = false; }
                else    { document.getElementById('err_rep_ejercicio')?.classList.add('d-none'); }

                if (!q) { document.getElementById('err_rep_trimestre')?.classList.remove('d-none'); ok = false; }
                else    { document.getElementById('err_rep_trimestre')?.classList.add('d-none'); }

                if (!ok) return;

                const base = (window.BASE_URL || '');
                const url = base + '/cv/reportes/empleados-terminados?ejercicio=' + encodeURIComponent(y) + '&trimestre=' + encodeURIComponent(q);

                // cerrar modal
                const el = document.getElementById('modal_reporte_cv');
                const inst = bootstrap.Modal.getInstance(el);
                if (inst) inst.hide();

                // ✅ Intentar descargar sin cambiar de página.
                // Si el backend responde 404, mostramos el toast.
                try {
                    const resp = await fetch(url, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    if (!resp.ok) {
                        // 404 -> no hay datos
                        showNoDataToast();
                        return;
                    }

                    const blob = await resp.blob();

                    // Obtener filename del header si viene
                    let filename = 'reporte_cv.xlsx';
                    const cd = resp.headers.get('Content-Disposition') || resp.headers.get('content-disposition') || '';
                    const match = cd.match(/filename="([^"]+)"/i);
                    if (match && match[1]) filename = match[1];

                    const a = document.createElement('a');
                    const objectUrl = window.URL.createObjectURL(blob);
                    a.href = objectUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(objectUrl);

                } catch (e) {
                    console.error(e);
                    // si falla, igual mostramos el toast genérico
                    showNoDataToast();
                }
            }
        })();
    </script>

    <div class="page-body">
        <div class="container-xl">
            <div id="blade_dashboard"></div>
        </div>
    </div>
</x-pages.page-app>