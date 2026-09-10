<div class="space-y-6">
    <x-layout.page-header
        title="النشر الذكي"
        subtitle="رفع الملفات المتغيرة فقط مع فحص محلي أو مقارنة مباشرة مع السيرفر"
    >
        <x-slot:actions>
            <div class="flex items-center gap-2 flex-wrap" x-data="deployConnectModal">
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
                    @click="start('compare', $wire)"
                    :loading="$isScanning"
                    :disabled="$isDeploying"
                >
                    مقارنة مع السيرفر
                </x-ui.button>
                <x-ui.button
                    variant="secondary"
                    icon="shield-check"
                    @click="$dispatch('open-modal', 'deploy-paths')"
                >
                    مسارات النشر
                </x-ui.button>
                @if(count($addedFiles) + count($modifiedFiles) + count($deletedFiles) > 0 && ! $isDeploying)
                    <x-ui.button
                        variant="primary"
                        icon="rocket-launch"
                        @click="start('deploy', $wire)"
                        :loading="$isDeploying"
                    >
                        نشر التغييرات ({{ count($addedFiles) + count($modifiedFiles) }})
                    </x-ui.button>
                @endif

                {{-- Connection status modal --}}
                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[var(--z-modal)]"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="deploy-connect-title"
                >
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>

                    <div class="fixed inset-0 overflow-y-auto">
                        <div class="flex min-h-full items-center justify-center p-4">
                            <div class="w-full max-w-md bg-white rounded-[var(--radius-xl)] shadow-2xl">
                                <div class="p-6 text-center">
                                    {{-- Connecting --}}
                                    <template x-if="phase === 'connecting'">
                                        <div>
                                            <div class="w-14 h-14 mx-auto rounded-full bg-[var(--color-primary-50)] flex items-center justify-center">
                                                <x-heroicon-o-cloud-arrow-up class="w-7 h-7 text-[var(--accent-500)] animate-bounce" />
                                            </div>
                                            <h3 id="deploy-connect-title" class="mt-4 text-lg font-semibold text-[var(--color-text-primary)]">
                                                جاري الاتصال بالخادم...
                                            </h3>
                                            <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                                                <span x-text="actionLabel"></span> — يرجى الانتظار حتى يستجيب الخادم.
                                            </p>
                                            <div class="mt-4 flex items-center justify-center gap-2 text-sm text-[var(--color-text-secondary)]">
                                                <span class="w-4 h-4 border-2 border-[var(--accent-500)] border-t-transparent rounded-full animate-spin"></span>
                                                <span>مرّت <span class="font-semibold" x-text="elapsed"></span> ثانية</span>
                                            </div>
                                            <ul class="mt-4 space-y-1.5 text-xs text-[var(--color-text-muted)] text-start">
                                                <li>إنشاء الأرشيف وإرساله إلى <code dir="ltr" class="font-mono">deployer.php</code>.</li>
                                                <li>فك الضغط على السيرفر وتحديث الملفات.</li>
                                                <li>يتم تحديث الحالة تلقائيًا عند انتهاء العملية.</li>
                                            </ul>
                                        </div>
                                    </template>

                                    {{-- Long wait warning --}}
                                    <template x-if="phase === 'waiting'">
                                        <div>
                                            <div class="w-14 h-14 mx-auto rounded-full bg-amber-50 flex items-center justify-center">
                                                <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-amber-500" />
                                            </div>
                                            <h3 id="deploy-connect-title" class="mt-4 text-lg font-semibold text-[var(--color-text-primary)]">
                                                الاتصال يستغرق وقتًا أطول من المعتاد
                                            </h3>
                                            <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                                                قد يكون الخادم بطيئًا أو لا يستجيب حاليًا. يمكنك متابعة الانتظار أو إغلاق النافذة — ستظهر النتيجة عند اكتمال العملية.
                                            </p>
                                            <div class="mt-5 flex justify-center gap-2">
                                                <x-ui.button variant="primary" size="sm" @click="keepWaiting()">متابعة الانتظار</x-ui.button>
                                                <x-ui.button variant="ghost" size="sm" @click="close()">إغلاق النافذة</x-ui.button>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Failed / timeout --}}
                                    <template x-if="phase === 'error'">
                                        <div>
                                            <div class="w-14 h-14 mx-auto rounded-full bg-[var(--color-danger-50)] flex items-center justify-center">
                                                <x-heroicon-o-x-circle class="w-7 h-7 text-[var(--color-danger-500)]" />
                                            </div>
                                            <h3 id="deploy-connect-title" class="mt-4 text-lg font-semibold text-[var(--color-text-primary)]">
                                                تعذر الاتصال بالخادم
                                            </h3>
                                            <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                                                انتهت مهلة الاتصال قبل اكتمال العملية. تأكد من أن
                                                <code dir="ltr" class="font-mono">deployer.php</code>
                                                يعمل على السيرفر ثم أعد المحاولة.
                                            </p>
                                            <div class="mt-5 flex justify-center">
                                                <x-ui.button variant="primary" size="sm" @click="close()">إغلاق</x-ui.button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Deployment paths modal --}}
                <x-ui.modal name="deploy-paths" title="مسارات النشر" size="lg">
                    <div class="space-y-4">
                        <x-ui.alert variant="info" :dismissible="false">
                            القائمة تعرض الفولدرات والملفات الموجودة على الجذر مباشرة. تحديد أي مجلد يعني السماح بكل ما بداخله تلقائيًا دون عرضه. إذا لم تحفظ أي مسار، يعمل النظام بمساراته الافتراضية من
                            <code dir="ltr">config/deployment.php</code>.
                        </x-ui.alert>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-sm font-semibold text-[var(--color-text-primary)]">
                                قائمة الجذر
                                <span class="ms-2 text-xs font-normal text-[var(--color-text-muted)]">
                                    ({{ $this->allowedSelectedCount }} مُحدد — {{ count($this->allowedEntries) + count($this->allowedDisabledEntries) }} عنصر)
                                </span>
                            </h2>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.input
                                    type="search"
                                    wire:model.live.debounce.200ms="allowedSearch"
                                    placeholder="ابحث عن مسار…"
                                    icon="magnifying-glass"
                                    size="sm"
                                    class="w-52"
                                />
                                <x-ui.button variant="secondary" size="sm" wire:click="selectAllPaths">
                                    تحديد الكل
                                </x-ui.button>
                                <x-ui.button variant="ghost" size="sm" wire:click="clearAllPaths">
                                    إلغاء الكل
                                </x-ui.button>
                            </div>
                        </div>

                        @php
                            $visible = $this->allowedEntries;
                            $visibleDisabled = $this->allowedDisabledEntries;

                            if ($allowedSearch !== '') {
                                $needle = mb_strtolower($allowedSearch);
                                $visible = array_values(array_filter(
                                    $this->allowedEntries,
                                    fn (array $entry): bool => str_contains(mb_strtolower($entry['path']), $needle)
                                ));
                                $visibleDisabled = array_values(array_filter(
                                    $this->allowedDisabledEntries,
                                    fn (array $entry): bool => str_contains(mb_strtolower($entry['path']), $needle)
                                ));
                            }
                        @endphp

                        @if ($visible === [] && $visibleDisabled === [])
                            <x-ui.empty-state
                                icon="folder"
                                title="لا توجد نتائج"
                                description="جرّب كلمة بحث أخرى، أو ألغِ البحث لعرض كل الفولدرات والملفات."
                            />
                        @else
                            <div class="max-h-[55vh] overflow-y-auto rounded-lg border border-[var(--color-border)]">
                                <ul class="divide-y divide-[var(--color-border)]">
                                    @foreach ($visible as $entry)
                                        <li
                                            wire:key="entry-{{ $entry['path'] }}"
                                            class="flex items-center gap-3 px-3 py-2 hover:bg-[var(--color-bg-secondary)]/60"
                                        >
                                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="allowedSelected"
                                                    value="{{ $entry['path'] }}"
                                                    class="h-4 w-4 shrink-0 rounded border-[var(--color-border)] accent-[var(--accent-500)]"
                                                />
                                                <span class="flex items-center gap-1.5 text-[var(--color-text-muted)]">
                                                    <x-dynamic-component
                                                        :component="'heroicon-s-' . ($entry['type'] === 'dir' ? 'folder' : 'document-text')"
                                                        class="w-4 h-4 {{ $entry['type'] === 'dir' ? 'text-[var(--accent-500)]' : 'text-[var(--color-text-muted)]' }}"
                                                    />
                                                </span>
                                                <span
                                                    dir="ltr"
                                                    class="truncate font-mono text-xs text-[var(--color-text-primary)]"
                                                    title="{{ $entry['path'] }}"
                                                >
                                                    {{ $entry['label'] }}
                                                </span>
                                            </label>
                                            <span class="shrink-0 font-mono text-[10px] text-[var(--color-text-muted)]/70" dir="ltr">
                                                {{ $entry['type'] === 'dir' ? 'مجلد (يشمل ما بداخله)' : 'ملف' }}
                                            </span>
                                        </li>
                                    @endforeach

                                    {{-- Always-excluded entries — shown for transparency, never selectable --}}
                                    @foreach ($visibleDisabled as $entry)
                                        <li
                                            wire:key="entry-disabled-{{ $entry['path'] }}"
                                            class="flex items-center gap-3 px-3 py-2 opacity-50"
                                            title="مستبعد تلقائيًا من النشر"
                                        >
                                            <input
                                                type="checkbox"
                                                disabled
                                                class="h-4 w-4 shrink-0 rounded border-[var(--color-border)] opacity-40"
                                            />
                                            <span class="flex items-center gap-1.5 text-[var(--color-text-muted)]">
                                                <x-dynamic-component
                                                    :component="'heroicon-s-' . ($entry['type'] === 'dir' ? 'folder' : 'document-text')"
                                                    class="w-4 h-4"
                                                />
                                            </span>
                                            <span
                                                dir="ltr"
                                                class="truncate font-mono text-xs text-[var(--color-text-primary)]"
                                                title="{{ $entry['path'] }}"
                                            >
                                                {{ $entry['label'] }}
                                            </span>
                                            <span class="shrink-0 font-mono text-[10px] text-[var(--color-text-muted)]/70" dir="ltr">
                                                مستبعد تلقائيًا
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex items-center justify-between gap-3 border-t border-[var(--color-border)] pt-4">
                            <p class="text-sm text-[var(--color-text-muted)]">
                                سيتم حفظ {{ $this->allowedSelectedCount }} مسار مسموح — أي ملف خارج هذه القائمة يُتجاهل في الاستيراد والنشر، واختيار مجلد يغطي كل ما بداخله.
                            </p>
                            <x-ui.button variant="primary" icon="check" wire:click="saveAllowedPaths" :loading="$allowedSaving">
                                حفظ
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.modal>
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

@script
<script>
    Alpine.data('deployConnectModal', () => ({
        open: false,
        phase: 'connecting', // connecting | waiting | error
        elapsed: 0,
        timer: null,
        longWaitShown: false,
        actionLabel: '',

        start(action, $wire) {
            this.open = true;
            this.phase = 'connecting';
            this.longWaitShown = false;
            this.elapsed = 0;
            this.actionLabel = action === 'compare' ? 'مقارنة الملفات مع السيرفر' : 'نشر التغييرات إلى السيرفر';

            clearInterval(this.timer);
            this.timer = setInterval(() => {
                this.elapsed++;

                // After ~25s without a response, warn the user while still
                // letting the request run — the result appears when it lands.
                if (this.elapsed >= 25 && ! this.longWaitShown && this.phase === 'connecting') {
                    this.phase = 'waiting';
                }
            }, 1000);

            const request = action === 'compare'
                ? $wire.scanServerChanges()
                : $wire.startDeployment();

            request
                .then(() => this.finish())
                .catch(() => this.fail());
        },

        keepWaiting() {
            this.longWaitShown = true;
            this.phase = 'connecting';
        },

        finish() {
            clearInterval(this.timer);
            this.open = false;
        },

        fail() {
            clearInterval(this.timer);
            this.phase = 'error';
        },

        close() {
            clearInterval(this.timer);
            this.open = false;
        },
    }));
</script>
@endscript
