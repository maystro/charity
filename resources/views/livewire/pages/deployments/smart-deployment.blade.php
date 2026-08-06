<div class="space-y-6">
    <x-layout.page-header
        title="النشر الذكي"
        subtitle="رفع الملفات المتغيرة فقط مع فحص محلي أو مقارنة مباشرة مع السيرفر"
    >
        <x-slot:actions>
            <div class="flex items-center gap-2 flex-wrap">
                <x-ui.button
                    variant="secondary"
                    icon="arrow-path"
                    wire:click="scanChanges"
                    :loading="$isScanning"
                    :disabled="$isDeploying"
                >
                    فحص محلي
                </x-ui.button>
                <x-ui.button
                    variant="secondary"
                    icon="cloud-arrow-down"
                    wire:click="scanServerChanges"
                    :loading="$isScanning"
                    :disabled="$isDeploying"
                >
                    مقارنة مع السيرفر
                </x-ui.button>
                @if(count($addedFiles) + count($modifiedFiles) + count($deletedFiles) > 0 && ! $isDeploying)
                    <x-ui.button
                        variant="primary"
                        icon="rocket-launch"
                        wire:click="startDeployment"
                        :loading="$isDeploying"
                    >
                        نشر التغييرات ({{ count($addedFiles) + count($modifiedFiles) }})
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Setup warning --}}
    @unless($isServerConfigured)
        <x-ui.alert variant="warning" :dismissible="false">
            لم يتم ضبط <code class="font-mono text-xs">DEPLOY_SERVER_URL</code> في ملف <code class="font-mono text-xs">.env</code> — لن تتمكن من النشر إلى السيرفر.
            أضف رابط <code class="font-mono text-xs">deployer.php</code> على السيرفر لتتمكن من النشر والمقارنة المباشرة.
        </x-ui.alert>
    @endunless

    @if($errorMessage)
        <x-ui.alert variant="danger" :dismissible="false">
            {{ $errorMessage }}
        </x-ui.alert>
    @endif

    @if($successMessage)
        <x-ui.alert variant="success" :dismissible="false">
            {{ $successMessage }}
        </x-ui.alert>
    @endif

    {{-- Status cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-ui.stat
            icon="check-badge"
            label="حالة المزامنة"
            :number="$stats['synced'] ? 'متزامن' : 'غير متزامن'"
            :variant="$stats['synced'] ? 'success' : 'warning'"
        />
        <x-ui.stat
            icon="document-text"
            label="إجمالي الملفات"
            :number="$stats['total_files']"
            variant="primary"
        />
        <x-ui.stat
            icon="archive-box"
            label="ملفات المانيفست"
            :number="$stats['manifest_files']"
            variant="neutral"
        />
        <x-ui.stat
            icon="rocket-launch"
            label="آخر نشر"
            :number="$stats['last_deployment'] ? $stats['last_deployment']->files_count . ' ملف' : '—'"
            :variant="$stats['last_deployment']?->isSuccessful() ? 'success' : 'neutral'"
        />
    </div>

    {{-- Progress --}}
    @if($isScanning)
        <x-ui.card padding>
            <div class="flex items-center gap-3 text-sm text-[var(--color-text-secondary)]">
                <x-heroicon-o-arrow-path class="w-5 h-5 animate-spin text-[var(--color-info-500)]" />
                جارٍ فحص الملفات، يرجى الانتظار...
            </div>
        </x-ui.card>
    @endif

    @if($isDeploying)
        <x-ui.card padding>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2 text-[var(--color-text-secondary)]">
                        <x-heroicon-o-rocket-launch class="w-4 h-4 text-[var(--accent-500)]" />
                        جارٍ نشر الملفات إلى السيرفر...
                    </span>
                    <span class="font-semibold text-[var(--color-text-primary)]" dir="ltr">
                        {{ $uploadProgress }}%
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs text-[var(--color-text-muted)]">
                    <span>تم رفع {{ $uploadedCount }} من {{ $totalFilesToUpload }} ملف</span>
                    <span>الملف الحالي: {{ $currentFile ?: '—' }}</span>
                </div>
                <div class="h-2 bg-[var(--color-bg-secondary)] rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300 bg-[var(--accent-500)]"
                        style="width: {{ $uploadProgress }}%"
                    ></div>
                </div>
                <p class="text-xs text-[var(--color-text-muted)]">
                    سيتم تحديث الحالة تلقائيًا عند انتهاء العملية. يتم إنشاء أرشيف ZIP وإرساله إلى <code class="font-mono">deployer.php</code> على السيرفر.
                </p>
            </div>
        </x-ui.card>
    @endif

    {{-- Files preview + Deployment history side by side --}}
    @php $totalPending = count($addedFiles) + count($modifiedFiles) + count($deletedFiles); @endphp
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        @if($totalPending > 0 && ! $isDeploying)
            <x-ui.card padding>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">
                        الملفات التي سيتم نشرها
                        <span class="text-sm font-normal text-[var(--color-text-muted)] ms-2">
                            ({{ $totalPending }} ملف — الحجم الكلي: <span dir="ltr">{{ number_format($totalSize) }}</span> بايت)
                        </span>
                    </h2>
                    <x-ui.button variant="ghost" size="sm" icon="trash" wire:click="resetManifest">
                        إعادة ضبط المانيفست
                    </x-ui.button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-[var(--color-text-muted)] border-b border-[var(--color-border)]">
                                <th class="text-start font-medium py-2 pe-4">حالة الملف</th>
                                <th class="text-start font-medium py-2">اسم الملف</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach($this->sortedFiles as $file)
                                @php
                                    $badge = match($file['status']) {
                                        'deleted' => ['variant' => 'danger', 'label' => 'حذف'],
                                        'modified' => ['variant' => 'warning', 'label' => 'تحديث'],
                                        'added' => ['variant' => 'info', 'label' => 'إضافة'],
                                    };
                                @endphp
                                <tr class="hover:bg-[var(--color-bg-secondary)]/50">
                                    <td class="py-2.5 pe-4">
                                        <x-ui.badge :variant="$badge['variant']" dot size="sm">
                                            {{ $badge['label'] }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="py-2.5 font-mono text-xs text-[var(--color-text-secondary)]" dir="ltr">{{ $file['path'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif

        {{-- Recent deployments --}}
        <x-ui.card padding :class="$totalPending > 0 && ! $isDeploying ? '' : 'lg:col-span-2'">
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">سجل النشر الذكي</h2>

            @if($recentDeployments->isEmpty())
                <x-ui.empty-state icon="clock" title="لا توجد عمليات نشر بعد" description="ابدأ بفحص الملفات ثم انشر التغييرات." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-[var(--color-text-muted)] border-b border-[var(--color-border)]">
                                <th class="text-start font-medium py-2 pe-4">#</th>
                                <th class="text-start font-medium py-2 pe-4">الحالة</th>
                                <th class="text-start font-medium py-2 pe-4">عدد الملفات</th>
                                <th class="text-start font-medium py-2">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach($recentDeployments as $deployment)
                                <tr class="hover:bg-[var(--color-bg-secondary)]/50">
                                    <td class="py-2.5 pe-4 text-[var(--color-text-muted)] font-mono">#{{ $deployment->id }}</td>
                                    <td class="py-2.5 pe-4">
                                        <x-ui.badge :variant="$deployment->isSuccessful() ? 'success' : ($deployment->status === 'failed' ? 'danger' : 'warning')" dot size="sm">
                                            {{ $deployment->isSuccessful() ? 'ناجح' : ($deployment->status === 'failed' ? 'فشل' : 'قيد التنفيذ') }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="py-2.5 pe-4 font-mono text-[var(--color-text-secondary)]">{{ $deployment->files_count }}</td>
                                    <td class="py-2.5 font-mono text-xs text-[var(--color-text-muted)]">
                                        {{ $deployment->created_at->format('Y-m-d H:i') }}
                                        @if($deployment->duration() !== null)
                                            <span class="block text-[var(--color-text-muted)]/70">({{ $deployment->duration() }} ث)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>
</div>
