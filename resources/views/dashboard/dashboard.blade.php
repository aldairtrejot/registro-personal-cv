<x-pages.page-app title="Dashboard">
    <x-title.title-header title="Dashboard" />

    <style>
        /* Hover suave en cards */
        .card.card-link.card-link-pop {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .card.card-link.card-link-pop:hover {
            transform: translateY(-2px);
        }

        .card-link-pop:hover .ribbon {
            filter: brightness(.9);
        }

        /* Íconos del ribbon: blancos y más grandes */
        .ribbon .ti {
            color: #ffffff !important;
            font-size: 20px;
            line-height: 1;
        }

        @media (min-width: 992px) {
            .ribbon .ti {
                font-size: 25px;
            }
        }
    </style>

    <div class="container-xl my-3">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">

            {{-- Usuarios (solo ADMIN = rol 1) --}}
            @hasrole(1)
                <div class="col">
                    <a href="{{ route('user') }}" class="card card-link card-link-pop h-100 shadow-sm">
                        <div style="background-color:#BC955C" class="ribbon ribbon-top">
                            <i class="ti ti-users-minus" aria-hidden="true"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title mb-2">Usuarios</h3>
                            <p class="text-secondary mb-0">
                                Ir a la sección de usuarios: altas, bajas y permisos.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary w-100" role="button">IR</span>
                        </div>
                    </a>
                </div>
            @endhasrole

            {{-- Roles (solo ADMIN) --}}
            @hasrole(1)
                <div class="col">
                    <a href="{{ route('user') }}" class="card card-link card-link-pop h-100 shadow-sm">
                        <div style="background-color:#BC955C" class="ribbon ribbon-top">
                            <i class="ti ti-puzzle" aria-hidden="true"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title mb-2">Roles</h3>
                            <p class="text-secondary mb-0">
                                Ir a la sección de administración de roles.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary w-100" role="button">IR</span>
                        </div>
                    </a>
                </div>
            @endhasrole

            {{-- Mi expediente (rol 2 = empleado)
            @hasrole(2)
                <div class="col">
                    <a href="{{ route('follow') }}" class="card card-link card-link-pop h-100 shadow-sm">
                        <div style="background-color:#BC955C" class="ribbon ribbon-top">
                            <i class="ti ti-file-dots" aria-hidden="true"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title mb-2">Mi expediente</h3>
                            <p class="text-secondary mb-0">
                                Organiza tus documentos y monitorea tu avance en el camino hacia
                                el proceso de profesionalización.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary w-100" role="button">IR</span>
                        </div>
                    </a>
                </div>
            @endhasrole--}}

            {{-- Profesionalización (varios roles, incluido admin) 
            @hasrole(1,3,4,5)
                <div class="col">
                    <a href="{{ route('employee') }}" class="card card-link card-link-pop h-100 shadow-sm">
                        <div style="background-color:#BC955C" class="ribbon ribbon-top">
                            <i class="ti ti-escalator-up" aria-hidden="true"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title mb-2">Profesionalización</h3>
                            <p class="text-secondary mb-0">
                                Ir a profesionalización para validar información.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary w-100" role="button">IR</span>
                        </div>
                    </a>
                </div>
            @endhasrole--}}

            {{-- Revisión de CV (que la vean ADMIN y REVISOR) --}}
            @hasrole(1,3)
                <div class="col">
                    <a href="{{ route('revisor.empleados') }}" class="card card-link card-link-pop h-100 shadow-sm">
                        <div style="background-color:#BC955C" class="ribbon ribbon-top">
                            <i class="ti ti-file-text" aria-hidden="true"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title mb-2">Revisión de CV</h3>
                            <p class="text-secondary mb-0">
                                Consulta y revisa los CV capturados por el personal.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary w-100" role="button">IR</span>
                        </div>
                    </a>
                </div>
            @endhasrole

            {{-- 🔹 Reporte de CV (solo ADMIN = rol 1) --}}
            @hasrole(1)
                <div class="col">
                    <a href="{{ route('cv.reportes.empleados_terminados') }}" class="card card-link card-link-pop h-100 shadow-sm">
                        <div style="background-color:#235B4E" class="ribbon ribbon-top">
                            <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title mb-2">Reporte de CV</h3>
                            <p class="text-secondary mb-0">
                                Descarga en Excel los empleados que han concluido su registro.
                            </p>
                        </div>
                        <div  class="card-footer bg-transparent">
                            <span class="btn btn-secondary w-100" role="button">DESCARGAR</span>
                        </div>
                    </a>
                </div>
            @endhasrole

            {{-- 🔹 Reporte de CV (solo REVISOR = rol 3) --}}
            @hasrole(3)
                <div class="col">
                    <a href="{{ route('cv.reportes.empleados_terminados') }}" class="card card-link card-link-pop h-100 shadow-sm">
                        <div style="background-color:#235B4E" class="ribbon ribbon-top">
                            <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title mb-2">Reporte de CV (Revisor)</h3>
                            <p class="text-secondary mb-0">
                                Descarga en Excel los registros concluidos para su análisis.
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="btn btn-secondary w-100" role="button">DESCARGAR</span>
                        </div>
                    </a>
                </div>
            @endhasrole

        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div id="blade_dashboard"></div>
        </div>
    </div>
</x-pages.page-app>
