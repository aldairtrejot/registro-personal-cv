<!-- resources/views/follow/follow.blade.php -->
<x-pages.page-app title="Dashboard">
    {{-- CSRF meta por si el layout no la trae --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-title.title-header title="Mi Expediente">
        <x-button.button-header-modal tooltip="Historia" icon="ti ti-history" idModal="#modal_follow_history" />
    </x-title.title-header>

    <div class="page-body">
        <div class="container-xl">

            <div id="blade_modal_follow_history"></div>

            <style>
                .nav-tabs .nav-link { border: none !important; color: #7a7b7cff; font-weight: 600; }
                .nav-tabs .nav-link.active { border-bottom: 3px solid #444; color: #9F2241; }
                .nav-tabs { border-bottom: none; }
            </style>

            <div>
                <!-- Menú de pestañas -->
                <ul class="nav nav-tabs mb-3" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tabs-home-6" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">
                            Seguimiento
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tabs-profile-6" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            Datos
                        </a>
                    </li>
                </ul>

                <!-- Contenido de pestañas -->
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabs-home-6" role="tabpanel">
                        <div id="blade_follow_dashboard"></div>
                    </div>
                    <div class="tab-pane fade" id="tabs-profile-6" role="tabpanel">
                        <div id="blade_data_employee"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Props globales para el front
        window.FOLLOW_PROPS = {
            hasPosition: {{ ($hasPosition ?? false) ? 'true' : 'false' }},
            positionId : {{ (int) ($positionId ?? 0) }},
            routes: {
                // Endpoints API
                main : "{{ route('main') }}",
                validate: "{{ route('files.validate') }}",
                upload: "{{ route('files.upload') }}",

                // SOLO cloud/view
                cloudViewBase: "{{ url('/cloud/view') }}",

                // (opcionales si usas visor con spinner)
                previewBase: "{{ url('/files/preview') }}",
                cloudPreviewBase: "{{ url('/cloud/preview') }}",

                // Follow
                follow: "{{ url('/follow') }}",
                docsByPositionBase: "{{ url('/follow/positions') }}"
            },
            csrf: "{{ csrf_token() }}"
        };

        // Axios: CSRF y XHR
        (function () {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.FOLLOW_PROPS?.csrf;
            if (window.axios) {
                window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                if (token) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                window.axios.defaults.withCredentials = true;
            }
        })();
    </script>
</x-pages.page-app>







