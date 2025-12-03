{{-- resources/views/administration/entity/list.blade.php --}}
<x-pages.page-app>

    {{-- Título de la sección --}}
    <x-title.title-header title="Administración">
       {{-- <x-button.button-header tooltip="Agregar" icon="ti ti-plus" href="{{ route('entity.create') }}" />--}}
    </x-title.title-header>

    <div class="page-body">
        <div class="container-xl">
            {{-- Aquí se montará el componente Vue para la lista de entidades --}}
            <div id="blade_entity_list"></div>
        </div>
    </div>

</x-pages.page-app>