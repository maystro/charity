<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('layouts.auth', ['title' => 'تسجيل الدخول'])]
class extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;
    public bool $showPassword = false;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect($this->homeRoute());
        }
    }

    public function login(): void
    {
        $this->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = \App\Models\User::where('username', $this->username)
            ->first();

        if ($user && \Illuminate\Support\Facades\Hash::check($this->password, $user->password)) {
            Auth::login($user, $this->remember);
            session()->regenerate();
            $this->redirect($this->homeRoute(), navigate: true);

            return;
        }

        $this->addError('username', 'بيانات الدخول غير صحيحة.');
    }

    protected function homeRoute(): string
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            return route('deployments.index');
        }

        return route('dashboard');
    }
}

?>

@php
    $organizationName = \App\Models\SystemSetting::get('organization_tagline', config('app.name', 'منشأة خيرية'));
    $organizationTagline = \App\Models\SystemSetting::get('organization_name', 'نظام إدارة منشأة خيرية');
    $organizationLogoPath = \App\Models\SystemSetting::get('organization_logo_path');
    $organizationLogoUrl = $organizationLogoPath && Storage::disk('public')->exists($organizationLogoPath)
        ? asset('media/'.ltrim($organizationLogoPath, '/'))
        : null;
@endphp

<div class="min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, #2a1c12 0%, #4d3422 40%, #64432b 70%, #7a5535 100%);">
    <div id="progress-bar"></div>

    <div class="w-full max-w-[440px]">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-40 h-40 rounded-2xl bg-white backdrop-blur-sm border border-white/20 mb-4 overflow-hidden shadow-sm">
                @if($organizationLogoUrl)
                    <img src="{{ $organizationLogoUrl }}" alt="{{ $organizationName }}" class="w-full h-full object-contain p-2 bg-white" />
                @else
                    <svg class="w-20 h-20 text-[var(--accent-500)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                @endif
            </div>
            <h1 class="text-2xl font-bold text-white">{{ $organizationName }}</h1>
            <p class="text-white/60 text-sm mt-1">{{ $organizationTagline }}</p>
        </div>

        <div class="glass-strong rounded-3xl p-8 shadow-2xl" style="background: rgba(255,255,255,0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.3);">
            <h2 class="text-xl font-bold text-center mb-6" style="color: var(--color-primary-800);">تسجيل الدخول</h2>

            <form wire:submit="login" class="space-y-5">
                <div>
                    <label for="username" class="block text-sm font-medium mb-1.5" style="color: var(--color-primary-700);">اسم المستخدم</label>
                    <input
                        type="text"
                        id="username"
                        wire:model="username"
                        autocomplete="username"
                        class="w-full h-12 px-4 rounded-xl border text-base transition-colors focus:outline-none focus:ring-2"
                        style="border-color: var(--color-border-strong); background: white;"
                        placeholder="أدخل اسم المستخدم"
                    />
                    @error('username')
                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1.5" style="color: var(--color-primary-700);">كلمة المرور</label>
                    <div class="relative">
                        <input
                            type="{{ $showPassword ? 'text' : 'password' }}"
                            id="password"
                            wire:model="password"
                            autocomplete="current-password"
                            class="w-full h-12 px-4 pl-12 rounded-xl border text-base transition-colors focus:outline-none focus:ring-2"
                            style="border-color: var(--color-border-strong); background: white;"
                            placeholder="أدخل كلمة المرور"
                        />
                        <button
                            type="button"
                            wire:click="$toggle('showPassword')"
                            class="absolute left-3 top-1/2 -translate-y-1/2 p-1 rounded-lg hover:bg-gray-100 transition-colors"
                            tabindex="-1"
                        >
                            @if($showPassword)
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="remember"
                        wire:model="remember"
                        class="w-4 h-4 rounded border-gray-300"
                        style="accent-color: var(--accent-500);"
                    />
                    <label for="remember" class="text-sm" style="color: var(--color-text-secondary);">تذكر الدخول</label>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-70 cursor-not-allowed"
                    class="w-full h-12 rounded-xl inline-flex items-center justify-center text-white font-semibold text-base transition-all hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background: var(--accent-500);"
                >
                    <svg wire:loading wire:target="login" class="w-5 h-5 animate-spin shrink-0 me-[10px]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="login" class="whitespace-nowrap">دخول</span>
                    <span wire:loading wire:target="login" class="whitespace-nowrap">جاري الدخول...</span>
                </button>
            </form>
        </div>
    </div>
</div>
