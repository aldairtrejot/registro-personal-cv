<div class="dropend">
    <a class="dropdown-item dropdown-toggle show" href="#sidebar-cards" data-bs-toggle="dropdown"
        data-bs-auto-close="outside" role="button" aria-expanded="true">
        {{ $title }}
    </a>
    <div class="dropdown-menu show" data-bs-popper="static">
        {{ $slot }}
    </div>
</div>
