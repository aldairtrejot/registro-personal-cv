<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('') }}">

    <script>
        window.BASE_URL = "{{ url('') }}";
    </script>

    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />
    <title>CVPROIB</title>

    <link rel="stylesheet" href="{{ asset('assets/css/tabler.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/spinner.css') }}">
    <script src="{{ asset('assets/js/jquery.js') }}"></script>

    <style>
        .navbar-imss { background: rgb(122, 27, 50); }
        .navbar-imss .nav-link { color: rgba(255, 255, 255, .95) !important; }
        .navbar-imss .text-muted-white { color: rgba(255, 255, 255, .78) !important; }
        .nav-badge {
            position: absolute;
            top: .15rem;
            right: .15rem;
            transform: translate(35%, -35%);
            border: 2px solid rgba(122, 27, 50, 1);
        }
        .dropdown-menu { min-width: 16rem; }
        .user-kicker { font-size: .72rem; text-transform: uppercase; letter-spacing: .02em; color: rgba(0,0,0,.55); }
    </style>

    @vite(['resources/js/app.js'])
</head>

<body>
    <div id="spinnerOverlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <div class="page">

        {{-- NAVBAR SUPERIOR --}}
        <header class="navbar navbar-expand-md d-print-none navbar-imss">
            <div class="container-xl">
                <button class="navbar-toggler" type="button"
                        data-bs-toggle="collapse" data-bs-target="#navbar-menu"
                        aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <img src="{{ asset('assets/images/imss_logo.png') }}" style="width: 180px; height: auto;" />
                </div>

                <div class="navbar-nav flex-row order-md-last align-items-center gap-1">

                    @php($user = Auth::user())
                    @php($displayName = $user?->nombre_completo ?? $user?->name ?? $user?->username ?? 'USUARIO')
                    @php($displayEmail = $user?->email ?? '')

                    {{-- NOTIFICACIONES --}}
                    @auth
                    <div class="nav-item dropdown d-none d-md-flex">
                        <a href="#" class="nav-link px-2 position-relative"
                           data-bs-toggle="dropdown" data-bs-auto-close="outside"
                           role="button" aria-expanded="false" aria-label="Notificaciones">
                            <i class="ti ti-bell" style="font-size: 1.25rem;"></i>
                            <span id="notifBadge" class="badge bg-red nav-badge d-none">0</span>
                        </a>

                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Notificaciones</h3>
                                    <div class="ms-auto small text-secondary">Sesión activa</div>
                                </div>

                                <div class="list-group list-group-flush list-group-hoverable">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="status-dot status-dot-animated bg-green d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <div class="text-body d-block">Estatus de usuario</div>
                                                <div class="d-block text-secondary text-truncate mt-n1">
                                                    Activo · {{ $displayEmail }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    @endauth

                    {{-- USUARIO --}}
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 p-0 px-2"
                           data-bs-toggle="dropdown" role="button" aria-expanded="false" aria-label="Menú usuario">
                            <span class="avatar avatar-sm" style="background: rgba(255,255,255,.14); color: white;">
                                <i class="ti ti-user" style="font-size: 1.05rem;"></i>
                            </span>

                            <div class="d-none d-xl-block ps-2">
                                <div class="fw-semibold" style="line-height: 1.1;">
                                    @auth
                                        {{ strtoupper(collect(explode(' ', (string)$displayName))->take(2)->implode(' ')) }}
                                    @else
                                        INVITADO
                                    @endauth
                                </div>
                                <div class="small text-muted-white" style="line-height: 1.1;">
                                    @auth {{ $displayEmail }} @else Sin sesión iniciada @endauth
                                </div>
                            </div>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            @auth
                                <div class="dropdown-header">
                                    <div class="user-kicker">Cuenta</div>
                                    <div class="fw-semibold text-truncate">{{ $displayName }}</div>
                                    <div class="small text-secondary text-truncate">{{ $displayEmail }}</div>
                                </div>
                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item" href="#"
                                   data-open-logout="1"
                                   data-bs-toggle="modal" data-bs-target="#modal_logout">
                                    <i class="ti ti-logout me-2"></i> Cerrar sesión
                                </a>

                                <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="d-none">
                                    @csrf
                                </form>
                            @endauth

                            @guest
                                <a class="dropdown-item" href="{{ url('/login') }}">
                                    <i class="ti ti-login me-2"></i> Iniciar sesión
                                </a>
                            @endguest
                        </div>
                    </div>

                </div>
            </div>
        </header>

        {{-- MENÚ LATERAL --}}
        <x-menu.menu-app>
            @hasrole(1, 2, 3, 4, 5)
                <x-menu.menu-only title="Dashboard" icon="ti ti-home" href="{{ route('dashboard') }}" />
            @endhasrole

            @hasrole(5)
                <x-menu.menu-only title="Mi Expediente" icon="ti ti-file-spark" href="{{ route('follow') }}" />
            @endhasrole

            @hasrole(5)
                <x-menu.menu-dropdown title="Administración" icon="ti ti-user-cog">
                    <x-menu.menu-item label="Usuarios" href="{{ route('user') }}" :isNew="false" />
                    <x-menu.menu-item label="Usuario Empleados" href="{{ route('useremployee') }}" :isNew="false" />
                    <x-menu.menu-item-2x title="Roles">
                        <x-menu.menu-item label="Rol" href="{{ route('role') }}" :isNew="false" />
                        <x-menu.menu-item label="Rama" href="{{ route('branch') }}" :isNew="false" />
                        <x-menu.menu-item label="Zona" href="{{ route('zone') }}" :isNew="false" />
                        <x-menu.menu-item label="Entidad" href="{{ route('entity') }}" :isNew="false" />
                    </x-menu.menu-item-2x>
                </x-menu.menu-dropdown>
            @endhasrole

            @hasrole(1, 2, 3, 4, 5)
                <x-menu.menu-only title="Acerca de" icon="ti ti-info-circle" href="{{ route('about') }}" />
            @endhasrole
        </x-menu.menu-app>

        <div class="page-wrapper">
            {{ $slot }}
        </div>
    </div>

    {{-- MODAL LOGOUT --}}
    @auth
    <div class="modal fade" id="modal_logout" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <h3 class="mb-1">Cerrar sesión</h3>
                    <div class="text-secondary">¿Está seguro que desea cerrar la sesión?</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="logoutForm" class="btn btn-danger">
                        <i class="ti ti-logout me-1"></i> Cerrar sesión
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <script src="{{ asset('assets/js/tabler.js') }}"></script>

    {{-- ✅ MISMO SCRIPT “SIEMPRE ABRE” --}}
    <script>
        (function () {
            function closeAllDropdowns() {
                document.querySelectorAll('.dropdown.show').forEach(function (dd) {
                    dd.classList.remove('show');
                    const menu = dd.querySelector('.dropdown-menu');
                    if (menu) menu.classList.remove('show');
                });
            }

            document.addEventListener('click', function (e) {
                const toggle = e.target.closest('[data-bs-toggle="dropdown"]');
                const clickedInsideMenu = e.target.closest('.dropdown-menu');

                if (toggle) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dd = toggle.closest('.dropdown');
                    if (!dd) return;

                    const menu = dd.querySelector('.dropdown-menu');
                    if (!menu) return;

                    const isOpen = dd.classList.contains('show') || menu.classList.contains('show');
                    closeAllDropdowns();

                    if (!isOpen) {
                        dd.classList.add('show');
                        menu.classList.add('show');
                    }
                    return;
                }

                if (clickedInsideMenu) {
                    const dd = clickedInsideMenu.closest('.dropdown');
                    const toggleEl = dd ? dd.querySelector('[data-bs-toggle="dropdown"]') : null;
                    const autoClose = toggleEl ? (toggleEl.getAttribute('data-bs-auto-close') || 'true') : 'true';
                    if (autoClose === 'outside') return;
                }

                closeAllDropdowns();
            }, true);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeAllDropdowns();
            });

            function showModalFallback(modalEl) {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.removeAttribute('aria-hidden');
                modalEl.setAttribute('aria-modal', 'true');
                document.body.classList.add('modal-open');

                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.dataset.fallbackBackdrop = '1';
                document.body.appendChild(backdrop);
            }

            function hideModalFallback(modalEl) {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                document.querySelectorAll('div.modal-backdrop[data-fallback-backdrop="1"]').forEach(b => b.remove());
            }

            document.addEventListener('click', function (e) {
                const openLogout = e.target.closest('[data-open-logout="1"]');
                if (!openLogout) return;

                const modalEl = document.getElementById('modal_logout');
                if (!modalEl) return;

                if (window.bootstrap && window.bootstrap.Modal) {
                    const instance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    instance.show();
                    return;
                }

                e.preventDefault();
                showModalFallback(modalEl);
            }, true);

            const modalEl = document.getElementById('modal_logout');
            if (modalEl) {
                modalEl.addEventListener('click', function (e) {
                    if (e.target === modalEl && !(window.bootstrap && window.bootstrap.Modal)) {
                        hideModalFallback(modalEl);
                    }
                });

                modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        if (!(window.bootstrap && window.bootstrap.Modal)) {
                            e.preventDefault();
                            hideModalFallback(modalEl);
                        }
                    });
                });
            }
        })();
    </script>
</body>

</html>