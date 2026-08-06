<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        :title="$release->version . ' — ' . $release->title"
        subtitle="تفاصيل الإصدار والتغييرات وعمليات النشر"
        :breadcrumbs="[
            ['label' => 'الإصدارات والنشر', 'route' => 'deployments.index'],
            ['label' => $release->version],
        ]"
    >
        <x-slot:actions>
            @if($release->isDraft())
                <x-ui.button variant="success" icon="check" wire:click="publish" wire:confirm="هل أنت متأكد من اعتماد هذا الإصدار؟">
                    اعتماد الإصدار
                </x-ui.button>
            @elseif($release->isPublished())
                <x-ui.button variant="primary" icon="rocket-launch" wire:click="openDeployModal">
                    نشر الآن
                </x-ui.button>
                <x-ui.button variant="danger" icon="arrow-uturn-left" wire:click="rollBack" wire:confirm="هل أنت متأكد من التراجع عن هذا الإصدار؟">
                    تراجع
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Release Info --}}
    <x-ui.card padding>
        <div class="flex items-center gap-3 flex-wrap mb-4">
            @php
                $statusConfig = [
                    'draft' => ['variant' => 'warning', 'label' => 'مسودة'],
                    'published' => ['variant' => 'success', 'label' => 'منشور'],
                    'rolled_back' => ['variant' => 'danger', 'label' => 'متراجع عنه'],
                ];
                $cfg = $statusConfig[$release->status->value] ?? ['variant' => 'neutral', 'label' => $release->status->value];
            @endphp
            <x-ui.badge :variant="$cfg['variant']" size="lg" dot>{{ $cfg['label'] }}</x-ui.badge>

            @if($release->released_at)
                <span class="text-sm text-[var(--color-text-muted)] flex items-center gap-1">
                    <x-heroicon-o-calendar class="w-4 h-4" />
                    تاريخ الاعتماد: {{ $release->released_at->format('Y-m-d H:i') }}
                </span>
            @endif

            <span class="text-sm text-[var(--color-text-muted)] flex items-center gap-1">
                <x-heroicon-o-user class="w-4 h-4" />
                {{ $release->creator?->name }}
            </span>

            @if($release->source_revision)
                <span class="text-sm text-[var(--color-text-muted)] font-mono flex items-center gap-1" dir="ltr">
                    <x-heroicon-o-code-bracket class="w-4 h-4" />
                    {{ substr($release->source_revision, 0, 8) }}
                </span>
            @endif
        </div>

        @if($release->description)
            <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">{{ $release->description }}</p>
        @endif
    </x-ui.card>

    {{-- Two-column: Changes + Deployments --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        {{-- Changes --}}
        <x-ui.card padding>
            <div class="flex items-center justify-between gap-3 mb-5 flex-wrap">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">التغييرات</h2>
                @if($release->changes->isNotEmpty())
                    <x-ui.button variant="outline" size="sm" icon="archive-box" wire:click="prepareUploadPackage" :loading="$preparingPackage">
                        حزمة الرفع ZIP
                    </x-ui.button>
                @endif
            </div>

            @if($release->changes->isEmpty())
                <x-ui.empty-state icon="document-text" title="لا توجد تغييرات" description="لم يتم توثيق أي تغييرات في هذا الإصدار." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-border)]">
                                <th class="text-start px-3 py-1.5 text-xs font-semibold text-[var(--color-text-muted)]">النوع</th>
                                <th class="text-start px-3 py-1.5 text-xs font-semibold text-[var(--color-text-muted)]">الملف</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach($release->changes as $change)
                                <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors" wire:key="change-{{ $change->id }}">
                                    <td class="px-3 py-1.5">
                                        @php
                                            $typeConfig = [
                                                'added' => ['variant' => 'success', 'icon' => 'plus-circle', 'label' => 'إضافة'],
                                                'modified' => ['variant' => 'info', 'icon' => 'pencil-square', 'label' => 'تعديل'],
                                                'fixed' => ['variant' => 'warning', 'icon' => 'check-circle', 'label' => 'إصلاح'],
                                                'updated' => ['variant' => 'info', 'icon' => 'arrow-path', 'label' => 'تحديث'],
                                                'removed' => ['variant' => 'danger', 'icon' => 'trash', 'label' => 'حذف'],
                                            ];
                                            $tc = $typeConfig[$change->type->value] ?? $typeConfig['modified'];
                                        @endphp
                                        <x-ui.badge :variant="$tc['variant']" size="sm">{{ $tc['label'] }}</x-ui.badge>
                                    </td>
                                    <td class="px-3 py-1.5">
                                        <code class="text-xs font-mono text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] px-2 py-1 rounded-[var(--radius-sm)]" dir="ltr">
                                            {{ $change->file_path }}
                                        </code>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($lastPackage)
                    <div class="mt-4 flex items-start gap-2 text-sm text-[var(--color-text-muted)] bg-[var(--color-bg-secondary)] rounded-[var(--radius-md)] p-3">
                        <x-heroicon-o-archive-box class="w-4 h-4 mt-0.5 shrink-0 text-[var(--accent-500)]" />
                        <div>
                            <p>آخر حزمة: <span class="font-mono" dir="ltr">{{ $lastPackage['filename'] }}</span> — {{ $lastPackage['count'] }} ملف.</p>
                            @if($lastPackage['removed'] !== [])
                                <p>{{ count($lastPackage['removed']) }} ملفات محذوفة مذكورة داخل الحزمة في <span class="font-mono" dir="ltr">REMOVED_FILES.txt</span>.</p>
                            @endif
                            @if($lastPackage['missing'] !== [])
                                <p class="text-[var(--color-danger-500)]">{{ count($lastPackage['missing']) }} ملف غير موجود في المشروع ولن يُضمّن في الحزمة.</p>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </x-ui.card>

        {{-- Deployments --}}
        <x-ui.card padding>
        <div @if($this->hasActiveDeployment) wire:poll.2.5s="refreshDeployments" @endif>
        <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">
            عمليات النشر
            @if($this->hasActiveDeployment)
                <span class="inline-flex items-center gap-1 text-xs font-normal text-[var(--color-info-500)] ms-2">
                    <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" />
                    جارٍ التحديث تلقائيًا
                </span>
            @endif
        </h2>

        @if($release->deployments->isEmpty())
            <x-ui.empty-state icon="rocket-launch" title="لم ينشر بعد" description="لم يتم تشغيل أي عملية نشر لهذا الإصدار." />
        @else
            <div class="space-y-3">
                @foreach($release->deployments as $deployment)
                    @php
                        $deployStatusConfig = [
                            'pending' => ['variant' => 'neutral', 'label' => 'قيد الانتظار'],
                            'in_progress' => ['variant' => 'info', 'label' => 'جارٍ التنفيذ'],
                            'completed' => ['variant' => 'success', 'label' => 'مكتمل'],
                            'failed' => ['variant' => 'danger', 'label' => 'فشل'],
                            'rolled_back' => ['variant' => 'warning', 'label' => 'تم التراجع'],
                        ];
                        $dc = $deployStatusConfig[$deployment->status->value] ?? $deployStatusConfig['pending'];
                    @endphp
                    <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-4" wire:key="deployment-{{ $deployment->id }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <x-ui.badge :variant="$dc['variant']" dot>{{ $dc['label'] }}</x-ui.badge>
                                    <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $deployment->environment->label() }}</span>
                                </div>
                                <div class="mt-2 flex items-center gap-4 text-xs text-[var(--color-text-muted)]">
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-user class="w-3.5 h-3.5" />
                                        {{ $deployment->creator?->name }}
                                    </span>
                                    @if($deployment->started_at)
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-play class="w-3.5 h-3.5" />
                                            {{ $deployment->started_at->format('Y-m-d H:i') }}
                                        </span>
                                    @endif
                                    @if($deployment->completed_at)
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-check class="w-3.5 h-3.5" />
                                            {{ $deployment->completed_at->format('Y-m-d H:i') }}
                                        </span>
                                    @endif
                                    @if($deployment->duration())
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                            {{ $deployment->duration() }} ثانية
                                        </span>
                                    @endif
                                </div>
                                @if($deployment->failure_reason)
                                    <p class="mt-2 text-sm text-[var(--color-danger-500)] bg-[var(--color-danger-50)] rounded-[var(--radius-sm)] p-2">
                                        {{ $deployment->failure_reason }}
                                    </p>
                                @endif

                                {{-- Progress + Steps --}}
                                @if($deployment->steps->isNotEmpty())
                                    @php $pct = $deployment->progressPercentage(); @endphp
                                    <div class="mt-4">
                                        @if($pct !== null)
                                            <div class="flex items-center justify-between text-xs mb-1.5">
                                                <span class="text-[var(--color-text-muted)]">
                                                    @php $current = $deployment->currentStep(); @endphp
                                                    @if($current)
                                                        الخطوة الحالية: <span class="text-[var(--color-text-primary)] font-medium">{{ $current->label }}</span>
                                                    @elseif($deployment->status->value === 'completed')
                                                        <span class="text-[var(--color-success-500)] font-medium">اكتمل النشر بنجاح</span>
                                                    @elseif($deployment->status->value === 'failed' || $deployment->status->value === 'rolled_back')
                                                        <span class="text-[var(--color-danger-500)] font-medium">توقف النشر</span>
                                                    @else
                                                        بانتظار بدء التنفيذ...
                                                    @endif
                                                </span>
                                                <span class="font-semibold text-[var(--color-text-primary)]" dir="ltr">{{ $pct }}%</span>
                                            </div>
                                            <div class="h-2 bg-[var(--color-bg-secondary)] rounded-full overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all duration-500 {{ $deployment->status->value === 'failed' || $deployment->status->value === 'rolled_back' ? 'bg-[var(--color-danger-500)]' : 'bg-[var(--accent-500)]' }}"
                                                    style="width: {{ $pct }}%"
                                                ></div>
                                            </div>
                                        @endif

                                        <div class="mt-4 space-y-1.5">
                                            @foreach($deployment->steps as $step)
                                                @php
                                                    $stepStatusConfig = [
                                                        'pending' => ['variant' => 'neutral', 'icon' => 'minus-circle', 'color' => 'text-[var(--color-text-muted)]'],
                                                        'in_progress' => ['variant' => 'info', 'icon' => 'arrow-path', 'color' => 'text-[var(--color-info-500)]'],
                                                        'completed' => ['variant' => 'success', 'icon' => 'check-circle', 'color' => 'text-[var(--color-success-500)]'],
                                                        'failed' => ['variant' => 'danger', 'icon' => 'x-circle', 'color' => 'text-[var(--color-danger-500)]'],
                                                        'skipped' => ['variant' => 'neutral', 'icon' => 'minus-circle', 'color' => 'text-[var(--color-text-muted)]'],
                                                    ];
                                                    $sc = $stepStatusConfig[$step->status->value] ?? $stepStatusConfig['pending'];
                                                @endphp
                                                <div class="flex items-center gap-3 text-sm" wire:key="step-{{ $step->id }}">
                                                    <span class="shrink-0 {{ $sc['color'] }}">
                                                        <x-dynamic-component :component="'heroicon-o-'.$sc['icon']" class="w-4 h-4 {{ $step->status->value === 'in_progress' ? 'animate-spin' : '' }}" />
                                                    </span>
                                                    <span class="text-[var(--color-text-secondary)]">{{ $step->label }}</span>
                                                    <span class="ms-auto shrink-0">
                                                        <x-ui.badge :variant="$sc['variant']" size="sm">{{ $step->status->label() }}</x-ui.badge>
                                                    </span>
                                                </div>
                                                @if($step->output && $step->status->value !== 'completed')
                                                    <pre class="text-xs font-mono text-[var(--color-text-muted)] bg-[var(--color-bg-secondary)] rounded-[var(--radius-sm)] p-2 overflow-x-auto max-h-32" dir="ltr">{{ $step->output }}</pre>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        </div>
    </x-ui.card>
    </div>
    {{-- End Two-column --}}

    {{-- Deploy Modal --}}
    <x-ui.modal name="deploy-modal" title="نشر الإصدار" subtitle="اختر البيئة التي تريد النشر إليها" size="sm">
        <div class="p-5 space-y-4">
            @unless($this->isFtpConfigured)
                <x-ui.alert variant="warning" :dismissible="false">
                    إعدادات FTP غير مكتملة — لن تُرفع الملفات للسيرفر.
                    <a href="{{ route('deployments.ftp-settings') }}" class="underline font-medium">أكمل الإعدادات من هنا</a>
                    قبل بدء النشر.
                </x-ui.alert>
            @endunless

            <div>
                <x-ui.select label="البيئة" name="deployEnvironment" wire:model="deployEnvironment">
                    <option value="">اختر البيئة...</option>
                    @foreach($environments as $env)
                        <option value="{{ $env->value }}">{{ $env->label() }}</option>
                    @endforeach
                </x-ui.select>
                @error('deployEnvironment')
                    <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <x-ui.button variant="secondary" x-on:click="open = false">
                    إلغاء
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    icon="rocket-launch"
                    wire:click="deploy"
                    :loading="$wire->submitting ?? false"
                >
                    بدء النشر
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
