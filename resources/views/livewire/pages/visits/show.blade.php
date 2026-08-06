@php
$status = $this->visitStatus;
$type = $this->visitTypeEnum;
@endphp

<div class="space-y-6">
    <x-layout.page-header title="تفاصيل الزيارة">
        <x-slot:actions>
            <a href="{{ route('visits.index') }}" wire:navigate>
                <x-ui.button variant="ghost" icon="arrow-right">العودة للقائمة</x-ui.button>
            </a>
            @if($this->canEdit)
                <a href="{{ route('visits.edit', $visit) }}" wire:navigate>
                    <x-ui.button variant="outline" icon="pencil">تعديل</x-ui.button>
                </a>
                <a href="{{ route('visits.execute', $visit) }}" wire:navigate>
                    <x-ui.button variant="primary" icon="play">تنفيذ الزيارة</x-ui.button>
                </a>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Header Card --}}
    <x-ui.card>
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <p class="text-xs text-[var(--color-text-muted)]">رقم الزيارة</p>
                <p class="text-lg font-bold text-[var(--color-text-primary)]">{{ $visit->visit_number ?? '—' }}</p>
            </div>
            <div class="me-auto">
                <x-ui.badge variant="{{ $status?->variant() ?? 'neutral' }}" size="lg">
                    {{ $status?->label() ?? $visit->status }}
                </x-ui.badge>
                @if($visit->is_overdue)
                    <span class="me-2 text-sm text-[var(--color-danger-500)] font-bold">متأخرة!</span>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{-- Info Grid --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        {{-- Main Info --}}
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">بيانات الزيارة</div>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">الأسرة</dt>
                    <dd class="text-sm font-medium">
                        <a href="{{ route('families.show', $visit->family) }}" wire:navigate class="text-[var(--accent-600)] hover:underline">
                            {{ $visit->family?->case_name }} ({{ $visit->family?->case_number }})
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">نوع الزيارة</dt>
                    <dd class="text-sm"><x-ui.badge variant="{{ $type?->variant() ?? 'neutral' }}">{{ $type?->label() ?? $visit->visit_type }}</x-ui.badge></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">الأولوية</dt>
                    <dd class="text-sm">{{ ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'مرتفعة', 'critical' => 'عاجلة جداً'][$visit->priority] ?? $visit->priority }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">الموعد</dt>
                    <dd class="text-sm">{{ $visit->scheduled_at?->format('Y/m/d H:i') ?? 'غير محدد' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">المدة المتوقعة</dt>
                    <dd class="text-sm">{{ $visit->duration_minutes ? $visit->duration_minutes.' دقيقة' : '—' }}</dd>
                </div>
                @if($visit->purpose)
                    <div>
                        <dt class="mb-1 text-sm text-[var(--color-text-muted)]">الغرض</dt>
                        <dd class="text-sm">{{ $visit->purpose }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        {{-- Researcher & Representative --}}
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">الفريق الميداني</div>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">الباحث</dt>
                    <dd class="text-sm font-medium">{{ $visit->researcher?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">المندوب</dt>
                    <dd class="text-sm font-medium">{{ $visit->representative?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">أُنشئت بواسطة</dt>
                    <dd class="text-sm">{{ $visit->creator?->name ?? '—' }}</dd>
                </div>
                @if($visit->completed_at)
                    <div class="flex justify-between">
                        <dt class="text-sm text-[var(--color-text-muted)]">تاريخ الإكمال</dt>
                        <dd class="text-sm">{{ $visit->completed_at->format('Y/m/d H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-[var(--color-text-muted)]">أكملها</dt>
                        <dd class="text-sm">{{ $visit->completer?->name ?? '—' }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        {{-- Location --}}
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">الموقع والعنوان</div>
            <dl class="space-y-3">
                <div>
                    <dt class="mb-1 text-sm text-[var(--color-text-muted)]">العنوان</dt>
                    <dd class="text-sm">{{ $visit->address_snapshot ?? '—' }}</dd>
                </div>
                @if($visit->latitude && $visit->longitude)
                    <div class="flex justify-between">
                        <dt class="text-sm text-[var(--color-text-muted)]">الإحداثيات</dt>
                        <dd class="text-sm">{{ $visit->latitude }}, {{ $visit->longitude }}</dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">تم التحقق من الموقع</dt>
                    <dd class="text-sm">{{ $visit->location_verified ? '✅ نعم' : '❌ لا' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Linked Entities --}}
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">الارتباطات</div>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">البحث الاجتماعي</dt>
                    <dd class="text-sm">{{ $visit->research?->research_number ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">طلب المساعدة</dt>
                    <dd class="text-sm">{{ $visit->aidRequest?->request_number ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </div>

    {{-- Outcome --}}
    @if($visit->outcome_summary || $visit->recommendations)
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">نتائج وتوصيات الزيارة</div>
            @if($visit->outcome_summary)
                <div class="mb-3">
                    <p class="mb-1 text-xs text-[var(--color-text-muted)]">ملخص النتائج</p>
                    <p class="text-sm">{{ $visit->outcome_summary }}</p>
                </div>
            @endif
            @if($visit->recommendations)
                <div>
                    <p class="mb-1 text-xs text-[var(--color-text-muted)]">التوصيات</p>
                    <p class="text-sm">{{ $visit->recommendations }}</p>
                </div>
            @endif
            @if($visit->next_follow_up_at)
                <div class="mt-3 flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">المتابعة القادمة</dt>
                    <dd class="text-sm font-medium text-[var(--accent-600)]">{{ $visit->next_follow_up_at->format('Y/m/d') }}</dd>
                </div>
            @endif
        </x-ui.card>
    @endif

    {{-- Status Timeline --}}
    @if($visit->statusHistories->isNotEmpty())
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">سجل تغييرات الحالة</div>
            <div class="space-y-3">
                @foreach($visit->statusHistories->sortByDesc('created_at') as $history)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 h-2 w-2 rounded-full bg-[var(--accent-500)] shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm">
                                @if($history->from_status)
                                    <x-ui.badge variant="neutral" size="sm">{{ App\Enums\VisitStatus::tryFrom($history->from_status)?->label() ?? $history->from_status }}</x-ui.badge>
                                    <span class="mx-1 text-[var(--color-text-muted)]">←</span>
                                @endif
                                <x-ui.badge variant="{{ App\Enums\VisitStatus::tryFrom($history->to_status)?->variant() ?? 'neutral' }}" size="sm">{{ App\Enums\VisitStatus::tryFrom($history->to_status)?->label() ?? $history->to_status }}</x-ui.badge>
                            </p>
                            <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                                {{ $history->changer?->name ?? 'النظام' }} — {{ $history->created_at->format('Y/m/d H:i') }}
                            </p>
                            @if($history->notes)
                                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">{{ $history->notes }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- Notes --}}
    @if($visit->notes)
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">ملاحظات</div>
            <p class="text-sm">{{ $visit->notes }}</p>
        </x-ui.card>
    @endif
</div>
