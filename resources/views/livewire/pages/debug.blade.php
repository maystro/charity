<div class="flex items-center justify-center min-h-[60vh]">
    <div class="w-full max-w-2xl">
        <div class="glass rounded-[var(--radius-2xl)] overflow-hidden" style="border: 1px solid var(--color-border-strong);">
            {{-- Header --}}
            <div class="px-6 py-5 flex items-center gap-4" style="background: var(--accent-50); border-bottom: 1px solid var(--color-border);">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0" style="background: var(--color-danger-50);">
                    <svg class="w-6 h-6 text-[var(--color-danger-500)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-[var(--color-text-primary)]">خطأ في الخادم</h1>
                    <p class="text-sm text-[var(--color-text-muted)] mt-0.5">تفاصيل الخطأ الداخلية</p>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">
                {{-- Error Source --}}
                <div class="rounded-xl p-4" style="background: var(--color-bg-secondary); border: 1px solid var(--color-border);">
                    <p class="text-xs font-semibold text-[var(--color-text-muted)] mb-1">مصدر الخطأ</p>
                    <p class="text-sm font-medium text-[var(--color-text-primary)] break-all">App\Livewire\Debug::render()</p>
                </div>

                {{-- File --}}
                <div class="rounded-xl p-4" style="background: var(--color-bg-secondary); border: 1px solid var(--color-border);">
                    <p class="text-xs font-semibold text-[var(--color-text-muted)] mb-1">اسم الملف</p>
                    <p class="text-sm font-medium text-[var(--color-text-primary)] break-all">app/Livewire/Debug.php</p>
                </div>

                {{-- Line Number --}}
                <div class="rounded-xl p-4" style="background: var(--color-bg-secondary); border: 1px solid var(--color-border);">
                    <p class="text-xs font-semibold text-[var(--color-text-muted)] mb-1">رقم السطر</p>
                    <p class="text-sm font-medium text-[var(--color-text-primary)]">12</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 flex items-center justify-between" style="background: var(--color-bg-secondary); border-top: 1px solid var(--color-border);">
                <span class="text-xs text-[var(--color-text-muted)]">يرجى التواصل مع الدعم الفني إذا استمر الخطأ.</span>
                <button onclick="history.back()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white transition hover:opacity-90" style="background: var(--accent-500);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                    </svg>
                    رجوع
                </button>
            </div>
        </div>
    </div>
</div>
