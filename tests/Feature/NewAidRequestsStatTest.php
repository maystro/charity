<?php

namespace Tests\Feature;

use App\Livewire\Shared\NewAidRequestsStat;
use App\Models\AidRequest;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewAidRequestsStatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_new_requests_count_is_zero_when_no_requests_await_review(): void
    {
        AidRequest::factory()->approved()->create();

        Livewire::test(NewAidRequestsStat::class)
            ->assertSet('newRequestsCount', 0);
    }

    public function test_new_requests_count_includes_under_review_statuses_only(): void
    {
        AidRequest::factory()->submitted()->create(['title' => 'مقدمة']);
        AidRequest::factory()->needsCompletion()->create(['title' => 'تحتاج استكمال']);
        AidRequest::factory()->underReview()->create(['title' => 'تحت المراجعة']);
        AidRequest::factory()->approved()->create(['title' => 'معتمدة']);

        Livewire::test(NewAidRequestsStat::class)
            ->assertSet('newRequestsCount', 3);
    }

    public function test_top_new_requests_returns_requests_ordered_by_created_at(): void
    {
        $first = AidRequest::factory()->submitted()->create(['title' => 'أولاً']);
        $second = AidRequest::factory()->underReview()->create(['title' => 'ثانياً']);

        $component = Livewire::test(NewAidRequestsStat::class);
        $top = $component->get('topNewRequests');

        $this->assertCount(2, $top);
        $this->assertTrue($top->contains('id', $first->id));
        $this->assertTrue($top->contains('id', $second->id));
    }

    public function test_top_new_requests_caps_at_five(): void
    {
        AidRequest::factory()->count(7)->submitted()->create();

        $component = Livewire::test(NewAidRequestsStat::class);

        $this->assertCount(5, $component->get('topNewRequests'));
        $this->assertSame(7, $component->get('newRequestsCount'));
    }

    public function test_fieldworker_sees_only_their_own_requests(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();

        $family = Family::factory()->approved()->create();

        AidRequest::factory()->for($family)->submitted()->create([
            'title' => 'طلب المندوب الحالي',
            'submitted_by' => $fieldworker->id,
        ]);
        AidRequest::factory()->for($family)->submitted()->create([
            'title' => 'طلب مندوب آخر',
            'submitted_by' => $other->id,
        ]);

        $this->actingAs($fieldworker);

        $component = Livewire::test(NewAidRequestsStat::class);
        $top = $component->get('topNewRequests');

        $this->assertSame(1, $component->get('newRequestsCount'));
        $this->assertTrue($top->contains('title', 'طلب المندوب الحالي'));
        $this->assertFalse($top->contains('title', 'طلب مندوب آخر'));
    }
}
