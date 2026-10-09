<?php

namespace Tests\Feature\Alerts;

use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Alert;
use App\Models\Family;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Alerts\AidExecutionDueAlertService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AidExecutionDueAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SystemSetting::set('execution_reminder_lead_days', 3, 'aid_requests', 'lead', 'integer');
    }

    private function createDueItem(): AidRequestItem
    {
        Carbon::setTestNow('2026-10-13');

        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->approved()->create([
            'created_by' => $user->id,
            'submitted_by' => $user->id,
        ]);

        return AidRequestItem::create([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => 'دواء',
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 100,
            'estimated_total' => 100,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 0,
            'approved' => true,
            'execution_start_date' => '2026-10-15',
        ]);
    }

    public function test_creates_due_alert_for_item_in_window(): void
    {
        $item = $this->createDueItem();

        $result = app(AidExecutionDueAlertService::class)->generate();

        $this->assertSame(1, $result['created']);
        $alert = Alert::first();
        $this->assertNotNull($alert);
        $this->assertSame(Alert::TYPE_AID_EXECUTION_DUE, $alert->type);
        $this->assertSame(AidRequestItem::class, $alert->alertable_type);
        $this->assertSame($item->id, $alert->alertable_id);
    }

    public function test_overdue_alert_after_due_date(): void
    {
        $item = $this->createDueItem();
        Carbon::setTestNow('2026-10-18');

        app(AidExecutionDueAlertService::class)->generate();

        $this->assertDatabaseHas('alerts', [
            'alertable_id' => $item->id,
            'type' => Alert::TYPE_AID_EXECUTION_OVERDUE,
        ]);
    }

    public function test_resolves_alert_when_item_out_of_scope(): void
    {
        $item = $this->createDueItem();
        app(AidExecutionDueAlertService::class)->generate();

        Carbon::setTestNow('2026-09-01');
        $result = app(AidExecutionDueAlertService::class)->generate();

        $this->assertSame(1, $result['resolved']);
        $this->assertSame(Alert::STATUS_RESOLVED, Alert::first()->fresh()->status);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
