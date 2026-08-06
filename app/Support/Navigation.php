<?php

namespace App\Support;

use App\Models\User;

class Navigation
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => 'لوحة التحكم',
                'visibility' => 'all',
                'items' => [
                    ['label' => 'لوحة التحكم', 'route' => 'dashboard', 'icon' => 'home', 'permission' => 'dashboard'],
                ],
            ],
            'case_management' => [
                'label' => 'الحالات والمساعدات',
                'visibility' => 'user-permission',
                'items' => [
                    ['label' => 'الأسر والحالات', 'route' => 'families.index', 'icon' => 'users', 'exclude_query' => ['status'], 'badge' => 'families_pending', 'permission' => 'families.index'],
                    ['label' => 'إعادة التقييم', 'route' => 'families.re-assessment-index', 'icon' => 'arrow-path', 'badge' => 'reassessment_overdue', 'permission' => 'families.re-assessment-index'],
                    ['label' => 'طلبات المساعدة', 'route' => 'aid-requests.index', 'icon' => 'hand-raised', 'badge' => 'aid_requests_pending', 'permission' => 'aid-requests.index'],
                    ['label' => 'الزيارات والمتابعات', 'route' => 'visits.index', 'icon' => 'calendar-days', 'badge' => 'visits_overdue', 'permission' => 'visits.index'],
                    ['label' => 'التنفيذ والمتابعة', 'route' => 'delivery.index', 'icon' => 'truck', 'permission' => 'delivery.index'],
                ],
            ],
            'fieldwork' => [
                'label' => 'العمل الميداني',
                'visibility' => 'fieldworker',
                'items' => [
                    ['label' => 'الأسر والحالات', 'route' => 'families.index', 'icon' => 'users', 'exclude_query' => ['status'], 'permission' => 'families.index'],
                    ['label' => 'طلبات المساعدة', 'route' => 'aid-requests.index', 'icon' => 'hand-raised', 'permission' => 'aid-requests.index'],
                    ['label' => 'الزيارات والمتابعات', 'route' => 'visits.index', 'icon' => 'calendar-days', 'permission' => 'visits.index'],
                    ['label' => 'التنفيذ والمتابعة', 'route' => 'delivery.index', 'icon' => 'truck', 'permission' => 'delivery.index'],
                ],
            ],
            'projects_donations' => [
                'label' => 'المشروعات و التبرعات',
                'visibility' => 'user-permission',
                'items' => [
                    ['label' => 'المشروعات', 'route' => 'projects.index', 'icon' => 'briefcase', 'badge' => 'projects_active', 'permission' => 'projects.index'],
                    ['label' => 'المتبرعون', 'route' => 'donors.index', 'icon' => 'heart', 'permission' => 'donors.index'],
                    ['label' => 'التبرعات', 'route' => 'donations.index', 'icon' => 'currency-dollar', 'permission' => 'donations.index'],
                ],
            ],
            'admin' => [
                'label' => 'إدارة النظام',
                'visibility' => 'admin',
                'items' => [
                    ['label' => 'بيانات المؤسسة', 'route' => 'organization.index', 'icon' => 'building-office-2', 'permission' => 'organization.index', 'assignable' => false],
                    ['label' => 'المندوبون والباحثون', 'route' => 'fieldworkers.index', 'icon' => 'user-group', 'permission' => 'fieldworkers.index', 'assignable' => false],
                    ['label' => 'المستخدمون والصلاحيات', 'route' => 'users.index', 'icon' => 'users', 'permission' => 'users.index', 'assignable' => false],
                    ['label' => 'إعدادات النظام', 'route' => 'settings.index', 'icon' => 'cog', 'permission' => 'settings.index', 'assignable' => false],
                ],
            ],
            'super_admin' => [
                'label' => 'الإدارة التقنية',
                'visibility' => 'super_admin',
                'items' => [
                    ['label' => 'الإدارة التقنية', 'route' => 'deployments.index', 'icon' => 'server-stack', 'permission' => 'deployments.index', 'assignable' => false],
                    ['label' => 'النشر الذكي', 'route' => 'deployments.smart-deployment', 'icon' => 'sparkles', 'permission' => 'deployments.index', 'assignable' => false],
                    ['label' => 'المسارات المسموحة', 'route' => 'deployments.allowed-paths', 'icon' => 'shield-check', 'permission' => 'deployments.index', 'assignable' => false],
                    ['label' => 'صيانة', 'route' => 'deployments.maintenance', 'icon' => 'wrench-screwdriver', 'permission' => 'deployments.index', 'assignable' => false],
                    ['label' => 'النسخ الاحتياطية', 'route' => 'backups.index', 'icon' => 'archive-box', 'permission' => 'backups.index', 'assignable' => false],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function permissionGroups(): array
    {
        return array_filter(self::groups(), static fn (array $group): bool => ($group['visibility'] ?? null) === 'user-permission');
    }

    /**
     * @return array<string, string>
     */
    public static function permissionOptions(): array
    {
        $options = [];

        foreach (self::permissionGroups() as $group) {
            foreach ($group['items'] as $item) {
                $permission = (string) ($item['permission'] ?? $item['route'] ?? '');

                if ($permission === '') {
                    continue;
                }

                $options[$permission] = $item['label'];
            }
        }

        return $options;
    }

    public static function visibleGroupsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin()) {
            return array_filter(self::groups(), static fn (array $group): bool => ($group['visibility'] ?? null) === 'super_admin');
        }

        if ($user->isAdmin()) {
            return array_filter(self::groups(), static fn (array $group): bool => ($group['visibility'] ?? null) !== 'fieldworker' && ($group['visibility'] ?? null) !== 'super_admin');
        }

        if ($user->isFieldworker()) {
            return array_filter(self::groups(), static fn (array $group): bool => ($group['visibility'] ?? null) === 'all' || ($group['visibility'] ?? null) === 'fieldworker');
        }

        $allowedGroups = [];
        $seenPermissions = [];

        foreach (self::permissionGroups() as $groupKey => $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                $permission = (string) ($item['permission'] ?? $item['route'] ?? '');

                if ($permission === '' || ! $user->canAccessMenu($permission) || isset($seenPermissions[$permission])) {
                    continue;
                }

                $seenPermissions[$permission] = true;
                $items[] = $item;
            }

            if ($items !== []) {
                $group['items'] = $items;
                $allowedGroups[$groupKey] = $group;
            }
        }

        return $allowedGroups;
    }

    public static function canAccessRoute(User $user, ?string $routeName): bool
    {
        if ($routeName === null || $routeName === '') {
            return true;
        }

        if ($user->isSuperAdmin()) {
            if ($routeName === 'dashboard' || $routeName === 'home') {
                return true;
            }

            return self::routeBelongsToGroup($routeName, self::groups()['super_admin']['items']);
        }

        if ($user->isAdmin()) {
            // Charity admins must never reach the technical super-admin area.
            if (self::routeBelongsToGroup($routeName, self::groups()['super_admin']['items'])) {
                return false;
            }

            return true;
        }

        if ($routeName === 'dashboard' || $routeName === 'home') {
            return true;
        }

        if ($user->isFieldworker()) {
            return self::routeBelongsToGroup($routeName, self::groups()['fieldwork']['items']);
        }

        foreach (self::permissionGroups() as $group) {
            foreach ($group['items'] as $item) {
                $permission = (string) ($item['permission'] ?? $item['route'] ?? '');

                if ($permission === '' || ! $user->canAccessMenu($permission)) {
                    continue;
                }

                if (self::routeBelongsToItem($routeName, $item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected static function routeBelongsToGroup(string $routeName, array $items): bool
    {
        foreach ($items as $item) {
            if (self::routeBelongsToItem($routeName, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected static function routeBelongsToItem(string $routeName, array $item): bool
    {
        $base = (string) ($item['route'] ?? '');

        if ($base === '') {
            return false;
        }

        if ($routeName === $base) {
            return true;
        }

        $routePattern = str_ends_with($base, '.index')
            ? substr($base, 0, -strlen('.index'))
            : $base;

        if ($routePattern === '') {
            return false;
        }

        if (! str_starts_with($routeName, $routePattern.'.')) {
            return false;
        }

        foreach (self::allRouteNames() as $otherRoute) {
            if ($otherRoute !== $base && $routeName === $otherRoute) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public static function allRouteNames(): array
    {
        $routes = [];

        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                if (! empty($item['route'])) {
                    $routes[] = (string) $item['route'];
                }
            }
        }

        return array_values(array_unique($routes));
    }
}
