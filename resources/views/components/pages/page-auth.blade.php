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

    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="card card-md" style="box-shadow:0 4px 20px rgba(0,0,0,0.3); border-radius:10px; padding:20px;">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/images/imss-bienestar-2025.png') }}"
                            style="width: 180px; height: auto;" />
                    </div>

                    <h2 class="h2 text-center mb-4">
                        Proceso Curricular
                    </h2>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/tabler.js') }}"></script>
</body>

</html>
