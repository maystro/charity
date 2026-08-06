<?php

namespace Tests\Feature;

use App\Enums\FamilyStatus;
use App\Livewire\Shared\PendingApprovalsStat;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingApprovalsStatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_pending_count_is_zero_when_no_families_await_approval(): void
    {
        Family::factory()->approved()->create();

        Livewire::test(PendingApprovalsStat::class)
            ->assertSet('pendingCount', 0);
    }

    public function test_pending_count_includes_under_review_and_needs_completion(): void
    {
        Family::factory()->underReview()->create(['case_name' => 'أسرة تحت المراجعة']);
        Family::factory()->needsCompletion()->create(['case_name' => 'أسرة تحتاج استكمال']);
        Family::factory()->approved()->create(['case_name' => 'أسرة معتمدة']);

        Livewire::test(PendingApprovalsStat::class)
            ->assertSet('pendingCount', 2);
    }

    public function test_top_pending_families_returns_pending_only_ordered_by_created_at(): void
    {
        $first = Family::factory()->underReview()->create(['case_name' => 'أولاً']);
        $second = Family::factory()->needsCompletion()->create(['case_name' => 'ثانياً']);

        $component = Livewire::test(PendingApprovalsStat::class);
        $top = $component->get('topPendingFamilies');

        $this->assertCount(2, $top);
        $this->assertTrue($top->contains('id', $first->id));
        $this->assertTrue($top->contains('id', $second->id));
        $this->assertNotContains(FamilyStatus::Approved->value, $top->pluck('status')->all());
    }

    public function test_top_pending_families_caps_at_five(): void
    {
        Family::factory()->count(7)->underReview()->create();

        $component = Livewire::test(PendingApprovalsStat::class);

        $this->assertCount(5, $component->get('topPendingFamilies'));
        $this->assertSame(7, $component->get('pendingCount'));
    }
}
