<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        title="التنبيهات"
        subtitle="تنبيهات النظام الدورية — إعادة التقييم وغيرها"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="arrow-path" href="{{ route('families.re-assessment-index') }}" wire:navigate>
                إعادة التقييم
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Filter Tabs --}}
    <x-ui.card padding>
        <div class="flex items-center gap-2 flex-wrap">
            @php
                $tabs = [
                    'active'    => ['label' => 'نشطة', 'count' => $counts['active'], 'color' => 'var(--color-warning-500)'],
                    'overdue'   => ['label' => 'متأخرة', 'count' => $counts['overdue'], 'color' => 'var(--color-danger-500)'],
                    'dismissed' => ['label' => 'تم تجاهلها', 'count' => $counts['dismissed'], 'color' => 'var(--color-text-muted)'],
                    'resolved'  => ['label' => 'تم حلها', 'count' => $counts['resolved'], 'color' => 'var(--color-success-500)'],
                ];
            @endphp
            @foreach($tabs as $key => $tab)
                <button
                    wire:click="$set('filter', '{{ $key }}')"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-colors {{ $filter === $key ? 'text-white' : 'hover:bg-black/5 text-[var(--color-text-secondary)]' }}"
                    style="{{ $filter === $key ? 'background: ' . $tab['color'] . ';' : '' }}"
                >
                    <span>{{ $tab['label'] }}</span>
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold {{ $filter === $key ? 'bg-white/25 text-white' : 'bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)]' }}">
                        {{ $tab['count'] }}
                    </span>
                </button>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Alerts List --}}
    <x-ui.card padding>
        @if($alerts->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background: var(--color-bg-secondary);">
                    <x-heroicon-o-bell-slash class="w-8 h-8 text-[var(--color-text-muted)]" />
                </div>
                <p class="text-sm font-medium text-[var(--color-text-secondary)]">لا توجد تنبيهات في هذا التصنيف</p>
            </div>
        @else
            <div class="divide-y divide-[var(--color-border)]">
                @foreach($alerts as $alert)
                    @php
                        $severityConfig = [
                            'critical' => ['bg' => 'var(--color-danger-50)', 'border' => 'var(--color-danger-500)', 'icon' => 'exclamation-circle', 'iconColor' => 'var(--color-danger-500)'],
                            'warning'  => ['bg' => 'var(--color-warning-50)', 'border' => 'var(--color-warning-500)', 'icon' => 'exclamation-triangle', 'iconColor' => 'var(--color-warning-500)'],
                            'info'     => ['bg' => 'var(--color-info-50)', 'border' => 'var(--color-info-500)', 'icon' => 'information-circle', 'iconColor' => 'var(--color-info-500)'],
                        ];
                        $cfg = $severityConfig[$alert->severity] ?? $severityConfig['info'];
                        $alertable = $alert->alertable;
                        $isFamily = $alertable instanceof \App\Models\Family;
                    @endphp
                    <div class="flex items-start gap-4 p-4 hover:bg-[var(--color-bg-secondary)]/30 transition-colors" wire:key="alert-{{ $alert->id }}">
                        {{-- Severity Icon --}}
                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background: {{ $cfg['bg'] }};">
                            <x-dynamic-component :component="'heroicon-o-' . $cfg['icon']" class="w-5 h-5" style="color: {{ $cfg['iconColor'] }};" />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-semibold text-[var(--color-text-primary)]">{{ $alert->title }}</h3>
                                @if($alert->isOverdue() && $alert->isActive())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white" style="background: var(--color-danger-500);">
                                        متأخر
                                    </span>
                                @endif
                                @if($alert->status === \App\Models\Alert::STATUS_DISMISSED)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)]">تم التجاهل</span>
                                @elseif($alert->status === \App\Models\Alert::STATUS_RESOLVED)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold" style="background: var(--color-success-50); color: var(--color-success-600);">تم الحل</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-[var(--color-text-secondary)] leading-relaxed">{{ $alert->message }}</p>
                            <div class="mt-2 flex items-center gap-4 text-xs text-[var(--color-text-muted)]">
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                    {{ $alert->created_at->format('Y-m-d H:i') }}
                                </span>
                                @if($alert->due_at)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                        موعد الاستحقاق: {{ $alert->due_at->format('Y-m-d') }}
                                    </span>
                                @endif
                                @if($isFamily && $alertable)
                                    <a href="{{ route('families.show', $alertable) }}" wire:navigate class="flex items-center gap-1 text-[var(--accent-500)] hover:text-[var(--accent-600)] transition-colors font-medium">
                                        <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
                                        عرض الأسرة
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        @if($alert->isActive())
                            <div class="shrink-0 flex items-center gap-1">
                                @if($isFamily && $alertable)
                                    <a href="{{ route('families.re-assessment-index') }}" wire:navigate class="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:text-[var(--color-success-500)] hover:bg-[var(--color-success-50)] transition-colors" title="بدء إعادة تقييم">
                                        <x-heroicon-o-arrow-path class="w-4.5 h-4.5" />
                                    </a>
                                @endif
                                <button wire:click="resolveAlert({{ $alert->id }})" wire:confirm="تحديد هذا التنبيه كمحلول؟" class="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:text-[var(--color-success-500)] hover:bg-[var(--color-success-50)] transition-colors" title="تحديد كمحلول">
                                    <x-heroicon-o-check class="w-4.5 h-4.5" />
                                </button>
                                <button wire:click="dismissAlert({{ $alert->id }})" wire:confirm="تجاهل هذا التنبيه؟" class="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors" title="تجاهل">
                                    <x-heroicon-o-x-mark class="w-4.5 h-4.5" />
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $alerts->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
