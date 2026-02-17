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

    @vite(['resources/js/app.js'])

    <style>
        :root{
            --imss-green: #006341;
            --imss-green-2: #0b8450;
            --imss-wine: #7a1b32;
            --text-1: #111827;
            --text-2: #6b7280;
            --card-border: rgba(229,231,235,.85);
        }

        /* Fondo institucional */
        body{
            min-height: 100vh;
            background:
                radial-gradient(1200px 700px at 10% 10%, rgba(0, 99, 65, .12), transparent 60%),
                radial-gradient(900px 500px at 90% 20%, rgba(122, 27, 50, .08), transparent 55%),
                linear-gradient(180deg, #f7f9fc 0%, #f2f6fb 100%);
        }

        /* Marca de agua muy sutil (patrón) */
        body::before{
            content:"";
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .08;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='560' height='560' viewBox='0 0 560 560'%3E%3Cg fill='none' stroke='%23006341' stroke-width='2'%3E%3Cpath d='M280 34c136 0 246 110 246 246S416 526 280 526 34 416 34 280 144 34 280 34z'/%3E%3Cpath d='M280 110c94 0 170 76 170 170s-76 170-170 170-170-76-170-170 76-170 170-170z'/%3E%3Cpath d='M132 280h296'/%3E%3Cpath d='M280 132v296'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 560px 560px;
            background-repeat: repeat;
            background-position: center;
        }

        /* Barra superior verde */
        .auth-topbar{
            height: 56px;
            background: linear-gradient(90deg, var(--imss-green) 0%, var(--imss-green-2) 100%);
            box-shadow: 0 10px 24px rgba(16,24,40,.10);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        /* Contenedor */
        .auth-clean-wrap{
            min-height: calc(100vh - 56px);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 26px 16px 34px;
        }

        .auth-clean-inner{
            width: 100%;
            max-width: 1100px; /* ancho cómodo */
        }

        /* Superficie de contenido (para que no se vea “pelón”) */
        .auth-surface{
            background: rgba(255,255,255,.78);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 18px 55px rgba(16,24,40,.10);
            backdrop-filter: blur(6px);
        }

        @media (max-width: 576px){
            .auth-clean-wrap{ padding: 18px 12px 26px; }
            .auth-surface{ padding: 12px; border-radius: 16px; }
        }
    </style>
</head>

<body>
    <div id="spinnerOverlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <div class="auth-topbar"></div>

    <div class="auth-clean-wrap">
        <div class="auth-clean-inner">
            <div class="auth-surface">
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/tabler.js') }}"></script>
</body>
</html>