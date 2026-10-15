<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForceLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_logout_page_is_public(): void
    {
        $this->get(route('force-logout'))
            ->assertOk()
            ->assertSee('تسجيل الخروج الآن')
            ->assertSee('/force-logout');
    }

    public function test_force_logout_now_ends_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->assertAuthenticated();

        $this->get(route('force-logout.now'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
