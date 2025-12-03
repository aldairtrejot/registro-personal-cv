<x-pages.page-app>

    <x-title.title-header title="{{ isset($id) ? 'Modificar' : 'Agregar ' }} entidad">
        <x-button.button-header tooltip="Regresar" icon="ti ti-arrow-left-dashed" href="{{ route('entity') }}" />
    </x-title.title-header>

    <div class="page-body">
        <div class="container-xl">
            <input type="hidden" name="id" id="id" value="{{ $id ?? '' }}">
            <div id="blade_entity_form"></div>
        </div>
    </div>
</x-pages.page-app>

