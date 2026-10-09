<?php

use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Component;

new
class extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Hash::check((string) $value, (string) auth()->user()?->password)) {
                        $fail(__('validation.current_password'));
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'different:current_password', 'confirmed'],
        ]);

        auth()->user()?->update([
            'password' => $this->password,
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('notify', message: 'تم تحديث كلمة المرور بنجاح', type: 'success');
        $this->dispatch('close-modal', 'change-password');
    }
}

?>

<div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'change-password') open = true" @close-modal.window="if ($event.detail === 'change-password') open = false">
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[var(--z-modal)]"
        @keydown.escape.window="open = false"
    >
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="open = false"></div>

        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="w-full max-w-md bg-white rounded-[var(--radius-xl)] shadow-2xl"
                    @click.stop
                >
                    <form wire:submit="updatePassword">
                        <div class="flex items-start justify-between gap-4 p-5 border-b border-[var(--color-border)]">
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">تغيير كلمة المرور</h3>
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">أدخل كلمة المرور الحالية ثم كلمة مرور جديدة</p>
                            </div>
                            <button type="button" @click="open = false" class="p-1 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-5 space-y-4">
                            <div>
                                <x-ui.input label="كلمة المرور الحالية" name="current_password" type="password" wire:model="current_password" dir="ltr" placeholder="••••••••" autocomplete="current-password" />
                                @error('current_password') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-ui.input label="كلمة المرور الجديدة" name="password" type="password" wire:model="password" dir="ltr" placeholder="••••••••" autocomplete="new-password" />
                                @error('password') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-ui.input label="تأكيد كلمة المرور" name="password_confirmation" type="password" wire:model="password_confirmation" dir="ltr" placeholder="••••••••" autocomplete="new-password" />
                                @error('password_confirmation') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 p-5 border-t border-[var(--color-border)]">
                            <x-ui.button type="button" variant="secondary" @click="open = false">إلغاء</x-ui.button>
                            <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">حفظ</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
