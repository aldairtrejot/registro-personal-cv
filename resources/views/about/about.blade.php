<x-pages.page-app>
    <x-title.title-header title="Acerca de">
    </x-title.title-header>

    <div class="page-body">
        <div class="container-xl">

            <div class="text-center mb-5">
                <h4 class="display-6 fw-bold text-primary" style="color: #8A8A8A !important;">
                    Proceso Curricular
                </h4>
                <span class="badge badge-outline text-default">Version {{ config('app.version', '-') }}</span>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h2 class="card-title">Información técnica</h2>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Última Actualización</div>
                            <div class="datagrid-content">-
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Soporte Técnico</div>
                            <div class="datagrid-content">soporte_rh@imssbienestar.gob.mx</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Creado por</div>
                            <div class="datagrid-content">soporte_rh@imssbienestar.gob.mx</div>
                        </div>
                    </div>
                </div>
            </div>

            <br>
            <div class="alert alert-warning alert-dismissible" role="alert">
                <div class="alert-icon">
                    <!-- Download SVG icon from http://tabler.io/icons/icon/alert-triangle -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="icon alert-icon icon-2">
                        <path d="M12 9v4"></path>
                        <path
                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                        </path>
                        <path d="M12 16h.01"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="alert-heading">Aviso de Confidencialidad</h4>
                    <div class="alert-description">Este sistema es de uso exclusivo para personal autorizado de IMSS
                        BIENESTAR.</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        </div>
    </div>
</x-pages.page-app>
