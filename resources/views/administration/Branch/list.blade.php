<x-pages.page-app>
  <x-title.title-header title="Administración" />

  <div class="page-body">
    <div class="container-xl">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <div
        id="blade_Branch_list"
        data-list-url="/branch/table"
        data-create-url="/branch/save"
        data-delete-base="/branch"
        data-edit-base="/branch"
      ></div>
    </div>
  </div>
</x-pages.page-app>
