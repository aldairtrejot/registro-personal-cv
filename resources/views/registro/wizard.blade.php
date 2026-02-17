{{-- Vista para el wizard de registro de CV --}}
<x-pages.page-auth-clean>
    <div id="blade_registro_wizard"></div>

    <script>
        // Si al finalizar quieres mandar al login
        window.CV_FINISH_URL = "{{ url('/registro-personal-cv/public/login') }}";
    </script>
</x-pages.page-auth-clean>