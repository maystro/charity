<?php

namespace Tests\Feature\AidRequests;

use App\Enums\AidRequestStatus;
use App\Livewire\AidRequests\ShowAidRequest;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Alert;
use App\Models\Family;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ShowAidRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_items_and_review_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->submitted()->create(['created_by' => $admin->id]);

        $approvedItem = $this->createItem($request, 'معتمد سابقاً');
        $approvedItem->update(['approved' => true, 'reviewed_at' => now(), 'reviewer_id' => $admin->id]);
        $pendingItem = $this->createItem($request, 'قيد المراجعة');

        $this->actingAs($admin);

        $component = Livewire::test(ShowAidRequest::class, ['aidRequest' => $request]);

        $component->assertSee($approvedItem->title)
            ->assertSee($pendingItem->title)
            ->assertSee('إجراءات الاعتماد')
            ->assertSet('canReviewItems', true)
            ->assertSee('100.00 ج.م')
            ->assertDontSee('ر.س');
    }

    public function test_status_badge_shows_arabic_label_for_partially_approved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->partiallyApproved()->create(['created_by' => $admin->id]);

        $this->actingAs($admin);

        Livewire::test(ShowAidRequest::class, ['aidRequest' => $request])
            ->assertSee('معتمدة جزئياً')
            ->assertDontSee('partially_approved');
    }

    public function test_status_badge_shows_arabic_label_for_submitted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->submitted()->create(['created_by' => $admin->id]);

        $this->actingAs($admin);

        Livewire::test(ShowAidRequest::class, ['aidRequest' => $request])
            ->assertSee('مقدمة')
            ->assertDontSee('submitted');
    }

    public function test_fieldworker_sees_only_approved_items(): void
    {
        $fieldworker = User::factory()->create(['role' => 'fieldworker']);
        $admin = User::factory()->create(['role' => 'admin']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->approved()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
        ]);

        $approvedItem = $this->createItem($request, 'مساعدة معتمدة');
        $approvedItem->update(['approved' => true, 'reviewed_at' => now(), 'reviewer_id' => $admin->id]);
        $this->createItem($request, 'مساعدة غير معتمدة');

        $this->actingAs($fieldworker);

        Livewire::test(ShowAidRequest::class, ['aidRequest' => $request])
            ->assertSee('البنود المعتمدة')
            ->assertSee('مساعدة معتمدة')
            ->assertDontSee('مساعدة غير معتمدة')
            ->assertDontSee('إجراءات الاعتماد')
            ->assertSet('canReviewItems', false)
            ->assertSee('100.00 ج.م')
            ->assertDontSee('ر.س');
    }

    public function test_admin_can_approve_items_via_save_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fieldworker = User::factory()->create(['role' => 'fieldworker']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->submitted()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
        ]);

        $itemA = $this->createItem($request, 'مساعدة');
        $this->createItem($request, 'مساعدة 2');

        $this->actingAs($admin);

        Livewire::test(ShowAidRequest::class, ['aidRequest' => $request])
            ->set('approvedItemIds', [$itemA->id])
            ->set('reviewNotes', 'تم اعتماد البند الأول فقط.')
            ->call('saveApproval')
            ->assertDispatched('aid-request-reviewed');

        $request->refresh();

        $this->assertSame(AidRequestStatus::PartiallyApproved->value, $request->status);
        $this->assertTrue($itemA->fresh()->approved);
        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_AID_REQUEST_PARTIALLY_APPROVED,
            'notified_user_id' => $fieldworker->id,
        ]);
    }

    public function test_admin_can_reject_request_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fieldworker = User::factory()->create(['role' => 'fieldworker']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->submitted()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
        ]);

        $this->createItem($request, 'بند');

        $this->actingAs($admin);

        Livewire::test(ShowAidRequest::class, ['aidRequest' => $request])
            ->set('reviewNotes', 'معلومات ناقصة')
            ->call('rejectRequest')
            ->assertDispatched('aid-request-reviewed');

        $request->refresh();

        $this->assertSame(AidRequestStatus::Rejected->value, $request->status);
        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_AID_REQUEST_REJECTED,
            'notified_user_id' => $fieldworker->id,
        ]);
    }

    public function test_fieldworker_cannot_review_request(): void
    {
        $fieldworker = User::factory()->create(['role' => 'fieldworker']);
        $admin = User::factory()->create(['role' => 'admin']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->submitted()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
        ]);

        $item = $this->createItem($request, 'بند');

        $this->actingAs($fieldworker);

        Livewire::test(ShowAidRequest::class, ['aidRequest' => $request])
            ->set('approvedItemIds', [$item->id])
            ->call('saveApproval')
            ->assertStatus(403);
    }

    public function test_show_page_includes_print_button_and_print_header(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('organization/logo.png', 'fake-logo');

        SystemSetting::set('organization_name', 'جمعية التضامن الأجتماعي - بني سويف', 'organization', 'اسم المؤسسة', 'string');
        SystemSetting::set('organization_tagline', 'جمعية عهد الخير للتنمية والخدمات', 'organization', 'الاسم التعريفي', 'string');
        SystemSetting::set('organization_logo_path', 'organization/logo.png', 'organization', 'شعار المؤسسة', 'string');

        $admin = User::factory()->create(['role' => 'admin']);
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $request = AidRequest::factory()->for($family)->approved()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('aid-requests.show', $request))
            ->assertOk()
            ->assertSee('onclick="window.print()"', false)
            ->assertSee('print-header')
            ->assertSee('جمعية التضامن الأجتماعي - بني سويف')
            ->assertSee('جمعية عهد الخير للتنمية والخدمات')
            ->assertDontSee('ر.س');
    }

    private function createItem(AidRequest $aidRequest, string $title): AidRequestItem
    {
        return AidRequestItem::create([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => $title,
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 100,
            'estimated_total' => 100,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 0,
        ]);
    }
}
