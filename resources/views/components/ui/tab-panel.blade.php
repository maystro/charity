@props(['name'])

<div
    x-show="activeTab === '{{ $name }}'"
    x-cloak
    role="tabpanel"
    {{ $attributes }}
>
    {{ $slot }}
</div>
