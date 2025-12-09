{{-- Vista para el wizard de registro de CV --}}
<x-pages.page-auth>
    <div id="blade_registro_wizard"></div>

    <script>
        // URL a donde enviar al usuario al terminar el flujo
        window.CV_FINISH_URL = "{{ url('/registro-personal-cv/public/login') }}";
    </script>
</x-pages.page-auth>
