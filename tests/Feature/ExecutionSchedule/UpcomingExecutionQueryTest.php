<?php

namespace Tests\Feature\ExecutionSchedule;

use App\Enums\ExecutionDueState;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Family;
use App\Models\User;
use App\Services\ExecutionSchedule\UpcomingExecutionQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingExecutionQueryTest extends TestCase
{
    use RefreshDatabase;

    private function createItemForUser(User $user, array $requestOverrides = [], array $itemOverrides = []): AidRequestItem
    {
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);

        $aidRequest = AidRequest::factory()->for($family)->approved()->create(array_merge([
            'created_by' => $user->id,
            'submitted_by' => $user->id,
        ], $requestOverrides));

        return AidRequestItem::create(array_merge([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => 'بند',
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 50,
            'estimated_total' => 50,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 0,
            'approved' => true,
            'execution_start_date' => '2026-10-15',
        ], $itemOverrides));
    }

    public function test_items_due_for_reminder_returns_matching_items(): void
    {
        Carbon::setTestNow('2026-10-13');

        $admin = User::factory()->admin()->create();
        $this->createItemForUser($admin);
        $this->createItemForUser($admin, [], [
            'execution_start_date' => '2026-11-01',
        ]);

        $query = new UpcomingExecutionQuery;
        $items = $query->itemsDueForReminder(3, $admin);

        $this->assertCount(1, $items);
        $this->assertSame(ExecutionDueState::Upcoming, $items->first()->state);
    }

    public function test_requests_grouped_by_aid_request_with_earliest_due(): void
    {
        Carbon::setTestNow('2026-10-13');

        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $aidRequest = AidRequest::factory()->for($family)->approved()->create([
            'created_by' => $admin->id,
            'submitted_by' => $admin->id,
        ]);

        AidRequestItem::create([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => 'بند 1',
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 50,
            'estimated_total' => 50,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 0,
            'approved' => true,
            'execution_start_date' => '2026-10-16',
        ]);

        AidRequestItem::create([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => 'بند 2',
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 50,
            'estimated_total' => 50,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 1,
            'approved' => true,
            'execution_start_date' => '2026-10-14',
        ]);

        $query = new UpcomingExecutionQuery;
        $requests = $query->requestsDueForReminder(3, $admin);

        $this->assertCount(1, $requests);
        $this->assertSame('2026-10-14', $requests->first()['due_date']->toDateString());
        $this->assertCount(2, $requests->first()['items']);
    }

    public function test_fieldworker_only_sees_own_submitted_requests(): void
    {
        Carbon::setTestNow('2026-10-13');

        $fieldworker = User::factory()->create(['role' => User::ROLE_FIELDWORKER]);
        $other = User::factory()->create(['role' => User::ROLE_FIELDWORKER]);

        $this->createItemForUser($fieldworker);
        $this->createItemForUser($other);

        $query = new UpcomingExecutionQuery;

        $this->assertCount(1, $query->itemsDueForReminder(3, $fieldworker));
        $this->assertCount(2, $query->itemsDueForReminder(3, User::factory()->admin()->create()));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
