<!DOCTYPE html>
@php
    $prefs = auth()->user()?->preferences;
    $accent      = $prefs?->accent_color  ?: 'copper';
    $fontSize    = $prefs?->font_size     ?: 'medium';
    $uiDensity   = $prefs?->ui_density    ?: 'balanced';
    $reducedMotion = $prefs?->reduced_motion ? 'true' : 'false';
@endphp
<html
    lang="ar"
    dir="rtl"
    data-accent="{{ $accent }}"
    data-font-size="{{ $fontSize }}"
    data-ui-density="{{ $uiDensity }}"
    data-reduced-motion="{{ $reducedMotion }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'لوحة التحكم' }} - {{ config('app.name', 'منشأة خيرية') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800">

    {{-- Server preferences (source of truth) for the client Theme Manager --}}
    <script>
        window.SERVER_PREFS = {!! json_encode([
            'accent' => $accent,
            'fontSize' => $fontSize,
            'density' => $uiDensity,
            'reducedMotion' => $reducedMotion,
        ]) !!};
    </script>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="bg-[var(--accent-50)] text-[var(--color-text-primary)] antialiased">
    <div id="app-shell" class="flex h-screen overflow-hidden" style="gap: var(--shell-gap); padding: var(--outer-gap);">
        {{-- Sidebar (right column) --}}
        <livewire:sidebar />
        <livewire:user-preferences />

        {{-- Left column: TOP_BAR on top, main content below --}}
        <div class="flex-1 flex flex-col h-full" style="gap: var(--shell-gap);">
            {{-- TOP_BAR --}}
            <header id="TOP_BAR" class="glass shrink-0 rounded-[var(--radius-xl)] flex items-center gap-3 px-4 sm:px-6 overflow-visible" style="height: 87px; background: var(--color-sidebar-bg); backdrop-filter: blur(var(--glass-blur)); border: 1px solid var(--glass-border);">
                {{-- Mobile sidebar toggle (left in RTL = visually left edge) --}}
                <button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('toggle-mobile-sidebar'))"
                    class="lg:hidden p-2 rounded-lg hover:bg-black/5 transition-colors shrink-0"
                    aria-label="{{ __('ui.open_menu') }}"
                >
                    <svg class="w-6 h-6" style="color: var(--color-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>

                {{-- Right side in RTL (first child): alert stat tiles --}}
                @auth
                    @if (! auth()->user()->isSuperAdmin())
                        {{-- ١. حالات بانتظار الاعتماد --}}
                        <livewire:shared.pending-approvals-stat />

                        {{-- ٢. إعادة التقييم المتأخرة --}}
                        <livewire:shared.re-assessment-alerts-stat />

                        {{-- ٣. طلبات المساعدة الجديدة --}}
                        <livewire:shared.new-aid-requests-stat />
                    @endif
                @endauth

                {{-- Spacer pushes the user menu to the left (in RTL) --}}
                <div class="flex-1"></div>

                {{-- Left side in RTL: user menu --}}
                <button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-preferences' }))"
                    class="inline-flex items-center gap-2 p-2 rounded-[var(--radius-lg)] hover:bg-black/5 transition-colors shrink-0"
                    aria-label="{{ __('ui.user_menu') }}"
                >
                    <span class="w-9 h-9 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm" style="background: var(--accent-500);">
                        {{ mb_substr(auth()->user()?->name ?? 'م', 0, 1) }}
                    </span>
                </button>
            </header>

            {{-- Main Content --}}
            <main id="main-content" class="flex-1 min-w-0 flex flex-col rounded-[var(--radius-xl)] bg-[var(--color-content-bg)] shadow-sm overflow-hidden">
                {{-- Page Content — consistent full-width container across all pages --}}
                <div class="flex-1 overflow-y-auto p-6 scrollbar-thin">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    {{-- Global Toast -- renders notify events dispatched by Livewire components --}}
    <x-ui.toast />

    {{-- Offline Overlay --}}
    <div id="offline-overlay" class="fixed inset-0 z-[var(--z-overlay)] bg-black/30 backdrop-blur-sm hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 shadow-xl text-center max-w-md mx-4">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.242 2.829a5 5 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2">انقطع الاتصال بالإنترنت</h2>
            <p class="text-[var(--color-text-secondary)] mb-4">سيتم استئناف العمل تلقائيًا عند عودة الاتصال.</p>
            <div class="flex items-center justify-center gap-2 text-sm text-[var(--color-text-muted)]">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>جاري إعادة المحاولة...</span>
            </div>
        </div>
    </div>

    {{-- Session Expired Modal --}}
    <div id="session-modal" class="fixed inset-0 z-[var(--z-modal)] bg-black/50 backdrop-blur-sm hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 shadow-xl text-center max-w-md mx-4">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold mb-2">انتهت جلسة العمل</h2>
            <p class="text-[var(--color-text-secondary)] mb-6">انتهت الجلسة بسبب عدم النشاط. يرجى تسجيل الدخول مرة أخرى لحماية بيانات النظام.</p>
            <a href="/login" class="inline-block px-6 py-3 rounded-xl bg-[var(--accent-500)] text-white font-semibold hover:opacity-90 transition">
                تسجيل الدخول من جديد
            </a>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
