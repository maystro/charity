<?php

namespace Tests\Feature\ExecutionSchedule;

use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Family;
use App\Models\User;
use App\Services\ExecutionSchedule\ExecutionNextDueDateSync;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionNextDueDateSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_persists_next_due_date_for_one_time_item(): void
    {
        Carbon::setTestNow('2026-10-13');

        $user = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->approved()->create([
            'created_by' => $user->id,
            'submitted_by' => $user->id,
        ]);

        $item = AidRequestItem::create([
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
        ]);

        app(ExecutionNextDueDateSync::class)->sync($item->fresh());

        $this->assertSame('2026-10-15', $item->fresh()->next_due_date?->toDateString());

        Carbon::setTestNow();
    }
}
