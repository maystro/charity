<div class="space-y-6">
    @php
        $latitude = $fieldworker->latitude !== null ? (float) $fieldworker->latitude : null;
        $longitude = $fieldworker->longitude !== null ? (float) $fieldworker->longitude : null;
        $apiKey = config('services.google_maps.api_key');
        $hasApiKey = !empty($apiKey);
        $lat = $latitude ?? 0;
        $lng = $longitude ?? 0;
        $staticMapUrl = $hasApiKey
            ? "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom=12&size=640x300&scale=2&markers=color:red%7C{$lat},{$lng}&key={$apiKey}"
            : null;
        $linkMapUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode(($fieldworker->governorate ?? '') . ' ' . $fieldworker->name);
    @endphp

    <script src="https://maps.googleapis.com/maps/api/js?v=weekly&{{ $hasApiKey ? 'libraries=marker&key=' . $apiKey : '' }}&loading=async" async></script>

    {{-- Page Header --}}
    <x-layout.page-header
        title="{{ $fieldworker->name }}"
        subtitle="الكود: {{ $fieldworker->code }}"
        :breadcrumbs="[
            ['label' => 'المندوبون والباحثون', 'route' => 'fieldworkers.index'],
            ['label' => $fieldworker->code],
        ]"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="arrow-left" href="{{ route('fieldworkers.index') }}" wire:navigate>
                رجوع للقائمة
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Status Bar --}}
    <x-ui.card padding>
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6 flex-wrap">
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">الكود</p>
                    <p class="font-semibold text-[var(--color-text-primary)] font-mono">{{ $fieldworker->code }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">الاسم</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $fieldworker->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">الهاتف</p>
                    <p class="font-semibold text-[var(--color-text-primary)]" dir="ltr">{{ $fieldworker->phone ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">المحافظة</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $fieldworker->governorate ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">الإحداثيات</p>
                    <p class="font-semibold text-[var(--color-text-primary)] text-xs font-mono" dir="ltr">
                        @if($latitude !== null && $longitude !== null)
                            {{ number_format($latitude, 5) }}, {{ number_format($longitude, 5) }}
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>
            @if($fieldworker->status === 'active')
                <x-ui.badge variant="success" dot>نشط</x-ui.badge>
            @else
                <x-ui.badge variant="neutral" dot>غير نشط</x-ui.badge>
            @endif
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Map column --}}
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card padding>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[var(--color-text-primary)] flex items-center gap-2">
                        <x-dynamic-component :component="'heroicon-s-map-pin'" class="w-5 h-5 text-[var(--accent-500)]" />
                        تحديد الموقع على الخريطة
                    </h3>
                    <x-ui.button
                        variant="ghost"
                        size="sm"
                        icon="arrow-top-right-on-square"
                        href="{{ $linkMapUrl }}"
                        target="_blank"
                        rel="noopener"
                    >
                        فتح في خرائط جوجل
                    </x-ui.button>
                </div>

                <div
                    wire:ignore
                    x-data="{
                        map: null,
                        marker: null,
                        init() {
                            if (typeof google === 'undefined' || !google.maps) return;
                            const lat = {!! $latitude !== null ? $latitude : 'null' !!};
                            const lng = {!! $longitude !== null ? $longitude : 'null' !!};
                            const hasCoords = lat !== null && lng !== null;
                            const center = hasCoords
                                ? { lat: lat, lng: lng }
                                : { lat: 33.5138, lng: 36.2765 };
                            this.map = new google.maps.Map(this.$refs.mapCanvas, {
                                zoom: hasCoords ? 13 : 7,
                                center: center,
                                mapTypeControl: false,
                                streetViewControl: false,
                            });
                            if (hasCoords) {
                                this.marker = new google.maps.Marker({
                                    position: center,
                                    map: this.map,
                                    title: {!! json_encode($fieldworker->name) !!},
                                });
                            } else {
                                this.marker = new google.maps.Marker({
                                    position: center,
                                    map: this.map,
                                    title: {!! json_encode($fieldworker->governorate ?? 'سوريا') !!},
                                    label: {!! json_encode(mb_substr($fieldworker->governorate ?? '?', 0, 1)) !!},
                                });
                            }
                        }
                    }"
                    x-init="init()"
                >
                    <div
                        x-ref="mapCanvas"
                        class="w-full h-[300px] rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-[var(--color-bg-secondary)]"
                    ></div>
                    @if($latitude === null || $longitude === null)
                        <p class="text-xs text-[var(--color-text-muted)] mt-2">
                            لم يتم تحديد إحداثيات GPS دقيقة — يعرض الموقع المحافظة تقريبياً.
                        </p>
                    @endif
                </div>
            </x-ui.card>

            {{-- Families list --}}
            <x-ui.card padding>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-[var(--color-text-primary)] flex items-center gap-2">
                        <x-dynamic-component :component="'heroicon-s-user-group'" class="w-5 h-5 text-[var(--accent-500)]" />
                        الأسر التي قام بها
                        <x-ui.badge variant="secondary" size="sm">{{ $stats['total'] }}</x-ui.badge>
                    </h3>
                </div>

                @if($families->isEmpty())
                    <x-ui.empty-state
                        icon="user-group"
                        title="لا توجد أسر"
                        description="لم يقم هذا المندوب بإجراء أي بحوث حتى الآن"
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-border)]">
                                    <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">رقم الحالة</th>
                                    <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">الاسم</th>
                                    <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">المنطقة</th>
                                    <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider">الحالة</th>
                                    <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--color-border)]">
                                @foreach($families as $family)
                                    @php
                                        $familyStatus = \App\Enums\FamilyStatus::tryFrom($family->status) ?? \App\Enums\FamilyStatus::Approved;
                                    @endphp
                                    <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors" wire:key="family-{{ $family->id }}">
                                        <td class="px-4 py-3 font-mono text-xs text-[var(--color-text-secondary)]">{{ $family->case_number }}</td>
                                        <td class="px-4 py-3 font-medium text-[var(--color-text-primary)]">{{ $family->case_name }}</td>
                                        <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $family->community ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <x-ui.badge :variant="$familyStatus->variant()" dot>{{ $familyStatus->label() }}</x-ui.badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-ui.button variant="ghost" size="sm" icon="eye" href="{{ route('families.show', $family) }}" wire:navigate>
                                                عرض
                                            </x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $families->links() }}
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Stats column --}}
        <div class="space-y-6">
            <x-ui.card padding>
                <h3 class="font-semibold text-[var(--color-text-primary)] mb-4">إحصائيات</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--color-bg-secondary)]/40">
                        <span class="text-sm text-[var(--color-text-secondary)]">إجمالي الأسر</span>
                        <span class="text-lg font-bold text-[var(--color-text-primary)]">{{ $stats['total'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--color-success-50)]/50">
                        <span class="text-sm text-[var(--color-text-secondary)]">معتمدة</span>
                        <span class="text-lg font-bold text-[var(--color-success-500)]">{{ $stats['approved'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--color-warning-50)]/50">
                        <span class="text-sm text-[var(--color-text-secondary)]">قيد المراجعة</span>
                        <span class="text-lg font-bold text-[var(--color-warning-500)]">{{ $stats['underReview'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-[var(--color-bg-secondary)]/40">
                        <span class="text-sm text-[var(--color-text-secondary)]">مسودات</span>
                        <span class="text-lg font-bold text-[var(--color-text-primary)]">{{ $stats['drafts'] }}</span>
                    </div>
                </div>
            </x-ui.card>

            @if($fieldworker->notes)
                <x-ui.card padding>
                    <h3 class="font-semibold text-[var(--color-text-primary)] mb-3">ملاحظات</h3>
                    <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">{{ $fieldworker->notes }}</p>
                </x-ui.card>
            @endif

            <x-ui.card padding>
                <h3 class="font-semibold text-[var(--color-text-primary)] mb-4">معلومات إضافية</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-[var(--color-text-muted)]">تاريخ الإضافة</dt>
                        <dd class="text-[var(--color-text-primary)] mt-0.5">{{ $fieldworker->created_at->format('Y/m/d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[var(--color-text-muted)]">آخر تحديث</dt>
                        <dd class="text-[var(--color-text-primary)] mt-0.5">{{ $fieldworker->updated_at->format('Y/m/d') }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>
</div>