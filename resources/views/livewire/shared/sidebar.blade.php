<?php

use App\Models\SystemSetting;
use App\Support\Navigation;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new
class extends Component
{
    public bool $isCollapsed = false;
    public bool $isMobileOpen = false;
    public string $activeGroup = '';
    public string $organizationName = 'منشأة خيرية';
    public string $organizationTagline = 'الاسم التعريفي';
    public ?string $organizationLogoUrl = null;

    public function navGroups(): array
    {
        return Navigation::groups();
    }

    /**
     * هل تظهر المجموعة للمستخدم الحالي بناءً على صلاحياته؟
     * المجموعات بدون مفتاح «roles» تظهر للجميع.
     */
    protected function groupVisibleForUser(string $groupKey, array $group): bool
    {
        return in_array($groupKey, array_keys(Navigation::visibleGroupsFor(auth()->user())), true);
    }

    /**
     * المجموعات الظاهرة للمستخدم الحالي فقط (بعد تطبيق فلتر الأدوار).
     */
    public function visibleNavGroups(): array
    {
        return Navigation::visibleGroupsFor(auth()->user());
    }

    public function toggleCollapse(): void
    {
        $this->isCollapsed = ! $this->isCollapsed;

        $user = auth()->user();
        if ($user) {
            $user->preferences()->updateOrCreate(
                ['user_id' => $user->id],
                ['sidebar_state' => $this->isCollapsed ? 'collapsed' : 'open']
            );
        }
    }

    public function toggleMobile(): void
    {
        $this->isMobileOpen = ! $this->isMobileOpen;
    }

    public function openMobile(): void
    {
        $this->isMobileOpen = true;
    }

    public function closeMobile(): void
    {
        $this->isMobileOpen = false;
    }


    public function mount(): void
    {
        $this->loadOrganizationProfile();

        $user = auth()->user();
        if ($user && $user->preferences) {
            $this->isCollapsed    = $user->preferences->sidebar_state === 'collapsed';
            $this->accentColor    = $user->preferences->accent_color ?: 'copper';
        }

        // Auto-detect and open the group that matches the current route.
        // This keeps the correct group expanded after every wire:navigate SPA transition.
        foreach ($this->visibleNavGroups() as $groupKey => $group) {
            if ($groupKey === 'dashboard') {
                continue; // dashboard items are top-level, not grouped
            }
            foreach ($group['items'] as $item) {
                if ($this->isItemActive($item)) {
                    $this->activeGroup = $groupKey;
                    break 2;
                }
            }
        }
    }

    protected function loadOrganizationProfile(): void
    {
        $this->organizationName = (string) SystemSetting::get('organization_name', 'منشأة خيرية');
        $this->organizationTagline = (string) SystemSetting::get('organization_tagline', 'الاسم التعريفي');

        $logoPath = SystemSetting::get('organization_logo_path');
        $this->organizationLogoUrl = $logoPath && Storage::disk('public')->exists($logoPath)
            ? asset('media/'.ltrim($logoPath, '/'))
            : null;
    }

    /**
     * تحديد ما إذا كان عنصر قائمة جانبية نشطاً بناءً على المسار الحالي.
     *
     * القواعد الموحّدة:
     *  - يُطابَق اسم المسار الأساسي أو أي مسار فرعي له (مثل aid-requests.create / .show / .edit)
     *    عبر إنشاء نمط `<base>.*` (بعد إزالة لاحقة `.index` إن وُجدت).
     *  - إن وُجدت `query` في الـ item: يلزم مطابقة كل بارامترات الـ query في الطلب الحالي.
     *  - إن لم توجد `query` وتم ذكر `exclude_query` (مثل families مع status): لا يُعتبر نشطاً
     *    عند وجود تلك البارامترات في الطلب، عكس ذلك يُعتبر نشطاً.
     *  - يمكن تخصيص نمط مطابقة عبر `match_pattern` (regex يعمل على اسم المسار الحالي).
     *
     * @param  array<string, mixed>  $item
     */
    protected function isItemActive(array $item): bool
    {
        $base = $item['route'] ?? '';
        if ($base === '') {
            return false;
        }

        // نمط مطابقة مخصص (regex على اسم المسار الحالي) — يلغي الحاجة لتكرار الشروط
        if (! empty($item['match_pattern'])) {
            return (bool) preg_match('#' . str_replace('#', '\#', $item['match_pattern']) . '#', (string) request()->route()?->getName());
        }

        $current = (string) request()->route()?->getName();

        // مطابقة تامة مع اسم المسار الأساسي للعنصر
        if ($current === $base) {
            return $this->matchesQueryConstraints($item);
        }

        // مطابقة المسارات الفرعية (مثل aid-requests.create / .show / .edit)
        $routePattern = str_ends_with($base, '.index')
            ? substr($base, 0, -strlen('.index'))
            : $base;
        if (! request()->routeIs($routePattern . '.*')) {
            return false;
        }

        // ضمان عدم مطابقة مسار فرعي مملوك لعنصر آخر في القائمة (مثل families.re-assessment-index
        // الخاص بـ«إعادة التقييم» لا ينبغي أن ينشّط عنصر «الأسر والحالات»).
        foreach ($this->allNavRoutes() as $otherRoute) {
            if ($otherRoute !== $base && $current === $otherRoute) {
                return false;
            }
        }

        return $this->matchesQueryConstraints($item);
    }

    /**
     * التحقق من قيود الـ query على العنصر النشط.
     *
     * @param  array<string, mixed>  $item
     */
    protected function matchesQueryConstraints(array $item): bool
    {
        // وجود بارامترات query في الـ item — لزم مطابقتها كلها
        if (! empty($item['query'])) {
            foreach ($item['query'] as $k => $v) {
                if (request()->query($k) !== $v) {
                    return false;
                }
            }

            return true;
        }

        // لا يوجد query في الـ item: لزم ألا يكون واحد من البارامترات المُستثناة موجوداً
        foreach (($item['exclude_query'] ?? []) as $k) {
            if (! is_null(request()->query($k))) {
                return false;
            }
        }

        return true;
    }

    /**
     * كل أسماء المسارات المعلنة في القائمة الجانبية (لتمييز المسارات المستقلة عن المسارات الفرعية).
     *
     * @return array<int, string>
     */
    protected function allNavRoutes(): array
    {
        return Navigation::allRouteNames();
    }

    #[\Livewire\Attributes\On('sidebar-state-changed')]
    public function updateSidebarState(bool $collapsed): void
    {
        $this->isCollapsed = $collapsed;
    }

    #[On('organization-updated')]
    public function refreshOrganizationProfile(): void
    {
        $this->loadOrganizationProfile();
    }
}

?>

<div
    @toggle-mobile-sidebar.window="$wire.call('openMobile')"
    class="glass rounded-[var(--radius-xl)] flex flex-col h-full transition-all duration-300 overflow-hidden shrink-0 fixed lg:static right-0 z-[var(--z-sidebar)] lg:z-auto"
    :class="{ 'hidden lg:flex': !$wire.isMobileOpen, 'flex': $wire.isMobileOpen || window.innerWidth >= 1024 }"
    style="width: {{ $isCollapsed ? 'var(--sidebar-collapsed-width)' : 'var(--sidebar-width)' }}; background: var(--color-sidebar-bg); backdrop-filter: blur(var(--glass-blur)); border: 1px solid var(--glass-border);">

    <div class="relative p-4 border-b" style="border-color: var(--color-border);">
        <button
            wire:click="toggleCollapse"
            class="absolute left-4 top-4 p-2 rounded-lg hover:bg-black/5 transition-colors shrink-0"
            aria-label="{{ $isCollapsed ? 'فتح القائمة' : 'طي القائمة' }}"
        >
            <svg class="w-5 h-5 transition-transform {{ $isCollapsed ? 'rotate-180' : '' }}" style="color: var(--color-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>

        <div class="flex flex-col items-center justify-center gap-2 text-center pt-2">
            <div class="{{ $isCollapsed ? 'w-12 h-12' : 'w-16 h-16' }} rounded-2xl overflow-hidden border border-[var(--color-border)] bg-white flex items-center justify-center shadow-sm">
                @if($organizationLogoUrl)
                    <img src="{{ $organizationLogoUrl }}" alt="{{ $organizationName }}" class="w-full h-full object-contain p-2" />
                @else
                    <x-heroicon-o-building-office-2 class="w-8 h-8 text-[var(--accent-500)]" />
                @endif
            </div>
            @unless($isCollapsed)
                <div class="space-y-0.5">
                    <p class="text-xs text-[var(--color-text-muted)]">{{ $organizationTagline }}</p>
                    <p class="text-sm font-bold leading-tight" style="color: var(--color-primary-800);">{{ $organizationName }}</p>
                </div>
            @endunless
        </div>
    </div>

    <style>
        .sidebar-badge {
            min-width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            font-size: 10px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: #fff;
            flex-shrink: 0;
            position: relative;
            top: 1px;
        }

        .sidebar-badge__number {
            transform: translateY(1px);
            font-size: 11px;
        }
    </style>

    <nav
        class="sidebar-nav flex-1 overflow-y-auto py-3 px-2 space-y-1"
        data-sidebar-scroll
    >
        @foreach($this->visibleNavGroups() as $groupKey => $group)
            @if($groupKey === 'dashboard')
                @foreach($group['items'] as $item)
                    @php $itemActive = $this->isItemActive($item); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors {{ $itemActive ? 'font-semibold' : 'hover:bg-black/5 hover:text-[var(--color-text-primary)]' }}"
                        style="{{ $itemActive ? 'background: var(--accent-50); color: var(--accent-600);' : 'color: var(--color-text-secondary);' }}"
                        @if($isCollapsed) title="{{ $item['label'] }}" @endif
                    >
                        <span class="shrink-0 w-5 h-5 flex items-center justify-center">
                            <x-dynamic-component :component="'heroicon-s-' . $item['icon']" class="w-5 h-5 text-[var(--accent-500)]" />
                        </span>
                        @unless($isCollapsed)
                            <span>{{ $item['label'] }}</span>
                        @endunless
                    </a>
                @endforeach
                <div class="my-2 border-t" style="border-color: var(--color-border);"></div>
            @else
                {{-- عنوان المجموعة (ثابت — ليس قابلاً للطي) --}}
                @unless($isCollapsed)
                    <div class="px-3 pt-3 pb-1 text-base font-bold tracking-wide" style="color: var(--color-text-muted);">
                        {{ $group['label'] }}
                    </div>
                @else
                    <div class="my-2 border-t" style="border-color: var(--color-border);"></div>
                @endunless

                {{-- العناصر مفرودة دائماً --}}
                @foreach($group['items'] as $item)
                    @php
                        $itemHref = route($item['route'], $item['query'] ?? []);
                        $itemActive = $this->isItemActive($item);
                    @endphp
                    <a
                        href="{{ $itemHref }}"
                        wire:navigate
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] transition-colors {{ $itemActive ? 'font-semibold' : 'hover:bg-black/5 hover:text-[var(--color-text-primary)]' }}"
                        style="{{ $itemActive ? 'background: var(--accent-50); color: var(--accent-600);' : 'color: var(--color-text-secondary);' }}"
                        @if($isCollapsed) title="{{ $item['label'] }}" @endif
                    >
                        <span class="shrink-0 w-4.5 h-4.5 flex items-center justify-center">
                            <x-dynamic-component :component="'heroicon-o-' . $item['icon']" class="w-4.5 h-4.5 text-[var(--accent-500)]" />
                        </span>
                        @unless($isCollapsed)
                            <span class="flex-1">{{ $item['label'] }}</span>
                        @endunless
                        @if(!empty($item['badge']))
                            @php
                                $badgeCount = match ($item['badge']) {
                                    'families_pending' => \App\Models\Family::whereIn('status', ['under_review', 'draft', 'needs_completion'])->count(),
                                    'reassessment_overdue' => \App\Models\Alert::active()->forType(\App\Models\Alert::TYPE_REASSESSMENT_OVERDUE)->count(),
                                    'aid_requests_pending' => \App\Models\AidRequest::whereIn('status', ['submitted', 'under_review'])->count(),
                                    'visits_overdue' => \App\Models\Visit::where('is_overdue', true)->whereIn('status', \App\Enums\VisitStatus::pendingStatuses())->count(),
                                    'projects_active' => \App\Models\Project::where('status', 'active')->count(),
                                    default => 0,
                                };
                                $badgeColor = match ($item['badge']) {
                                    'families_pending' => 'var(--color-warning-500)',
                                    'reassessment_overdue' => 'var(--color-danger-500)',
                                    'aid_requests_pending' => 'var(--color-info-500)',
                                    'visits_overdue' => 'var(--color-danger-500)',
                                    'projects_active' => 'var(--color-success-500)',
                                    default => 'var(--color-text-muted)',
                                };
                                $badgePulse = in_array($item['badge'], ['reassessment_overdue', 'visits_overdue'], true);
                            @endphp
                            @if($badgeCount > 0)
                                <span class="sidebar-badge ms-auto {{ $badgePulse ? 'animate-pulse' : '' }}" style="background: {{ $badgeColor }};">
                                    <span class="sidebar-badge__number">{{ $badgeCount }}</span>
                                </span>
                            @endif
                        @endif
                    </a>
                @endforeach
            @endif
        @endforeach
    </nav>

    <div class="border-t p-3" style="border-color: var(--color-border);">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0" style="background: var(--accent-500);">
                {{ substr(auth()->user()->name ?? 'م', 0, 1) }}
            </div>
            @unless($isCollapsed)
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate" style="color: var(--color-text-primary);">{{ auth()->user()->name ?? '' }}</p>
                    <p class="text-xs truncate" style="color: var(--color-text-muted);">{{ auth()->user()->email ?? '' }}</p>
                </div>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="p-1.5 rounded-lg hover:bg-black/5 transition-colors" aria-label="خيارات المستخدم">
                        <svg class="w-4 h-4" style="color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"/>
                        </svg>
                    </button>
                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute bottom-full left-0 mb-2 w-52 rounded-xl shadow-xl border p-1.5 z-50 bg-white/95 backdrop-blur-md"
                        style="border-color: var(--color-border);"
                    >
                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg hover:bg-black/5 transition-colors" style="color: var(--color-text-secondary);">
                            <x-heroicon-o-user class="w-4.5 h-4.5 text-[var(--accent-500)]" />
                            <span>الملف الشخصي</span>
                        </a>
                        <button type="button" x-data @click="$dispatch('open-modal', 'user-preferences')" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg hover:bg-black/5 transition-colors text-right" style="color: var(--color-text-secondary);">
                            <x-heroicon-o-adjustments-horizontal class="w-4.5 h-4.5 text-[var(--accent-500)]" />
                            <span>تفضيلات الواجهة</span>
                        </button>
                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg hover:bg-black/5 transition-colors" style="color: var(--color-text-secondary);">
                            <x-heroicon-o-key class="w-4.5 h-4.5 text-[var(--accent-500)]" />
                            <span>تغيير كلمة المرور</span>
                        </a>
                        <hr class="my-1.5" style="border-color: var(--color-border);" />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg hover:bg-red-50 transition-colors text-red-600 text-right">
                                <x-heroicon-o-arrow-left-on-rectangle class="w-4.5 h-4.5 text-red-500" />
                                <span>تسجيل الخروج</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>
</div>

@if($isMobileOpen)
    <div class="fixed inset-0 bg-black/50 z-[var(--z-overlay)] lg:hidden" wire:click="closeMobile"></div>
@endif
