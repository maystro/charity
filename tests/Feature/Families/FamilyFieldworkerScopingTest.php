<?php

namespace Tests\Feature\Families;

use App\Livewire\Families\Index;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FamilyFieldworkerScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_approved_families(): void
    {
        $admin = User::factory()->admin()->create();
        Family::factory()->approved()->create(['case_name' => 'أسرة المدير الأولى']);
        Family::factory()->approved()->create(['case_name' => 'أسرة المدير الثانية']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertSee('أسرة المدير الأولى')
            ->assertSee('أسرة المدير الثانية');
    }

    public function test_fieldworker_sees_only_their_own_families(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();

        // أسرة قام المندوب بإنشائها (submitted_by)
        Family::factory()->approved()->create([
            'case_name' => 'أسرة قمت بإرسالها',
            'submitted_by' => $fieldworker->id,
        ]);

        // أسرة مرتبطة ببطاقة المندوب الميداني (fieldworker_id)
        Family::factory()->approved()->create([
            'case_name' => 'أسرة معنية بي',
            'fieldworker_id' => $fieldworker->fieldworker->id,
        ]);

        // أسرة مندوب آخر
        Family::factory()->approved()->create([
            'case_name' => 'أسرة مندوب آخر',
            'submitted_by' => $other->id,
        ]);

        Livewire::actingAs($fieldworker)
            ->test(Index::class)
            ->assertSee('أسرة قمت بإرسالها')
            ->assertSee('أسرة معنية بي')
            ->assertDontSee('أسرة مندوب آخر');
    }

    public function test_fieldworker_cmty_and_case_types_scoped(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();

        Family::factory()->approved()->create([
            'case_name' => 'أسرة الحقل',
            'submitted_by' => $fieldworker->id,
            'community' => 'حي المندوب',
            'case_type' => 'نوع مندوب',
        ]);
        Family::factory()->approved()->create([
            'case_name' => 'أسرة الآخر',
            'submitted_by' => $other->id,
            'community' => 'حي الآخر',
            'case_type' => 'نوع آخر',
        ]);

        $component = Livewire::actingAs($fieldworker)->test(Index::class);

        $communities = $component->get('communities');
        $this->assertContains('حي المندوب', $communities);
        $this->assertNotContains('حي الآخر', $communities);

        $caseTypes = $component->get('caseTypes');
        $this->assertContains('نوع مندوب', $caseTypes);
        $this->assertNotContains('نوع آخر', $caseTypes);
    }
}
