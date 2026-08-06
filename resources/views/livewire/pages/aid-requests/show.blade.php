@php
    $printOrganizationName = (string) \App\Models\SystemSetting::get('organization_name', 'جمعية التضامن الاجتماعي - بني سويف');
    $printOrganizationTagline = (string) \App\Models\SystemSetting::get('organization_tagline', 'جمعية عهد الخير للتنمية والخدمات');
    $printLogoPath = \App\Models\SystemSetting::get('organization_logo_path');
    $printLogoUrl = $printLogoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($printLogoPath)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($printLogoPath)
        : null;
@endphp

<div class="space-y-6">
    {{-- Print-only Header --}}
    <div class="print-header no-print hidden" id="print-header">
        @if($printLogoUrl)
            <div class="print-logo">
                <img src="{{ $printLogoUrl }}" alt="{{ $printOrganizationName }}" />
            </div>
        @endif
        <div class="org-name">{{ $printOrganizationName }}</div>
        <div class="org-subname">{{ $printOrganizationTagline }}</div>
        <div class="page-title">طلب مساعدة</div>
        <div class="request-number">#{{ $aidRequest->request_number }} - {{ $aidRequest->title }}</div>
    </div>

    {{-- Page Header --}}
    <x-layout.page-header
        title="طلب مساعدة #{{ $aidRequest->request_number }}"
        subtitle="{{ $aidRequest->title }}"
        :breadcrumbs="[
            ['label' => 'طلبات المساعدة', 'route' => 'aid-requests.index'],
            ['label' => $aidRequest->request_number],
        ]"
    >
        <x-slot:actions>
            @if($aidRequest->status === 'draft')
                <x-ui.button
                    variant="primary"
                    icon="pencil"
                    href="{{ route('aid-requests.edit', $aidRequest) }}"
                    wire:navigate
                >
                    تعديل
                </x-ui.button>
            @endif
            <x-ui.button variant="outline" icon="printer" onclick="window.print()" class="no-print">طباعة</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Status Bar --}}
    <x-ui.card padding>
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">رقم الطلب</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $aidRequest->request_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">الأسرة</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $aidRequest->family?->case_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">نوع الطلب</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $aidRequest->request_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">تاريخ الإنشاء</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $aidRequest->created_at->format('Y/m/d') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $status = $aidRequest->status instanceof \BackedEnum ? $aidRequest->status->value : $aidRequest->status;
                    $statusEnum = \App\Enums\AidRequestStatus::tryFrom($status);
                    $statusVariant = $statusEnum?->variant() ?? 'secondary';
                    $statusLabel = $statusEnum?->label() ?? $status;
                @endphp
                <x-ui.badge
                    :variant="$statusVariant"
                    size="md"
                    dot
                >
                    {{ $statusLabel }}
                </x-ui.badge>
            </div>
        </div>
    </x-ui.card>

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Request Info --}}
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">تفاصيل الطلب</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">مصدر الطلب</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]">{{ $aidRequest->request_source ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">الأولوية</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]">{{ $aidRequest->priority ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">وصف الاحتياج</dt>
                        <dd class="text-sm text-[var(--color-text-primary)] leading-relaxed">
                            {{ $aidRequest->need_description ?? '—' }}
                        </dd>
                    </div>
                    @if($aidRequest->internal_notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">ملاحظات داخلية</dt>
                            <dd class="text-sm text-[var(--color-text-primary)] leading-relaxed bg-[var(--color-bg-secondary)] rounded-lg p-3">
                                {{ $aidRequest->internal_notes }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>

            {{-- Items --}}
            @php
                $visibleItems = $this->visibleItems;
                $canReviewItems = $this->canReviewItems;
                $isReviewable = $this->isReviewable;
            @endphp
            @if($visibleItems->count())
                <x-ui.card padding>
                    <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">
                        @if($canReviewItems)
                            بنود المساعدة
                            <span class="text-xs font-normal text-[var(--color-text-muted)] mr-2">
                                ({{ $visibleItems->count() }} بند)
                            </span>
                        @else
                            البنود المعتمدة
                            <span class="text-xs font-normal text-[var(--color-text-muted)] mr-2">
                                ({{ $visibleItems->count() }} بند معتمد)
                            </span>
                        @endif
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b" style="border-color: var(--color-border);">
                                    @if($canReviewItems && $isReviewable)
                                        <th class="text-right py-2 px-3 text-xs font-medium text-[var(--color-text-muted)] w-10">اعتماد</th>
                                    @endif
                                    <th class="text-right py-2 px-3 text-xs font-medium text-[var(--color-text-muted)]">#</th>
                                    <th class="text-right py-2 px-3 text-xs font-medium text-[var(--color-text-muted)]">البند</th>
                                    <th class="text-right py-2 px-3 text-xs font-medium text-[var(--color-text-muted)]">الكمية</th>
                                    <th class="text-right py-2 px-3 text-xs font-medium text-[var(--color-text-muted)]">التكلفة التقديرية</th>
                                    @if($canReviewItems)
                                        <th class="text-right py-2 px-3 text-xs font-medium text-[var(--color-text-muted)]">الحالة</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--color-border);">
                                @foreach($visibleItems as $i => $item)
                                    <tr class="hover:bg-[var(--color-bg-secondary)] transition-colors">
                                        @if($canReviewItems && $isReviewable)
                                            <td class="py-2.5 px-3">
                                                <label class="inline-flex items-center justify-center">
                                                    <input
                                                        type="checkbox"
                                                        class="w-4 h-4 rounded border-[var(--color-border)] text-[var(--accent-600)] focus:ring-[var(--accent-500)]"
                                                        value="{{ $item->id }}"
                                                        wire:change="toggleApproval({{ $item->id }})"
                                                        @checked(in_array($item->id, $approvedItemIds))
                                                    />
                                                </label>
                                            </td>
                                        @endif
                                        <td class="py-2.5 px-3 text-[var(--color-text-muted)]">{{ $i + 1 }}</td>
                                        <td class="py-2.5 px-3 font-medium text-[var(--color-text-primary)]">{{ $item->title }}</td>
                                        <td class="py-2.5 px-3 text-[var(--color-text-secondary)]">{{ $item->quantity }}</td>
                                        <td class="py-2.5 px-3 text-[var(--color-text-secondary)]">
                                            {{ $item->estimated_total ? number_format($item->estimated_total, 2) . ' ج.م' : '—' }}
                                        </td>
                                        @if($canReviewItems)
                                            <td class="py-2.5 px-3">
                                                @if($item->approved)
                                                    <x-ui.badge variant="success" size="sm" dot>معتمد</x-ui.badge>
                                                @else
                                                    <x-ui.badge variant="secondary" size="sm">غير معتمد</x-ui.badge>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t" style="border-color: var(--color-border);">
                                    <td colspan="{{ ($canReviewItems ? 2 : 1) + 2 }}" class="py-2.5 px-3 text-sm font-semibold text-left text-[var(--color-text-primary)]">الإجمالي التقديري</td>
                                    <td class="py-2.5 px-3 font-bold text-[var(--accent-600)]">
                                        {{ number_format($visibleItems->where('approved', true)->sum('estimated_total'), 2) }} ج.م
                                    </td>
                                    @if($canReviewItems)<td></td>@endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-ui.card>

                {{-- Review controls --}}
                @if($canReviewItems && $isReviewable)
                    <x-ui.card padding>
                        <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">إجراءات الاعتماد</h2>
                        <div class="space-y-4">
                            <x-ui.textarea
                                label="ملاحظات المراجع"
                                name="reviewNotes"
                                placeholder="ملاحظات الاعتماد أو سبب الرفض..."
                                rows="3"
                                wire:model="reviewNotes"
                            />

                            <div class="flex items-center gap-3 flex-wrap">
                                <x-ui.button
                                    variant="primary"
                                    icon="check"
                                    wire:click="saveApproval"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading wire:target="saveApproval">جار الحفظ...</span>
                                    <span wire:loading.remove wire:target="saveApproval">حفظ الاعتماد</span>
                                </x-ui.button>
                                <x-ui.button
                                    variant="danger"
                                    icon="x-mark"
                                    wire:click="rejectRequest"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading wire:target="rejectRequest">جار الرفض...</span>
                                    <span wire:loading.remove wire:target="rejectRequest">رفض الطلب</span>
                                </x-ui.button>
                            </div>
                            <p class="text-xs text-[var(--color-text-muted)]">
                                عند الحفظ: تعتمد البنود المحددة فقط، ويتحول الطلب إلى «معتمد» إن اعتمدت كل البنود، أو «معتمد جزئياً» إن اعتمدت بعضها، ويُشعَر المندوب تلقائياً.
                            </p>
                        </div>
                    </x-ui.card>
                @endif
            @elseif(! $canReviewItems)
                <x-ui.empty-state
                    icon="clipboard-document-check"
                    title="لا توجد بنود معتمدة بعد"
                    description="ستظهر هنا البنود التي تم اعتمادها للطلب فقط."
                />
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Requester Info --}}
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">مقدم الطلب</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs text-[var(--color-text-muted)]">الاسم</dt>
                        <dd class="text-sm font-medium text-[var(--color-text-primary)]">{{ $aidRequest->requester_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[var(--color-text-muted)]">الصفة</dt>
                        <dd class="text-sm text-[var(--color-text-secondary)]">{{ $aidRequest->requester_relation ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[var(--color-text-muted)]">الجوال</dt>
                        <dd class="text-sm text-[var(--color-text-secondary)] font-mono">{{ $aidRequest->requester_phone ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            {{-- Attachments --}}
            @if($aidRequest->attachments->count())
                <x-ui.card padding>
                    <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">
                        المرفقات ({{ $aidRequest->attachments->count() }})
                    </h2>
                    <ul class="space-y-2">
                        @foreach($aidRequest->attachments as $attachment)
                            <li class="flex items-center gap-2 text-sm">
                                <x-heroicon-o-paper-clip class="w-4 h-4 text-[var(--color-text-muted)] shrink-0" />
                                <span class="text-[var(--color-text-secondary)] truncate">{{ $attachment->document_name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
