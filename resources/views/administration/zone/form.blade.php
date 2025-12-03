<x-pages.page-app>

    <x-title.title-header title="{{ isset($id) ? 'Modificar' : 'Agregar ' }} zona">
        <x-button.button-header tooltip="Regresar" icon="ti ti-arrow-left-dashed" href="{{ route('zone') }}" />
    </x-title.title-header>

    <div class="page-body">
        <div class="container-xl">
            <input type="hidden" name="id" id="id" value="{{ $id ?? '' }}">
            <div id="blade_zone_form"></div>
        </div>
    </div>
</x-pages.page-app>
