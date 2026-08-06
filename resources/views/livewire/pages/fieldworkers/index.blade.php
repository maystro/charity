<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        title="المندوبون والباحثون"
        subtitle="إدارة المندوبين والباحثين الميدانيين والبحث عن الأسر التي قاموا بها"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="openCreateModal">
                إضافة مندوب
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث بالكود أو الاسم أو الهاتف..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="governorate" wire:model.live="governorate">
                <option value="">جميع المحافظات</option>
                @foreach($governorates as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="status" wire:model.live="status">
                <option value="">كل الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding>
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-[var(--color-text-muted)]">
                عدد المندوبين: <span class="font-semibold text-[var(--color-text-primary)]">{{ $fieldworkers->total() }}</span>
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">الكود</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">المندوب</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">المحافظة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">المنطقة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">الهاتف</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">عدد الأسر</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($fieldworkers as $fieldworker)
                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors" wire:key="fieldworker-{{ $fieldworker->id }}">
                            <td class="px-4 py-3 font-mono text-xs text-[var(--color-text-secondary)]">{{ $fieldworker->code }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($fieldworker->user?->photo)
                                        <img src="{{ Storage::url($fieldworker->user->photo) }}" alt="{{ $fieldworker->name }}" class="w-9 h-9 rounded-full object-cover ring-1 ring-[var(--color-border)]" />
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-[var(--color-primary-100)] text-[var(--color-primary-700)] flex items-center justify-center text-xs font-semibold">
                                            {{ mb_substr($fieldworker->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('fieldworkers.show', $fieldworker) }}" wire:navigate class="font-medium text-[var(--color-text-primary)] hover:text-[var(--accent-500)] transition-colors block truncate">
                                            {{ $fieldworker->name }}
                                        </a>
                                        <p class="text-xs text-[var(--color-text-muted)] font-mono" dir="ltr">{{ $fieldworker->user?->username ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $fieldworker->governorate ?? '—' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $fieldworker->area ?? '—' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]" dir="ltr">{{ $fieldworker->phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)] font-medium">{{ $fieldworker->families_count }}</td>
                            <td class="px-4 py-3">
                                @if($fieldworker->status === 'active')
                                    <x-ui.badge variant="success" dot>نشط</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" dot>غير نشط</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.button variant="ghost" size="sm" icon="eye" href="{{ route('fieldworkers.show', $fieldworker) }}" wire:navigate>
                                    عرض
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-ui.empty-state
                                    icon="user-group"
                                    title="لا يوجد مندوبون"
                                    description="لم يتم العثور على مندوبين مطابقين لمعايير البحث"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $fieldworkers->links() }}
        </div>
    </x-ui.card>

    {{-- Create Modal --}}
    <x-ui.modal name="create-fieldworker" title="إضافة مندوب جديد" size="lg">
        <form wire:submit="saveFieldworker" class="space-y-5" x-data="{ showPassword: false }">
            {{-- صورة المندوب --}}
            <div class="flex items-center gap-4">
                <div class="shrink-0">
                    @if($photo)
                        <img src="{{ $photo->temporaryUrl() }}" alt="صورة المندوب" class="w-20 h-20 rounded-full object-cover ring-2 ring-[var(--color-primary-200)]" />
                    @else
                        <div class="w-20 h-20 rounded-full bg-[var(--color-primary-100)] text-[var(--color-primary-600)] flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--color-primary-700);">صورة المندوب</label>
                    <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-[var(--color-text-secondary)] file:me-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[var(--color-primary-50)] file:text-[var(--color-primary-700)] hover:file:bg-[var(--color-primary-100)] cursor-pointer" />
                    @error('photo') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-[var(--color-text-muted)] mt-1">PNG أو JPG، بحد أقصى 2MB.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-ui.input label="كود المندوب" name="form.code" wire:model="form.code" />
                    @error('form.code') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-ui.input label="اسم المندوب" name="form.name" wire:model="form.name" placeholder="الاسم الكامل" />
                    @error('form.name') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-ui.input label="رقم الهاتف" name="form.phone" wire:model="form.phone" placeholder="01xxxxxxxxx" dir="ltr" />
                    @error('form.phone') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-ui.input label="المنطقة المسئول عنها" name="form.area" wire:model="form.area" placeholder="الحي / الشارع" />
                    @error('form.area') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.input label="المحافظة" name="form.governorate" wire:model="form.governorate" placeholder="القاهرة / الجيزة ..." />
                <div>
                    <x-ui.select label="الحالة" name="form.status" wire:model="form.status">
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </x-ui.select>
                    @error('form.status') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- فاصل: بيانات الدخول --}}
            <div class="pt-2 border-t border-[var(--color-border)]">
                <p class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider mb-3">بيانات الدخول إلى النظام</p>
            </div>

            <div>
                <x-ui.input label="اسم المستخدم" name="form.username" wire:model="form.username" placeholder="username" dir="ltr" />
                @error('form.username') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5" style="color: var(--color-primary-700);">كلمة المرور</label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            wire:model="form.password"
                            dir="ltr"
                            class="w-full h-11 px-4 pl-11 rounded-xl border text-base transition-colors focus:outline-none focus:ring-2"
                            style="border-color: var(--color-border-strong); background: white;"
                            placeholder="••••••••"
                        />
                        <button type="button" @click="showPassword = !showPassword" class="absolute left-3 top-1/2 -translate-y-1/2 p-1 text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
                            <svg class="w-5 h-5" x-show="!showPassword" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg class="w-5 h-5" x-show="showPassword" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L17.772 17.772M6.228 6.228a3 3 0 11-4.243-4.243M17.772 17.772a3 3 0 11-4.243 4.243"/></svg>
                        </button>
                    </div>
                    @error('form.password') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-ui.input label="تأكيد كلمة المرور" name="form.password_confirmation" type="password" wire:model="form.password_confirmation" dir="ltr" placeholder="••••••••" />
                    @error('form.password_confirmation') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-[var(--color-border)]">
                <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'create-fieldworker')">إلغاء</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="check">حفظ</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    @if($showCreateModal)
        <script>
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'create-fieldworker' }));
        </script>
    @endif
</div>