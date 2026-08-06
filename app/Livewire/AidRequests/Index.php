<?php

namespace App\Livewire\AidRequests;

use Illuminate\Database\Eloquent\Builder;

/**
 * قائمة طلبات المساعدة الموحّدة للمشرف والمندوب.
 *
 *  - المشرف: يرى كل طلبات المساعدة في النظام (لا قيد).
 *  - المندوب: يرى الطلبات التي قدّمها هو فقط (submitted_by = auth()->id()).
 *
 * لا حاجة لصنف منفصل لصفحة المندوب — الـ scope يُطبّق تلقائياً
 * حسب دور المستخدم المسجّل. القالب blade الواحد
 * livewire.pages.aid-requests.index يخدم المنظرين معاً عبر الـ hooks
 * showDelete() / showCreate() / pageTitle() المُتجاوزة هنا.
 */
class Index extends BaseAidRequestsIndex
{
    /**
     * قيد الـ scope: المدير يرى الكل، المندوب يرى طلباته فقط.
     * أي دور آخر يُعامَل كالمدير (سلوك مفتوح افتراضياً).
     */
    protected function scopedQuery(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user) {
            // لا أحد مسجّل: قيد مستحيل يضمن نتيجة فارغة.
            return $query->whereRaw('1 = 0');
        }

        // المندوب يرى الطلبات التي قدّمها هو فقط.
        return $user->isFieldworker()
            ? $query->where('submitted_by', $user->id)
            : $query; // المشرف (وأي دور آخر) يرى الكل
    }

    /**
     * زر الحذف يظهر للمشرف فقط؛ المندوب لا يحذف الطلبات من هذه الصفحة.
     */
    protected function showDelete(): bool
    {
        $user = auth()->user();

        return $user ? ! $user->isFieldworker() : false;
    }

    /**
     * زر «إضافة طلب» يظهر للمشرف فقط.
     */
    protected function showCreate(): bool
    {
        $user = auth()->user();

        return $user ? ! $user->isFieldworker() : false;
    }
}
