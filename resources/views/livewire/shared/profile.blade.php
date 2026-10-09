<?php

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new
class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public $photo = null;

    public ?string $existingPhoto = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username ?? '';
        $this->existingPhoto = $user->photo;
    }

    public function save(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
        ];

        $photoPath = $this->storePhoto();
        if ($photoPath !== null) {
            $data['photo'] = $photoPath;
            $this->existingPhoto = $photoPath;
        }

        $user->update($data);

        $this->photo = null;

        $this->dispatch('user-profile-updated');
        $this->dispatch('notify', message: 'تم تحديث الملف الشخصي بنجاح', type: 'success');
        $this->dispatch('close-modal', 'profile');
    }

    private function storePhoto(): ?string
    {
        if (! $this->photo instanceof TemporaryUploadedFile) {
            return null;
        }

        $directory = 'users/'.now()->format('Y/m');
        $filename = Str::random(40).'.'.$this->photo->guessExtension();

        return $this->photo->storeAs($directory, $filename, 'public');
    }
}

?>

<div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'profile') open = true" @close-modal.window="if ($event.detail === 'profile') open = false">
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
                    class="w-full max-w-lg bg-white rounded-[var(--radius-xl)] shadow-2xl"
                    @click.stop
                >
                    <form wire:submit="save">
                        <div class="flex items-start justify-between gap-4 p-5 border-b border-[var(--color-border)]">
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">الملف الشخصي</h3>
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">تحديث بيانات حسابك وصورتك</p>
                            </div>
                            <button type="button" @click="open = false" class="p-1 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-5 space-y-4">
                            <div class="flex items-center gap-4">
                                @if($existingPhoto)
                                    <img src="{{ asset('media/'.ltrim($existingPhoto, '/')) }}" alt="" class="w-16 h-16 rounded-full object-cover ring-1 ring-[var(--color-border)]" />
                                @else
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white shrink-0" style="background: var(--accent-500);">
                                        {{ mb_substr($name ?: 'م', 0, 1) }}
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1">صورة الملف الشخصي</label>
                                    <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-[var(--color-text-secondary)] file:me-2 file:py-2 file:px-3 file:rounded-[var(--radius-md)] file:border-0 file:text-sm file:font-medium file:bg-[var(--accent-50)] file:text-[var(--accent-700)]" />
                                    @error('photo') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                                    <div wire:loading wire:target="photo" class="text-xs text-[var(--color-text-muted)] mt-1">جاري رفع الصورة…</div>
                                </div>
                            </div>

                            <div>
                                <x-ui.input label="الاسم" name="name" wire:model="name" />
                                @error('name') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-ui.input label="البريد الإلكتروني" name="email" type="email" wire:model="email" dir="ltr" />
                                @error('email') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-ui.input label="اسم المستخدم" name="username" wire:model="username" dir="ltr" />
                                @error('username') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
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
