<?php

namespace Tests\Unit\ExecutionSchedule;

use App\Enums\AidRequestStatus;
use App\Enums\ExecutionDueState;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Family;
use App\Models\User;
use App\Services\ExecutionSchedule\ExecutionScheduleResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionScheduleResolverTest extends TestCase
{
    use RefreshDatabase;

    private ExecutionScheduleResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ExecutionScheduleResolver;
    }

    private function createApprovedItem(array $requestOverrides = [], array $itemOverrides = []): AidRequestItem
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);

        $aidRequest = AidRequest::factory()->for($family)->approved()->create(array_merge([
            'created_by' => $user->id,
            'submitted_by' => $user->id,
            'needed_by' => '2026-10-20',
        ], $requestOverrides));

        return AidRequestItem::create(array_merge([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => 'بند اختبار',
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 100,
            'estimated_total' => 100,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 0,
            'approved' => true,
            'execution_start_date' => '2026-10-15',
        ], $itemOverrides));
    }

    public function test_one_time_item_within_lead_window_is_upcoming(): void
    {
        Carbon::setTestNow('2026-10-13');

        $item = $this->createApprovedItem([], [
            'execution_start_date' => '2026-10-15',
        ]);

        $reminder = $this->resolver->resolveReminder($item, 3);

        $this->assertNotNull($reminder);
        $this->assertSame(ExecutionDueState::Upcoming, $reminder->state);
        $this->assertSame('2026-10-15', $reminder->dueDate->toDateString());
    }

    public function test_one_time_item_before_lead_window_returns_null(): void
    {
        Carbon::setTestNow('2026-10-01');

        $item = $this->createApprovedItem([], [
            'execution_start_date' => '2026-10-15',
        ]);

        $this->assertNull($this->resolver->resolveReminder($item, 3));
    }

    public function test_one_time_item_after_due_date_is_overdue(): void
    {
        Carbon::setTestNow('2026-10-18');

        $item = $this->createApprovedItem([], [
            'execution_start_date' => '2026-10-15',
        ]);

        $reminder = $this->resolver->resolveReminder($item, 3);

        $this->assertNotNull($reminder);
        $this->assertSame(ExecutionDueState::Overdue, $reminder->state);
    }

    public function test_delivered_one_time_item_has_no_due_date(): void
    {
        Carbon::setTestNow('2026-10-13');

        $item = $this->createApprovedItem([], [
            'execution_start_date' => '2026-10-15',
            'delivered' => true,
            'delivery_date' => '2026-10-14',
        ]);

        $this->assertNull($this->resolver->nextDueDate($item));
    }

    public function test_emergency_item_uses_needed_by_when_no_execution_start_date(): void
    {
        Carbon::setTestNow('2026-10-18');

        $item = $this->createApprovedItem([
            'request_type' => 'طارئة',
            'needed_by' => '2026-10-20',
        ], [
            'execution_type' => 'وقتية',
            'execution_start_date' => null,
        ]);

        $reminder = $this->resolver->resolveReminder($item, 3);

        $this->assertNotNull($reminder);
        $this->assertSame('2026-10-20', $reminder->dueDate->toDateString());
    }

    public function test_recurring_item_uses_execution_start_date_for_first_cycle(): void
    {
        Carbon::setTestNow('2026-10-10');

        $item = $this->createApprovedItem([], [
            'execution_type' => 'دورية',
            'recurrence_type' => 'دورية',
            'execution_start_date' => '2026-10-12',
            'recurrence_interval_days' => 30,
        ]);

        $reminder = $this->resolver->resolveReminder($item, 3);

        $this->assertNotNull($reminder);
        $this->assertSame('2026-10-12', $reminder->dueDate->toDateString());
    }

    public function test_recurring_item_uses_delivery_date_even_when_not_marked_delivered(): void
    {
        Carbon::setTestNow('2026-11-10');

        $item = $this->createApprovedItem([], [
            'execution_type' => 'دورية',
            'recurrence_type' => 'دورية',
            'execution_start_date' => '2026-10-01',
            'recurrence_interval_days' => 30,
            'delivered' => false,
            'delivery_date' => '2026-10-15',
        ]);

        $due = $this->resolver->nextDueDate($item);

        $this->assertNotNull($due);
        $this->assertSame('2026-11-14', $due->toDateString());
    }

    public function test_recurring_item_after_delivery_schedules_next_cycle(): void
    {
        Carbon::setTestNow('2026-11-20');

        $item = $this->createApprovedItem([], [
            'execution_type' => 'دورية',
            'recurrence_type' => 'دورية',
            'execution_start_date' => '2026-10-01',
            'recurrence_interval_days' => 30,
            'delivered' => true,
            'delivery_date' => '2026-10-15',
        ]);

        $due = $this->resolver->nextDueDate($item);

        $this->assertNotNull($due);
        $this->assertSame('2026-11-14', $due->toDateString());

        $reminder = $this->resolver->resolveReminder($item, 3);
        $this->assertNotNull($reminder);
        $this->assertSame(ExecutionDueState::Overdue, $reminder->state);
    }

    public function test_ineligible_request_status_excludes_item(): void
    {
        Carbon::setTestNow('2026-10-13');

        $item = $this->createApprovedItem([
            'status' => AidRequestStatus::Draft->value,
        ], [
            'execution_start_date' => '2026-10-15',
        ]);

        $this->assertNull($this->resolver->nextDueDate($item));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
