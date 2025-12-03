<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#navbar-form" data-bs-toggle="dropdown" data-bs-auto-close="outside"
        role="button" aria-expanded="false">
        <i class="{{ $icon }}" style="font-size: 1.2rem;"></i>
        <span class="nav-link-title">&nbsp;{{ $title }}</span>
    </a>
    <div class="dropdown-menu">
        {{ $slot }}
    </div>
</li>
