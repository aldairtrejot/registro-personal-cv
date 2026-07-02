<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale() ?: 'es') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-base-url" content="{{ url('') }}">

    <title>{{ $title ?? 'CVPROIB' }}</title>

    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/tabler.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/spinner.css') }}">

    <script>
        window.BASE_URL = @json(url(''));
    </script>

    <script src="{{ asset('assets/js/jquery.js') }}"></script>
    @vite(['resources/js/app.js'])

    <style>
        :root {
            --imss-red: #9F2241;
            --imss-wine: #691C32;
            --imss-green: #006657;
            --imss-teal: #235B4E;
            --imss-green-dark: #10312B;
            --imss-gold: #BC955C;
            --imss-beige: #DDC9A3;

            --auth-body-bg: #f4f1ea;
            --auth-surface: rgba(255, 255, 255, 0.94);
            --auth-surface-soft: rgba(255, 255, 255, 0.82);
            --auth-border: rgba(16, 49, 43, 0.12);
            --auth-border-soft: rgba(16, 49, 43, 0.08);
            --auth-text: #1f2937;
            --auth-muted: #5f6b72;
            --auth-radius-lg: 24px;
            --auth-radius-md: 18px;
            --auth-shadow: 0 22px 50px rgba(16, 49, 43, 0.14);
        }

        html,
        body {
            min-height: 100%;
        }

        body.auth-body {
            min-height: 100vh;
            margin: 0;
            color: var(--auth-text);
            background:
                linear-gradient(rgba(244, 241, 234, 0.92), rgba(244, 241, 234, 0.88)),
                url('{{ asset('assets/images/background-imss-white.png') }}') center top / cover no-repeat fixed;
            overflow-x: hidden;
            position: relative;
        }

        body.auth-body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background:
                radial-gradient(circle at top left, rgba(159, 34, 65, 0.10), transparent 28%),
                radial-gradient(circle at top right, rgba(0, 102, 87, 0.12), transparent 30%),
                radial-gradient(circle at bottom center, rgba(188, 149, 92, 0.14), transparent 34%);
        }

        .auth-topbar,
        .auth-main {
            position: relative;
            z-index: 1;
        }

        .auth-topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.86);
            border-bottom: 1px solid rgba(255, 255, 255, 0.55);
            box-shadow: 0 8px 24px rgba(16, 49, 43, 0.08);
        }

        .auth-topbar::before {
            content: "";
            display: block;
            height: 6px;
            background: linear-gradient(90deg,
                var(--imss-wine) 0%,
                var(--imss-red) 18%,
                var(--imss-teal) 45%,
                var(--imss-green) 68%,
                var(--imss-gold) 100%);
        }

        .auth-topbar__inner {
            width: min(1240px, calc(100% - 32px));
            min-height: 60px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 8px 0;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .auth-brand__logos {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(16, 49, 43, 0.08);
            box-shadow: 0 6px 16px rgba(16, 49, 43, 0.06);
        }

        .auth-brand__logos img {
            display: block;
            object-fit: contain;
        }

        .auth-brand__logos .logo-imss {
            width: 34px;
            height: 34px;
        }

        .auth-brand__logos .logo-bienestar {
            width: 110px;
            height: 34px;
        }

        .auth-brand__divider {
            width: 1px;
            height: 24px;
            background: linear-gradient(180deg, transparent, rgba(16, 49, 43, 0.18), transparent);
        }

        .auth-brand__text {
            min-width: 0;
        }

        .auth-brand__title {
            margin: 0;
            font-size: 1rem;
            line-height: 1.2;
            font-weight: 800;
            color: var(--imss-green-dark);
            letter-spacing: .01em;
        }

        .auth-brand__subtitle {
            margin: 2px 0 0;
            color: var(--auth-muted);
            font-size: .84rem;
            font-weight: 600;
        }

        .auth-topbar__badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(0, 102, 87, 0.10), rgba(188, 149, 92, 0.18));
            color: var(--imss-green-dark);
            border: 1px solid rgba(0, 102, 87, 0.10);
            font-size: .82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .auth-main {
            min-height: calc(100vh - 80px);
            padding: 30px 16px 42px;
        }

        .auth-shell {
            width: min(1240px, 100%);
            margin: 0 auto;
            padding: 18px;
            border-radius: 30px;
            background: linear-gradient(180deg, rgba(255,255,255,0.52), rgba(255,255,255,0.24));
            border: 1px solid rgba(255,255,255,0.42);
            box-shadow: var(--auth-shadow);
            backdrop-filter: blur(12px);
        }

        .auth-shell__inner {
            background: var(--auth-surface);
            border: 1px solid rgba(255,255,255,0.72);
            border-radius: var(--auth-radius-lg);
            padding: 18px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.4);
        }

        a {
            color: var(--imss-green);
        }

        a:hover {
            color: var(--imss-green-dark);
        }

        ::selection {
            background: rgba(0, 102, 87, 0.16);
            color: var(--imss-green-dark);
        }



        /* Ajuste compacto: una sola imagen institucional en el encabezado */
        .auth-brand--compact {
            gap: 12px;
        }

        .auth-brand__single-logo {
            width: 138px;
            height: 42px;
            object-fit: contain;
            padding: 6px 10px;
            border-radius: 14px;
            background: rgba(255,255,255,0.78);
            border: 1px solid rgba(16, 49, 43, 0.08);
            box-shadow: 0 6px 16px rgba(16, 49, 43, 0.06);
            flex: 0 0 auto;
        }

        @media (max-width: 992px) {
            .auth-topbar__inner {
                width: min(100% - 24px, 1240px);
                min-height: 68px;
            }

            .auth-main {
                padding: 22px 10px 28px;
            }

            .auth-shell {
                padding: 12px;
                border-radius: 24px;
            }

            .auth-shell__inner {
                padding: 12px;
            }
        }

        @media (max-width: 768px) {
            .auth-topbar__inner {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 10px 0 12px;
            }

            .auth-brand {
                justify-content: center;
            }

            .auth-topbar__badge {
                justify-content: center;
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .auth-main {
                padding: 14px 6px 22px;
            }

            .auth-shell {
                padding: 8px;
                border-radius: 18px;
            }

            .auth-shell__inner {
                padding: 8px;
                border-radius: 16px;
            }

            .auth-brand {
                flex-direction: column;
                text-align: center;
            }

            .auth-brand__logos {
                width: 100%;
                justify-content: center;
            }

            .auth-brand__logos .logo-bienestar {
                width: 96px;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="auth-body">
    <div id="spinnerOverlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <header class="auth-topbar" aria-label="Encabezado institucional">
        <div class="auth-topbar__inner">
            <div class="auth-brand auth-brand--compact">
                <img src="{{ asset('assets/images/imss-bienestar-2025.png') }}" alt="IMSS Bienestar" class="auth-brand__single-logo" onerror="this.style.display='none'">
                <div class="auth-brand__text">
                    <h1 class="auth-brand__title">CVPROIB</h1>
                    <p class="auth-brand__subtitle">Registro curricular institucional</p>
                </div>
            </div>

            <div class="auth-topbar__badge">
                <i class="ti ti-shield-check"></i>
                <span>Captura segura</span>
            </div>
        </div>
    </header>

    <main class="auth-main">
        <section class="auth-shell">
            <div class="auth-shell__inner">
                {{ $slot }}
            </div>
        </section>
    </main>

    <script src="{{ asset('assets/js/tabler.js') }}"></script>
    @stack('scripts')
</body>
</html>
