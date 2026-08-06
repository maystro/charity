<?php

namespace Tests\Feature\Auth;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_page_full_screen(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('organization/logo.png', 'fake-logo');

        SystemSetting::set('organization_name', 'جمعية التضامن الأجتماعي - بني سويف', 'organization', 'اسم المؤسسة', 'string');
        SystemSetting::set('organization_tagline', 'جمعية عهد الخير للتنمية والخدمات', 'organization', 'الاسم التعريفي', 'string');
        SystemSetting::set('organization_logo_path', 'organization/logo.png', 'organization', 'شعار المؤسسة', 'string');

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('تسجيل الدخول');
        $response->assertSee('اسم المستخدم');
        $response->assertSee('كلمة المرور');
        $response->assertSee('bg-white backdrop-blur-sm border border-white/20');
        $response->assertSee('inline-flex items-center justify-center text-white font-semibold text-base', false);
        $response->assertSee('جمعية التضامن الأجتماعي - بني سويف');
        $response->assertSee('جمعية عهد الخير للتنمية والخدمات');
        $response->assertSee('media/organization/logo.png');
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_login_with_email_only_username_is_allowed(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        Livewire::test('login')
            ->set('username', 'admin@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'username' => 'adminuser',
            'password' => 'password123',
        ]);

        Livewire::test('login')
            ->set('username', 'adminuser')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_login_fails_with_short_password(): void
    {
        Livewire::test('login')
            ->set('username', 'admin@example.com')
            ->set('password', '123')
            ->call('login')
            ->assertHasErrors('password');

        $this->assertGuest();
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'fieldworker_one',
            'email' => 'someone@example.com',
            'password' => 'password123',
        ]);

        Livewire::test('login')
            ->set('username', 'fieldworker_one')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_username_fails_login(): void
    {
        User::factory()->create([
            'username' => 'valid_user',
            'password' => 'password123',
        ]);

        Livewire::test('login')
            ->set('username', 'valid_user')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }
}
