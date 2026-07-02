{{-- Vista para el wizard de registro de CV --}}
<x-pages.page-auth-clean>
    <div id="blade_registro_wizard"></div>

    <script>
        // Al finalizar, regresa a esta misma pantalla para capturar otro registro.
        window.CV_FINISH_URL = @json(url()->current());
    </script>
</x-pages.page-auth-clean>
