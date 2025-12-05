<x-pages.page-auth>
    <div id="blade_registro_wizard"></div>

    <script>
        // Aquí puedes poner la URL a donde quieres mandar al usuario al terminar
        window.CV_FINISH_URL = "{{ url('/registro-personal-cv/public/login') }}";
    </script>
</x-pages.page-auth>
