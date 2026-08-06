@php
    $isReview      = $status === \App\Enums\FamilyStatus::UnderReview;
    $isDraft       = $status === \App\Enums\FamilyStatus::Draft;
    $pageTitle     = $isReview ? 'حالات تحت المراجعة' : ($isDraft ? 'المسودات' : 'الأسر والحالات');
    $pageSubtitle  = $isReview ? 'الأسر المرسلة للمراجعة والاعتماد' : ($isDraft ? 'المسودات المحفوظة — يمكنك استكمالها وإرسالها' : 'إدارة وتسجيل الأسر المستفيدة المعتمدة');
    $dateColumn    = $isReview ? 'تاريخ الإرسال' : ($isDraft ? 'تاريخ الإنشاء' : 'تاريخ الاعتماد');
    $dateField     = $isReview ? 'submitted_at' : ($isDraft ? 'created_at' : 'approved_at');
    $showRoute     = $isReview ? 'families.review-show' : 'families.show';
    $emptyMessage  = $isReview ? 'لا توجد حالات تحت المراجعة' : ($isDraft ? 'لا توجد مسودات محفوظة' : 'لا توجد أسر معتمدة');
@endphp

<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        :title="$pageTitle"
        :subtitle="$pageSubtitle"
    >
        <x-slot:actions>
            @if($isReview)
                <x-ui.button variant="secondary" icon="user-group" href="{{ route('families.index') }}" wire:navigate>
                    الأسر المعتمدة
                </x-ui.button>
            @elseif($isDraft)
                <x-ui.button variant="secondary" icon="user-group" href="{{ route('families.index') }}" wire:navigate>
                    الأسر المعتمدة
                </x-ui.button>
            @else
                <x-ui.button variant="secondary" icon="hand-raised" href="{{ route('aid-requests.create') }}" wire:navigate>
                    إضافة طلب مساعدة
                </x-ui.button>
                <x-ui.button variant="primary" icon="user-group" href="{{ route('families.create') }}" wire:navigate>
                    إضافة أسرة جديدة
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="flex items-center gap-4 flex-wrap">
            {{-- Status filter: drafts only (under_review has its own sidebar link) --}}
            @unless($isReview)
                <x-ui.select name="status" wire:model.live="statusFilter">
                    <option value="">الأسر المعتمدة</option>
                    <option value="draft">المسودات</option>
                </x-ui.select>
            @endunless

            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث برقم الحالة أو الاسم أو الهاتف..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="community" wire:model.live="community">
                <option value="">جميع المناطق</option>
                @foreach($communities as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="case_type" wire:model.live="caseType">
                <option value="">جميع أنواع الحالات</option>
                @foreach($caseTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            @unless($isReview || $isDraft)
                <x-ui.select name="sort" wire:model.live="sort">
                    <option value="newest">الأحدث اعتماداً</option>
                    <option value="oldest">الأقدم اعتماداً</option>
                </x-ui.select>
            @endunless
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding>
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-[var(--color-text-muted)]">
                عدد الحالات: <span class="font-semibold text-[var(--color-text-primary)]">{{ $families->total() }}</span>
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">رقم الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">اسم الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المنطقة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الهاتف</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">نوع الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الأفراد</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">إجمالي الدخل</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">متوسط الفرد</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">{{ $dateColumn }}</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($families as $family)
                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors" wire:key="family-{{ $family->id }}">
                            <td class="px-4 py-3 font-mono text-xs text-[var(--color-text-secondary)]">{{ $family->case_number }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route($showRoute, $family) }}" wire:navigate class="font-medium text-[var(--color-text-primary)] hover:text-[var(--accent-500)] transition-colors">
                                    {{ $family->case_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $family->community ?? '—' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]" dir="ltr">{{ $family->phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $family->case_type }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $family->members_count }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)] font-medium">{{ number_format((float) $family->total_income, 2) }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)] font-medium">{{ number_format((float) $family->average_income_per_person, 2) }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)] text-xs">{{ $family->{$dateField}?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    {{-- View (review-show for under_review, show for approved) --}}
                                    <a href="{{ route($showRoute, $family) }}" wire:navigate class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--accent-500)] hover:bg-[var(--color-bg-secondary)] transition-colors" title="عرض">
                                        <x-heroicon-o-eye class="w-4.5 h-4.5" />
                                    </a>

                                    @if($isReview)
                                        {{-- Review: go to review-show page for approval --}}
                                        <a href="{{ route('families.review-show', $family) }}" wire:navigate class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-success-500)] hover:bg-[var(--color-success-50)] transition-colors" title="مراجعة واعتماد">
                                            <x-heroicon-o-clipboard-document-check class="w-4.5 h-4.5" />
                                        </a>
                                    @elseif($isDraft)
                                        {{-- Draft: resume editing + delete --}}
                                        <a href="{{ route('families.edit', $family) }}" wire:navigate class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--accent-500)] hover:bg-[var(--color-bg-secondary)] transition-colors" title="استكمال">
                                            <x-heroicon-o-pencil class="w-4.5 h-4.5" />
                                        </a>
                                        <button wire:click="delete({{ $family->id }})" wire:confirm="هل أنت متأكد من حذف هذه المسودة؟" class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors" title="حذف">
                                            <x-heroicon-o-trash class="w-4.5 h-4.5" />
                                        </button>
                                    @else
                                        {{-- Approved: edit + aid request + delete --}}
                                        <a href="{{ route('families.edit', $family) }}" wire:navigate class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--accent-500)] hover:bg-[var(--color-bg-secondary)] transition-colors" title="تعديل">
                                            <x-heroicon-o-pencil class="w-4.5 h-4.5" />
                                        </a>
                                        <a href="{{ route('aid-requests.create') }}" wire:navigate class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--accent-500)] hover:bg-[var(--color-bg-secondary)] transition-colors" title="إنشاء طلب مساعدة">
                                            <x-heroicon-o-hand-raised class="w-4.5 h-4.5" />
                                        </a>
                                        <button wire:click="delete({{ $family->id }})" wire:confirm="هل أنت متأكد من حذف هذه الأسرة؟" class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors" title="حذف">
                                            <x-heroicon-o-trash class="w-4.5 h-4.5" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-[var(--color-text-muted)]">{{ $emptyMessage }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 border-t border-[var(--color-border)] pt-4">
            {{ $families->links() }}
        </div>
    </x-ui.card>
</div>
