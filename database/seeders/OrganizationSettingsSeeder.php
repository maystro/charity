<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class OrganizationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::set(
            'organization_name',
            'جمعية التضامن الأجتماعي - بني سويف',
            'organization',
            'اسم المؤسسة',
            'string',
            'الاسم الرسمي الظاهر في رأس القائمة الجانبية'
        );

        SystemSetting::set(
            'organization_tagline',
            'جمعية عهد الخير للتنمية والخدمات',
            'organization',
            'الاسم التعريفي',
            'string',
            'الاسم التعريفي الظاهر تحت الشعار في رأس القائمة الجانبية'
        );

        SystemSetting::set(
            'organization_logo_path',
            Storage::disk('public')->exists('organization/logo.png') ? 'organization/logo.png' : '',
            'organization',
            'شعار المؤسسة',
            'string',
            'مسار شعار المؤسسة داخل التخزين العام'
        );
    }
}
