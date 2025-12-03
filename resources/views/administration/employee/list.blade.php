<x-pages.page-app>

  <x-title.title-header title="Profesionalización">
    {{-- Botón de cabecera (abre el modal #modal_download) --}}
    <x-button.button-header-modal
      tooltip="Descargar"
      icon="ti ti-file-download"
      idModal="#modal_download"
    />
  </x-title.title-header>

  <div class="page-body">
    <div class="container-xl">

      {{-- 👇 Expones los roles antes de montar Vue --}}
      <script>
        window.appUserRoles = @json(session('user_roles', []));
      </script>

      {{-- Punto de montaje del .vue que incluye el modal #modal_download --}}
      <div id="blade_employee_list"></div>

    </div>
  </div>
</x-pages.page-app>
