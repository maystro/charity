<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        title="تاريخ التقييمات"
        :subtitle="'الأسرة: ' . $family->currentAssessment?->case_name . ' — رقم الحالة: ' . $family->case_number"
        :breadcrumbs="[
            ['label' => 'الأسر والحالات', 'route' => 'families.index'],
            ['label' => 'إعادة التقييم', 'route' => 'families.re-assessment-index'],
            ['label' => $family->case_number],
        ]"
    />

    {{-- Timeline --}}
    <x-ui.card padding>
        <div class="space-y-6">
            @foreach($assessments as $assessment)
                <div class="relative border border-[var(--color-border)] rounded-[var(--radius-lg)] p-5 {{ $assessment->id === $family->current_assessment_id ? 'ring-2 ring-[var(--accent-500)]/30' : '' }}">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm {{ $assessment->id === $family->current_assessment_id ? 'bg-[var(--accent-500)] text-white' : 'bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)]' }}">
                                {{ $assessment->round }}
                            </div>
                            <div>
                                <h3 class="font-semibold text-[var(--color-text-primary)]">
                                    التقييم رقم {{ $assessment->round }}
                                    @if($assessment->id === $family->current_assessment_id)
                                        <x-ui.badge variant="success" size="sm" class="mr-2">الحالي</x-ui.badge>
                                    @endif
                                </h3>
                                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                                    {{ $assessment->created_at?->format('Y/m/d H:i') }}
                                    — الباحث: {{ $assessment->creator?->name ?? '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @php
                                $status = \App\Enums\FamilyStatus::tryFrom($assessment->status);
                            @endphp
                            @if($status)
                                <x-ui.badge :variant="$status->variant()" size="sm" dot>{{ $status->label() }}</x-ui.badge>
                            @endif
                        </div>
                    </div>

                    {{-- Summary grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-[var(--color-text-muted)] mb-1">نوع الحالة</p>
                            <p class="font-medium text-[var(--color-text-primary)]">{{ $assessment->case_type }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-[var(--color-text-muted)] mb-1">الأفراد</p>
                            <p class="font-medium text-[var(--color-text-primary)]">{{ $assessment->members_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-[var(--color-text-muted)] mb-1">إجمالي الدخل</p>
                            <p class="font-medium text-[var(--color-text-primary)]">{{ number_format((float) $assessment->total_income, 2) }} ج.م</p>
                        </div>
                        <div>
                            <p class="text-xs text-[var(--color-text-muted)] mb-1">متوسط الفرد</p>
                            <p class="font-medium text-[var(--color-text-primary)]">{{ number_format((float) $assessment->average_income_per_person, 2) }} ج.م</p>
                        </div>
                    </div>

                    {{-- Approval info --}}
                    @if($assessment->approved_at)
                        <div class="mt-4 pt-4 border-t border-[var(--color-border)]">
                            <p class="text-xs text-[var(--color-text-muted)]">
                                تم الاعتماد بتاريخ {{ $assessment->approved_at->format('Y/m/d H:i') }}
                                — بواسطة {{ $assessment->approver?->name ?? '—' }}
                                @if($assessment->review_notes)
                                    — ملاحظات: {{ $assessment->review_notes }}
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Rejection info --}}
                    @if($assessment->rejected_at)
                        <div class="mt-4 pt-4 border-t border-[var(--color-border)]">
                            <p class="text-xs text-[var(--color-danger-500)]">
                                تم الرفض بتاريخ {{ $assessment->rejected_at->format('Y/m/d H:i') }}
                                — سبب: {{ $assessment->rejection_reason ?? '—' }}
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-ui.card>
</div>
