<!DOCTYPE html>
@php
    $organizationName = \App\Models\SystemSetting::get('organization_name', config('app.name', 'منشأة خيرية'));
    $organizationTagline = \App\Models\SystemSetting::get('organization_tagline', 'نظام إدارة منشأة خيرية');
@endphp
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'تسجيل الدخول' }} - {{ $organizationName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800">
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body>
    {{ $slot }}
    <x-ui.toast />
    @livewireScripts
</body>
</html>
