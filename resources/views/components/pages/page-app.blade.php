<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />
    <title>CVPROIB</title>
    <link rel="stylesheet" href="{{ asset('assets/css/tabler.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/spinner.css') }}">
    <script src="{{ asset('assets/js/jquery.js') }}"></script>

    @vite(['resources/js/app.js'])
</head>

<body>

    <div id="spinnerOverlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <div class="page">

        <header class="navbar navbar-expand-md d-print-none" style="background:rgb(122, 27, 50)">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
                    aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <img src="{{ asset('assets/images/imss_logo.png') }}" style="width: 180px; height: auto;" />
                </div>

                <div class="navbar-nav flex-row order-md-last">
                    <div class="d-none d-md-flex">
                        <div class="nav-item dropdown d-none d-md-flex">
                            <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1"
                                aria-label="Show notifications" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="ti ti-bell" style="font-size: 1.2rem; color:white"></i>
                                <span class="badge bg-red"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                                <div class="card">
                                    <div class="card-header d-flex">
                                        <h3 class="card-title">Notificaciones</h3>
                                    </div>
                                    <div class="list-group list-group-flush list-group-hoverable">
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span
                                                        class="status-dot status-dot-animated bg-green d-block"></span>
                                                </div>
                                                <div class="col text-truncate">
                                                    <a href="#" class="text-body d-block">Estatus de usuario</a>
                                                    <div class="d-block text-secondary text-truncate mt-n1">Activo</div>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="#" class="list-group-item-actions">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            class="icon text-muted icon-2">
                                                            <path
                                                                d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div><!-- list-group -->
                                </div><!-- card -->
                            </div><!-- dropdown-menu -->
                        </div>
                    </div>

                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown"
                            aria-label="Open user menu">
                            <i style="font-size: 1.2rem; color:white" class="ti ti-user"></i>
                            <div class="d-none d-xl-block ps-2">
                                <div style="color:white">
                                    {{ strtoupper(collect(explode(' ', Auth::user()->name))->take(2)->implode(' ')) }}
                                </div>
                                <div class="mt-1 small text-secondary" style="color: white !important"></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            {{-- <a href="./settings.html" class="dropdown-item">Configuraciones</a>
                            <a href="#" class="dropdown-item">Estatus</a>
                            <a href="./profile.html" class="dropdown-item">Perfil</a>
                            <div class="dropdown-divider"></div> --}}
                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                data-bs-target="#modal_logout">Salir</button>
                        </div>
                    </div>
                </div><!-- navbar-nav -->
            </div><!-- container-xl -->
        </header>

        <x-menu.menu-app>
            {{-- Dashboard (roles 1..6) --}}
            @hasrole(1, 2, 3, 4, 5)
                <x-menu.menu-only title="Dashboard" icon="ti ti-home" href="{{ route('dashboard') }}" />
            @endhasrole

            {{-- Mi Expediente (rol 2) --}}
            @hasrole(2)
                <x-menu.menu-only title="Mi Expediente" icon="ti ti-file-spark" href="{{ route('follow') }}" />
            @endhasrole

            {{-- Administración (rol 1) --}}
            @hasrole(1)
                <x-menu.menu-dropdown title="Administración" icon="ti ti-user-cog">
                    <x-menu.menu-item label="Usuarios" href="{{ route('user') }}" :isNew="false" />
                    <x-menu.menu-item label="Usuario Empleados" href="{{ route('useremployee') }}" :isNew="false" />
                    <x-menu.menu-item-2x title="Roles">
                        <x-menu.menu-item label="Rol" href="{{ route('role') }}" :isNew="false" />
                        <x-menu.menu-item label="Rama" href="{{ route('branch') }}" :isNew="false" />
                        <x-menu.menu-item label="Zona" href="{{ route('zone') }}" :isNew="false" />

                        <x-menu.menu-item label="Entidad" href="{{ route('entity') }}" :isNew="false" />
                        {{-- 
                        <x-menu.menu-item label="Catalogo Zonas" href="{{ route('zone') }}" :isNew="false" />
                             <x-menu.menu-item label="Rol-2" href="{{ route('role') }}" :isNew="false" />
                        <x-menu.menu-item label="Rol-3" href="{{ route('role') }}" :isNew="false" />
                        <x-menu.menu-item label="Rol-4" href="{{ route('role') }}" :isNew="false" />
                        <x-menu.menu-item label="Rol-5" href="{{ route('role') }}" :isNew="false" />
                     --}}
                    </x-menu.menu-item-2x>
                    {{-- 
                    <x-menu.menu-item label="Diseño Archivo" href="{{ route('design') }}" :isNew="false" />
                    <x-menu.menu-item label="Diseño Archivo Supervisor" href="{{ route('designsup') }}"
                        :isNew="false" />
 --}}
                </x-menu.menu-dropdown>
            @endhasrole

            {{-- Profesionalización (roles 1..6) --}}
            @hasrole(1, 3, 4, 5, 6)
                <x-menu.menu-only title="Profesionalización" icon="ti ti-briefcase" href="{{ route('employee') }}" />
            @endhasrole

            {{-- Acerca de (roles 1..5) --}}
            @hasrole(1, 2, 3, 4, 5)
                <x-menu.menu-only title="Acerca de" icon="ti ti-info-circle" href="{{ route('about') }}" />
            @endhasrole
        </x-menu.menu-app>

        <div class="page-wrapper">
            {{ $slot }}
        </div>

    </div><!-- .page -->

    <div id="blade_logout"></div>

    <script src="{{ asset('assets/js/tabler.js') }}"></script>
</body>

</html>
