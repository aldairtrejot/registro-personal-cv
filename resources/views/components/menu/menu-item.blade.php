@props([
    'label' => '_PAGE',
    'href' => '#',
    'isNew' => false,
])

<a class="dropdown-item" href="{{ $href }}">
    {{ $label }}
    @if ($isNew)
        <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
    @endif
</a>
