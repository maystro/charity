<?php

namespace Database\Seeders;

use App\Models\Fieldworker;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(OrganizationSettingsSeeder::class);

        $user = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدير النظام',
                'email' => 'admin@charity.org',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );

        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'accent_color' => 'copper',
                'font_size' => 'medium',
                'ui_density' => 'balanced',
                'sidebar_state' => 'open',
                'reduced_motion' => false,
            ]
        );

        // Super admin: technical account, isolated from the charity app.
        $superAdmin = User::query()->updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'السوبر أدمن التقني',
                'email' => 'superadmin@charity.org',
                'password' => 'password',
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );

        UserPreference::updateOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'accent_color' => 'copper',
                'font_size' => 'medium',
                'ui_density' => 'balanced',
                'sidebar_state' => 'open',
                'reduced_motion' => false,
            ]
        );

        $user = User::query()->updateOrCreate(
            ['username' => 'agent'],
            [
                'name' => 'مندوب بني سويف',
                'email' => 'agent@charity.org',
                'password' => 'password',
                'role' => User::ROLE_FIELDWORKER,
            ]
        );

        Fieldworker::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => 'FW-AGENT-01',
                'name' => 'مندوب بني سويف',
                'phone' => '01000000001',
                'governorate' => 'بني سويف',
                'area' => 'بني سويف',
                'status' => 'active',
                'notes' => 'مندوب افتراضي ضمن بيانات العرض',
            ]
        );

        $this->call(DemoDataSeeder::class);
    }
}
