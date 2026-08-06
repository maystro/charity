<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_can_be_created_for_all_governorates(): void
    {
        $user = User::factory()->admin()->create();

        Livewire::actingAs($user)
            ->test('projects.create-project')
            ->set('name', 'مشروع على مستوى الجمهورية')
            ->set('governorate', 'على مستوى كل المحافظات')
            ->set('phases.0.name', 'المرحلة الأولى')
            ->set('phases.0.cost', '1000')
            ->call('save')
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'name' => 'مشروع على مستوى الجمهورية',
            'governorate' => 'على مستوى كل المحافظات',
        ]);
    }
}
