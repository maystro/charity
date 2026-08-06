<x-ui.alert {{ $attributes->merge(['variant' => $variant ?? 'danger']) }}>
    @if($title ?? false)
        <x-slot:title>{{ $title }}</x-slot:title>
    @endif
    @if($slot->isEmpty())
        {{ $message ?? __('يوجد خطأ في البيانات المدخلة.') }}
    @else
        {{ $slot }}
    @endif
</x-ui.alert>
