{{-- resources/views/administration/employee/edit.blade.php --}}
<x-pages.page-app>
  {{-- Si tu layout ya incluye este meta, puedes quitar esta línea --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <x-title.title-header title="Modificar Empleado">
    <x-button.button-header-modal
      tooltip="Historia"
      icon="ti ti-user-search"
      idModal="#modal_employee_history"
    />
    <x-button.button-header
      tooltip="Regresar"
      icon="ti ti-arrow-left-dashed"
      href="{{ route('employee') }}"
    />
  </x-title.title-header>

  <div class="page-body">
    <div class="container-xl">
      <input type="hidden" id="prof_id" value="{{ $prof_id ?? '' }}">
      <input type="hidden" id="id" value="{{ $id ?? '' }}">

      @php
        $mainRoleInjected = (int)(
          session('main_role')
          ?? session('roles_main')
          ?? (is_array(session('roles')) ? (session('roles')[0] ?? 0) : 0)
          ?? ($main_role ?? 0)
          ?? 0
        );
      @endphp

      {{-- ✅ BASE de la app (incluye el subpath y /public). 
           Tu form.vue toma esto como fallback si no hay VITE_BASE_URL --}}
      <script>
        window.APP_BASE_URL = "{{ url('') }}"; 
        window.USER_ROLE_MAIN = @json($mainRoleInjected);
      </script>

      <div id="blade_employee_form" data-user-role-main="{{ $mainRoleInjected }}"></div>

      <div id="blade_employee_modal_history"></div>
      <div id="blade_doc_history_modal"></div>

      <script>
        window.FOLLOW_PROPS = window.FOLLOW_PROPS || {};
        window.FOLLOW_PROPS.routes = {
          ...(window.FOLLOW_PROPS.routes || {}),
          employeeHistoryTmpl: "{{ route('employee.history', ['prof_id' => '__ID__']) }}",
          docHistoryTmpl: "{{ route('employee.document.history', ['doc_id' => '__ID__']) }}",
        };
        window.FOLLOW_PROPS.csrf = "{{ csrf_token() }}";
      </script>
    </div>
  </div>

  @push('scripts')
    @vite(['resources/js/app.js'])
  @endpush
</x-pages.page-app>
