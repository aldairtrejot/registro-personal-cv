<x-pages.page-app title="Detalle de CV">
    <x-title.title-header title="Detalle de CV" />

    <div class="page-body">
        <div class="container-xl">
            {{-- El ID del empleado viaja en data-atributo para Vue --}}
            <div
                id="blade_revisor_empleado_show"
                data-empleado-id="{{ $id }}"
            ></div>
        </div>
    </div>
</x-pages.page-app>
