<div class="space-y-6">
    <x-layout.page-header
        title="المستخدمون والصلاحيات"
        subtitle="إدارة المستخدمين العاديين وتحديد عناصر القائمة المسموح لهم بها"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="openCreateModal">
                إضافة مستخدم
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card padding>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <x-ui.input
                    name="search"
                    placeholder="بحث بالاسم أو البريد أو اسم المستخدم..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="roleFilter" wire:model.live="roleFilter">
                <option value="">كل الأدوار</option>
                <option value="admin">مدير</option>
                <option value="user">مستخدم</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    <x-ui.card padding>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">المستخدم</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">الدور</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">الصلاحيات</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($users as $user)
                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors" wire:key="user-{{ $user->id }}">
                            <td class="px-4 py-3">
                                <div class="space-y-1">
                                    <p class="font-medium text-[var(--color-text-primary)]">{{ $user->name }}</p>
                                    <p class="text-xs text-[var(--color-text-muted)]" dir="ltr">{{ $user->username }} · {{ $user->email }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($user->role === 'admin')
                                    <x-ui.badge variant="success" dot>مدير</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" dot>مستخدم</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->role === 'user')
                                    <div class="flex flex-wrap gap-2">
                                        @forelse($user->menuAbilities() as $ability)
                                            <span class="inline-flex items-center rounded-full bg-[var(--color-primary-50)] px-2.5 py-1 text-xs text-[var(--color-primary-700)]">
                                                {{ $permissionLabels[$ability] ?? $ability }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-[var(--color-text-muted)]">لا توجد صلاحيات</span>
                                        @endforelse
                                    </div>
                                @else
                                    <span class="text-xs text-[var(--color-text-muted)]">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-ui.button variant="ghost" size="sm" icon="pencil" wire:click="openEditModal({{ $user->id }})">
                                        تعديل
                                    </x-ui.button>
                                    @if(auth()->id() !== $user->id)
                                        <x-ui.button
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="هل أنت متأكد من حذف هذا المستخدم؟"
                                        >
                                            حذف
                                        </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ui.empty-state icon="users" title="لا يوجد مستخدمون" description="لم يتم العثور على مستخدمين مطابقين." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </x-ui.card>

    @if($showForm)
        <div class="fixed inset-0 z-[var(--z-modal)]">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeForm"></div>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="w-full max-w-4xl bg-white rounded-[var(--radius-xl)] shadow-2xl">
                        <div class="flex items-start justify-between gap-4 p-5 border-b border-[var(--color-border)]">
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">
                                    {{ $editingUserId ? 'تعديل مستخدم' : 'إضافة مستخدم جديد' }}
                                </h3>
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">إدارة بيانات المستخدم والصلاحيات الخاصة به.</p>
                            </div>
                            <button type="button" wire:click="closeForm" class="p-1 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-5">
                            <form wire:submit="save" class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-ui.input label="الاسم" name="form.name" wire:model="form.name" />
                                        @error('form.name') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-ui.input label="البريد الإلكتروني" name="form.email" wire:model="form.email" dir="ltr" />
                                        @error('form.email') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-ui.input label="اسم المستخدم" name="form.username" wire:model="form.username" dir="ltr" />
                                        @error('form.username') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-ui.select label="الدور" name="form.role" wire:model.live="form.role">
                                            <option value="user">مستخدم</option>
                                            <option value="admin">مدير</option>
                                        </x-ui.select>
                                        @error('form.role') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-ui.input label="كلمة المرور" name="form.password" wire:model="form.password" type="password" dir="ltr" />
                                        @error('form.password') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <x-ui.input label="تأكيد كلمة المرور" name="form.password_confirmation" wire:model="form.password_confirmation" type="password" dir="ltr" />
                                        @error('form.password_confirmation') <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                @if(($form['role'] ?? 'user') === 'user')
                                    <div class="space-y-4">
                                        <div class="border-t border-[var(--color-border)] pt-4">
                                            <h3 class="text-sm font-semibold text-[var(--color-text-primary)]">الصلاحيات</h3>
                                            <p class="text-xs text-[var(--color-text-muted)] mt-1">اختر عناصر القائمة المسموح بها لهذا المستخدم.</p>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @foreach($permissionGroups as $group)
                                                <div class="rounded-2xl border border-[var(--color-border)] p-4 space-y-3">
                                                    <h4 class="text-sm font-semibold text-[var(--color-text-primary)]">{{ $group['label'] }}</h4>
                                                    <div class="space-y-2">
                                                        @foreach($group['items'] as $item)
                                                            <x-ui.checkbox
                                                                :label="$item['label']"
                                                                name="form.menu_abilities"
                                                                value="{{ $item['permission'] }}"
                                                                wire:model="form.menu_abilities"
                                                            />
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[var(--color-border)]">
                                    <x-ui.button type="button" variant="secondary" wire:click="closeForm">إلغاء</x-ui.button>
                                    <x-ui.button type="submit" variant="primary" icon="check">{{ $editingUserId ? 'حفظ التعديلات' : 'حفظ' }}</x-ui.button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
