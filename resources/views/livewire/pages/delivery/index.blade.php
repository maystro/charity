<?php

use App\Livewire\Delivery\DeliveryIndex;
use App\Models\AidRequest;
use function Livewire\Volt\{state};

?>

<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        title="التنفيذ والمتابعة"
        subtitle="إدارة وتسليم المساعدات للأسر المستفيدة"
    />

    {{-- Tabs --}}
    <div class="border-b border-[var(--color-border)]">
        <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
            {{-- بانتظار التنفيذ --}}
            <button
                wire:click="$set('tab', 'ready')"
                @class([
                    'text-sm px-4 py-2.5 border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap',
                    'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' => $tab === 'ready',
                    'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]' => $tab !== 'ready',
                ])
                role="tab"
            >
                بانتظار التنفيذ
                @if ($this->readyCount > 0)
                    <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full bg-[var(--accent-500)]/10 text-[var(--accent-600)]">
                        {{ $this->readyCount }}
                    </span>
                @endif
            </button>

            {{-- قيد التنفيذ (المندوب يشتري) --}}
            <button
                wire:click="$set('tab', 'in_execution')"
                @class([
                    'text-sm px-4 py-2.5 border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap',
                    'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' => $tab === 'in_execution',
                    'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]' => $tab !== 'in_execution',
                ])
                role="tab"
            >
                قيد التنفيذ
                @if ($this->inExecutionCount > 0)
                    <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full bg-[var(--color-warning-50)] text-[var(--color-warning-500)]">
                        {{ $this->inExecutionCount }}
                    </span>
                @endif
            </button>

            {{-- بانتظار مراجعة التسليم --}}
            <button
                wire:click="$set('tab', 'pending_review')"
                @class([
                    'text-sm px-4 py-2.5 border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap',
                    'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' => $tab === 'pending_review',
                    'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]' => $tab !== 'pending_review',
                ])
                role="tab"
            >
                بانتظار المراجعة
                @if ($this->pendingReviewCount > 0)
                    <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full bg-[var(--color-warning-50)] text-[var(--color-warning-500)]">
                        {{ $this->pendingReviewCount }}
                    </span>
                @endif
            </button>

            {{-- تم التسليم --}}
            <button
                wire:click="$set('tab', 'delivered')"
                @class([
                    'text-sm px-4 py-2.5 border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap',
                    'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' => $tab === 'delivered',
                    'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]' => $tab !== 'delivered',
                ])
                role="tab"
            >
                تم التسليم
                @if ($this->deliveredCount > 0)
                    <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full bg-[var(--color-success-50)] text-[var(--color-success-500)]">
                        {{ $this->deliveredCount }}
                    </span>
                @endif
            </button>

            {{-- متأخر --}}
            <button
                wire:click="$set('tab', 'overdue')"
                @class([
                    'text-sm px-4 py-2.5 border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap',
                    'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' => $tab === 'overdue',
                    'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]' => $tab !== 'overdue',
                ])
                role="tab"
            >
                متأخر
                @if ($this->overdueCount > 0)
                    <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full bg-[var(--color-danger-50)] text-[var(--color-danger-500)]">
                        {{ $this->overdueCount }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    {{-- Filters --}}
    <x-ui.card>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث برقم الطلب أو اسم الأسرة..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="sort" wire:model.live="sort">
                <option value="newest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
                <option value="priority">حسب الأولوية</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    {{-- Requests List --}}
    <div class="space-y-4">
        @forelse ($requests as $request)
            <x-ui.card wire:key="request-{{ $request->id }}">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-medium text-[var(--color-text-muted)]">
                                #{{ $request->request_number }}
                            </span>
                            <x-ui.badge :variant="$this->statusVariant($request->status)">
                                {{ $this->statusLabel($request->status) }}
                            </x-ui.badge>
                            @if ($request->priority)
                                <x-ui.badge :variant="$this->priorityVariant($request->priority)">
                                    {{ $request->priority }}
                                </x-ui.badge>
                            @endif
                        </div>

                        <h3 class="text-base font-semibold text-[var(--color-text-primary)] truncate">
                            {{ $request->title }}
                        </h3>

                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-[var(--color-text-secondary)]">
                            @if ($request->family)
                                <span class="flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ $request->family->case_name }}
                                </span>
                            @endif
                            <span class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                {{ $request->items->count() }} بند
                            </span>
                            @if ($request->needed_by)
                                <span class="flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $request->needed_by->format('Y/m/d') }}
                                </span>
                            @endif
                        </div>

                        {{-- Items Summary --}}
                        @if ($request->items->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($request->items as $item)
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-[var(--radius-sm)] border px-2 py-0.5 text-xs',
                                        'border-[var(--color-success-500)]/30 bg-[var(--color-success-50)] text-[var(--color-success-500)]' => $item->delivered,
                                        'border-[var(--color-border)] bg-[var(--color-bg-secondary)] text-[var(--color-text-secondary)]' => !$item->delivered,
                                    ])>
                                        @if ($item->delivered)
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @endif
                                        {{ $item->title }}
                                        @if ($item->quantity)
                                            <span class="opacity-60">({{ $item->quantity }})</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        {{-- ready: بدء التنفيذ --}}
                        @if ($tab === 'ready')
                            <x-ui.button
                                variant="primary"
                                icon="play"
                                wire:click="startExecution({{ $request->id }})"
                                wire:confirm="هل أنت متأكد من بدء تنفيذ هذا الطلب؟"
                            >
                                بدء التنفيذ
                            </x-ui.button>
                        @endif

                        {{-- in_execution / overdue: تنفيذ --}}
                        @if ($tab === 'in_execution' || $tab === 'overdue')
                            <x-ui.button
                                variant="primary"
                                icon="play"
                                wire:click="openSubmitReviewPanel({{ $request->id }})"
                            >
                                تنفيذ
                            </x-ui.button>
                        @endif

                        {{-- pending_review: مراجعة التسليم --}}
                        @if ($tab === 'pending_review')
                            <x-ui.button
                                variant="primary"
                                icon="clipboard-check"
                                wire:click="openReviewPanel({{ $request->id }})"
                            >
                                مراجعة التسليم
                            </x-ui.button>
                        @endif

                        {{-- delivered: عرض --}}
                        @if ($tab === 'delivered')
                            <x-ui.button
                                variant="secondary"
                                icon="eye"
                                :href="route('aid-requests.show', $request->id)"
                                wire:navigate
                            >
                                عرض
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty-state
                icon="document-text"
                title="لا توجد طلبات"
                description="{{ match($tab) {
                    'ready' => 'لا توجد طلبات معتمدة بانتظار التنفيذ.',
                    'in_execution' => 'لا توجد طلبات قيد التنفيذ حالياً.',
                    'pending_review' => 'لا توجد طلبات بانتظار مراجعة التسليم.',
                    'delivered' => 'لا توجد طلبات تم تسليمها بعد.',
                    'overdue' => 'لا توجد طلبات متأخرة.',
                    default => '',
                } }}"
            />
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($requests->hasPages())
        <div class="mt-6">
            {{ $requests->links() }}
        </div>
    @endif

    {{-- ========== Modal: رفع مستندات التسليم (للمندوب) ========== --}}
    @if ($activeRequest && in_array($tab, ['in_execution', 'overdue']))
        <div
            x-data
            x-cloak
            x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[var(--z-modal)]"
            wire:key="submit-review-panel-{{ $activeRequest->id }}"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-black/40 backdrop-blur-sm"
                wire:click="closePanel"
            ></div>

            {{-- Panel --}}
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="w-full max-w-2xl bg-white rounded-[var(--radius-xl)] shadow-2xl max-h-[90vh] overflow-y-auto"
                    >
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-4 p-5 border-b border-[var(--color-border)]">
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">
                                    تنفيذ الطلب #{{ $activeRequest->request_number }}
                                </h3>
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                                    {{ $activeRequest->title }}
                                    @if ($activeRequest->family)
                                        — {{ $activeRequest->family->case_name }}
                                    @endif
                                </p>
                            </div>
                            <button
                                wire:click="closePanel"
                                class="p-1 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="p-5 space-y-4">
                            <div class="rounded-[var(--radius-md)] border border-[var(--color-warning-500)]/30 bg-[var(--color-warning-50)] p-3">
                                <p class="text-sm text-[var(--color-warning-500)]">
                                    الرجاء إدخال التكلفة الحقيقية لكل بند تم شراؤه وإرفاق مستندات الشراء (فواتير، روشتات، إلخ).
                                </p>
                            </div>

                            <h3 class="text-sm font-medium text-[var(--color-text-primary)]">البنود المعتمدة للشراء</h3>

                            @foreach ($activeRequest->items->where('approved', true) as $item)
                                <div
                                    wire:key="submit-item-{{ $item->id }}"
                                    class="rounded-[var(--radius-md)] border border-[var(--color-border)] bg-[var(--color-bg-secondary)] p-4 space-y-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-[var(--color-text-primary)]">
                                                {{ $item->title }}
                                            </p>
                                            <p class="text-xs text-[var(--color-text-muted)]">
                                                التكلفة التقديرية: {{ number_format($item->estimated_total, 2) }}
                                                @if ($item->quantity)
                                                    | الكمية: {{ $item->quantity }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-[var(--color-text-secondary)] mb-1">
                                                التكلفة الحقيقية *
                                            </label>
                                            <x-ui.input
                                                name="itemsCostData.{{ $item->id }}.actual_cost"
                                                type="number"
                                                step="0.01"
                                                wire:model="itemsCostData.{{ $item->id }}.actual_cost"
                                                placeholder="أدخل التكلفة الحقيقية"
                                            />
                                            @error("itemsCostData.{$item->id}.actual_cost")
                                                <p class="mt-1 text-xs text-[var(--color-danger-500)]">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-[var(--color-text-secondary)] mb-1">
                                                تاريخ الشراء
                                            </label>
                                            <x-ui.date-input
                                                name="itemsCostData.{{ $item->id }}.purchase_date"
                                                wire:model="itemsCostData.{{ $item->id }}.purchase_date"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-[var(--color-text-secondary)] mb-1">
                                            ملاحظات الشراء
                                        </label>
                                        <x-ui.textarea
                                            name="itemsCostData.{{ $item->id }}.purchase_notes"
                                            wire:model="itemsCostData.{{ $item->id }}.purchase_notes"
                                            placeholder="مثل: اسم المحل، رقم الفاتورة..."
                                            rows="2"
                                        />
                                    </div>

                                    {{-- مرفقات البند (فواتير، مستندات شراء) --}}
                                    <div>
                                        <label class="block text-xs font-medium text-[var(--color-text-secondary)] mb-1">
                                            إرفاق مستندات الشراء (فواتير، روشتات)
                                        </label>
                                        <input
                                            type="file"
                                            wire:model="itemAttachments.{{ $item->id }}"
                                            accept="image/*, .pdf, .doc, .docx"
                                            multiple
                                            class="block w-full text-sm text-[var(--color-text-secondary)]
                                                file:me-3 file:py-1.5 file:px-3
                                                file:rounded-[var(--radius-md)] file:border-0
                                                file:text-sm file:font-medium
                                                file:bg-[var(--accent-500)]/10 file:text-[var(--accent-600)]
                                                hover:file:bg-[var(--accent-500)]/20
                                                file:transition-colors file:cursor-pointer
                                                cursor-pointer"
                                        />
                                        @error("itemAttachments.{$item->id}.*")
                                            <p class="mt-1 text-xs text-[var(--color-danger-500)]">{{ $message }}</p>
                                        @enderror

                                        {{-- معاينة الملفات المرفوعة --}}
                                        @if (!empty($itemAttachments[$item->id]))
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($itemAttachments[$item->id] as $index => $attachedFile)
                                                    <div class="inline-flex items-center gap-1 rounded-[var(--radius-sm)] border border-[var(--color-border)] bg-white px-2 py-1 text-xs">
                                                        <svg class="h-3.5 w-3.5 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        <span class="text-[var(--color-text-secondary)] max-w-[120px] truncate">
                                                            {{ $attachedFile->getClientOriginalName() }}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            wire:click="removeItemAttachment({{ $item->id }}, {{ $index }})"
                                                            class="text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] transition-colors"
                                                        >
                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            {{-- General Notes --}}
                            <x-ui.textarea
                                label="ملاحظات عامة (اختياري)"
                                wire:model="deliveryNotes"
                                placeholder="أي ملاحظات إضافية حول عملية الشراء والتسليم..."
                            />
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between gap-4 p-5 border-t border-[var(--color-border)]">
                            <span class="text-sm text-[var(--color-text-muted)]">
                                {{ $activeRequest->items->where('approved', true)->count() }} بند معتمد
                            </span>
                            <div class="flex gap-2">
                                <x-ui.button
                                    variant="secondary"
                                    wire:click="closePanel"
                                >
                                    إلغاء
                                </x-ui.button>
                                <x-ui.button
                                    variant="primary"
                                    icon="check-circle"
                                    wire:click="submitForReview"
                                    wire:loading.attr="disabled"
                                >
                                    <svg wire:loading class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    <span wire:loading.remove>تنفيذ</span>
                                    <span wire:loading>جاري التنفيذ...</span>
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ========== Modal: مراجعة التسليم (للإدارة) ========== --}}
    @if ($activeRequest && $tab === 'pending_review')
        <div
            x-data="{ showReject: false }"
            x-cloak
            x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[var(--z-modal)]"
            wire:key="review-panel-{{ $activeRequest->id }}"
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-black/40 backdrop-blur-sm"
                wire:click="closePanel"
            ></div>

            {{-- Panel --}}
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="w-full max-w-2xl bg-white rounded-[var(--radius-xl)] shadow-2xl max-h-[90vh] overflow-y-auto"
                    >
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-4 p-5 border-b border-[var(--color-border)]">
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">
                                    مراجعة التسليم #{{ $activeRequest->request_number }}
                                </h3>
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                                    {{ $activeRequest->title }}
                                    @if ($activeRequest->family)
                                        — {{ $activeRequest->family->case_name }}
                                    @endif
                                </p>
                            </div>
                            <button
                                wire:click="closePanel"
                                class="p-1 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="p-5 space-y-4">
                            <h3 class="text-sm font-medium text-[var(--color-text-primary)]">تفاصيل المشتريات</h3>

                            @foreach ($activeRequest->items->where('approved', true) as $item)
                                <div
                                    wire:key="review-item-{{ $item->id }}"
                                    class="rounded-[var(--radius-md)] border border-[var(--color-border)] bg-[var(--color-bg-secondary)] p-4"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-[var(--color-text-primary)]">
                                                {{ $item->title }}
                                            </p>
                                            @if ($item->description)
                                                <p class="text-xs text-[var(--color-text-muted)] mt-0.5">
                                                    {{ $item->description }}
                                                </p>
                                            @endif
                                        </div>
                                        @if ($item->quantity)
                                            <span class="text-xs text-[var(--color-text-muted)] shrink-0">
                                                الكمية: {{ $item->quantity }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                                        <div>
                                            <span class="text-xs text-[var(--color-text-muted)]">التكلفة التقديرية</span>
                                            <p class="font-medium text-[var(--color-text-primary)]">
                                                {{ number_format($item->estimated_total, 2) }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-[var(--color-text-muted)]">التكلفة الحقيقية</span>
                                            <p class="font-medium text-[var(--color-text-primary)]">
                                                {{ $item->actual_cost ? number_format($item->actual_cost, 2) : '—' }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-[var(--color-text-muted)]">تاريخ الشراء</span>
                                            <p class="font-medium text-[var(--color-text-primary)]">
                                                {{ $item->purchase_date ? \Carbon\Carbon::parse($item->purchase_date)->format('Y/m/d') : '—' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if ($item->purchase_notes)
                                        <div class="mt-2 text-xs text-[var(--color-text-secondary)]">
                                            <span class="font-medium">ملاحظات:</span> {{ $item->purchase_notes }}
                                        </div>
                                    @endif

                                    {{-- عرض المرفقات المرفوعة من المندوب --}}
                                    @php
                                        $itemAttachments = $activeRequest->attachments
                                            ->where('aid_request_item_id', $item->id);
                                    @endphp
                                    @if ($itemAttachments->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($itemAttachments as $attachment)
                                                <a
                                                    href="{{ route('attachments.download', $attachment) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 rounded-[var(--radius-sm)] border border-[var(--accent-500)]/30 bg-[var(--accent-500)]/5 px-2 py-1 text-xs text-[var(--accent-600)] hover:bg-[var(--accent-500)]/10 transition-colors"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    <span class="max-w-[120px] truncate">{{ $attachment->original_name }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Review Notes --}}
                            <x-ui.textarea
                                label="ملاحظات المراجعة (اختياري)"
                                wire:model="deliveryNotes"
                                placeholder="أي ملاحظات حول عملية المراجعة..."
                            />

                            {{-- Rejection Reason (hidden by default) --}}
                            <div x-show="showReject" x-transition class="space-y-2">
                                <label class="block text-sm font-medium text-[var(--color-text-primary)]">
                                    سبب الرفض *
                                </label>
                                <x-ui.textarea
                                    name="rejectionReason"
                                    wire:model="rejectionReason"
                                    placeholder="اذكر سبب رفض التسليم ليتمكن المندوب من معالجته..."
                                    rows="3"
                                />
                                @error('rejectionReason')
                                    <p class="text-sm text-[var(--color-danger-500)]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between gap-4 p-5 border-t border-[var(--color-border)]">
                            <span class="text-sm text-[var(--color-text-muted)]">
                                {{ $activeRequest->items->where('approved', true)->count() }} بند
                            </span>
                            <div class="flex gap-2">
                                <x-ui.button
                                    variant="secondary"
                                    wire:click="closePanel"
                                >
                                    إلغاء
                                </x-ui.button>

                                {{-- Reject button --}}
                                <template x-if="!showReject">
                                    <x-ui.button
                                        variant="danger"
                                        icon="x-circle"
                                        @click="showReject = true"
                                    >
                                        رفض التسليم
                                    </x-ui.button>
                                </template>

                                {{-- Confirm reject --}}
                                <template x-if="showReject">
                                    <x-ui.button
                                        variant="danger"
                                        icon="x-circle"
                                        wire:click="rejectDelivery"
                                        wire:loading.attr="disabled"
                                    >
                                        <svg wire:loading class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        <span wire:loading.remove>تأكيد الرفض</span>
                                        <span wire:loading>جاري...</span>
                                    </x-ui.button>
                                </template>

                                {{-- Confirm delivery --}}
                                <x-ui.button
                                    variant="primary"
                                    icon="check-circle"
                                    wire:click="confirmDelivery"
                                    wire:confirm="هل أنت متأكد من تأكيد التسليم؟ سيتم تحويل الطلب إلى 'تم التسليم'."
                                    wire:loading.attr="disabled"
                                >
                                    <svg wire:loading class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    <span wire:loading.remove>تأكيد التسليم</span>
                                    <span wire:loading>جاري...</span>
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
</div>
