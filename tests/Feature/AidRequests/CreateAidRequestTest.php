<?php

namespace Tests\Feature\AidRequests;

use App\Enums\AidRequestStatus;
use App\Enums\AidType;
use App\Enums\FamilyStatus;
use App\Livewire\AidRequests\CreateAidRequest;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\AidRequestStatusHistory;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateAidRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_open_add_item_initializes_draft_panel(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')
            ->assertSet('selectedAidType', 'new')
            ->assertSet('draft.priority', 'عادية')
            ->assertSet('draft.need_title', '')
            ->assertSet('draft.unit_cost', null);
    }

    public function test_open_add_item_defaults_aid_type_to_first_available(): void
    {
        $family = Family::factory()->approved()->create();

        $component = Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id);

        $first = collect($component->get('availableAidTypes'))->first()['value'] ?? null;
        $this->assertNotNull($first);

        $component->call('openAddItem')
            ->assertSet('draft.aid_type', $first);
    }

    public function test_save_item_with_default_aid_type_succeeds(): void
    {
        $family = Family::factory()->approved()->create();

        // بدون تحديد نوع المساعدة يدوياً — يجب أن يمر باستخدام القيمة الافتراضية (أول نوع متاح)
        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')
            ->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'وصف احتياج مفصل كافٍ')
            ->set('draft.unit_cost', 250)
            ->call('saveItem')
            ->assertHasNoErrors()
            ->assertCount('items', 1);
    }

    public function test_save_item_adds_to_items_list(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'يحتاج علاج شهريراين')
            ->set('draft.unit_cost', 250)
            ->set('draft.priority', 'مرتفعة')
            ->call('saveItem')
            ->assertHasNoErrors()
            ->assertCount('items', 1)
            ->assertSet('items.0.aid_type', AidType::Medical->value)
            ->assertSet('items.0.title', 'عنوان مختصر للطلب')
            ->assertSet('items.0.priority', 'مرتفعة')
            ->assertSet('items.0.unit_cost', 250)
            ->assertSet('items.0.is_recurring', false)
            ->assertSet('selectedAidType', null);
    }

    public function test_save_item_requires_need_title(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)
            ->set('draft.need_title', '')
            ->set('draft.need_description', 'وصف طويل كافي هنا')
            ->set('draft.unit_cost', 100)
            ->call('saveItem')
            ->assertHasErrors(['draft.need_title']);
    }

    public function test_save_item_requires_need_description(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')->set('draft.aid_type', AidType::Financial->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', '')
            ->set('draft.unit_cost', 100)
            ->call('saveItem')
            ->assertHasErrors(['draft.need_description']);
    }

    public function test_save_item_requires_interval_and_start_for_recurring(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')->set('draft.aid_type', AidType::Financial->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'مساعدة دورية شهرة دون تفاصيل')
            ->set('draft.unit_cost', 100)
            ->set('draft.is_recurring', true)
            ->call('saveItem')
            ->assertHasErrors([
                'draft.recurrence_interval_days',
                'draft.execution_start_date',
            ]);
    }

    public function test_save_item_accepts_recurring_when_interval_and_start_provided(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')->set('draft.aid_type', AidType::Financial->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'مساعدة شهرية لمعالجة الإيجار')
            ->set('draft.unit_cost', 100)
            ->set('draft.is_recurring', true)
            ->set('draft.recurrence_interval_days', 30)
            ->set('draft.execution_start_date', now()->addDays(2)->toDateString())
            ->call('saveItem')
            ->assertHasNoErrors()
            ->assertCount('items', 1)
            ->assertSet('items.0.is_recurring', true)
            ->assertSet('items.0.recurrence_interval_days', 30)
            ->assertSet('items.0.unit_cost', 100);
    }

    public function test_remove_item_drops_from_list(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'وصف احتياج كامل هنا')
            ->set('draft.unit_cost', 150)
            ->call('saveItem')
            ->assertCount('items', 1)
            ->call('removeItem', 0)
            ->assertCount('items', 0);
    }

    public function test_save_draft_persists_request_and_items(): void
    {
        $family = Family::factory()->approved()->create();

        $component = Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'طلب مساعدة عام')
            ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'وصف احتياج طبي لطبيب باطني')
            ->set('draft.unit_cost', 250)
            ->set('draft.priority', 'متوسطة')
            ->call('saveItem')
            ->call('saveDraft');

        $aidRequest = AidRequest::first();
        $this->assertNotNull($aidRequest);
        $this->assertSame(AidRequestStatus::Draft->value, $aidRequest->status);
        $this->assertSame('طلب مساعدة عام', $aidRequest->title);
        $this->assertSame('وقتية', $aidRequest->request_type);
        $this->assertSame('متوسطة', $aidRequest->priority);
        $this->assertSame('250.00', (string) $aidRequest->items->first()->unit_cost);
        $this->assertSame('250.00', (string) $aidRequest->items->first()->estimated_total);

        $this->assertSame($component->get('aidRequestId'), $aidRequest->id);
        $this->assertCount(1, $aidRequest->items);
        $this->assertSame(AidType::Medical->value, $aidRequest->items->first()->aid_type);
    }

    public function test_save_draft_rejects_unapproved_families(): void
    {
        $family = Family::factory()->create(['status' => FamilyStatus::Draft->value]);

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'طلب على أسرة غير معتمدة')
            ->call('saveDraft')
            ->assertHasErrors(['family_id']);

        $this->assertSame(0, AidRequest::count());
    }

    public function test_save_draft_marks_recurring_request_type(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'مساعدة دورية')
            ->call('openAddItem')->set('draft.aid_type', AidType::Financial->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'مساعدة شهرية للأسرة المتعففة')
            ->set('draft.unit_cost', 300)
            ->set('draft.is_recurring', true)
            ->set('draft.recurrence_interval_days', 30)
            ->set('draft.execution_start_date', now()->addDay()->toDateString())
            ->call('saveItem')
            ->call('saveDraft');

        $aidRequest = AidRequest::first();
        $this->assertSame('دورية', $aidRequest->request_type);
        $this->assertSame(30, (int) $aidRequest->items->first()->recurrence_interval_days);
    }

    public function test_confirm_submit_requires_items(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'طلب بدون بنود')
            ->call('confirmSubmit')
            ->assertHasErrors(['items']);
    }

    public function test_confirm_submit_requires_family_and_title(): void
    {
        Livewire::test(CreateAidRequest::class)
            ->call('confirmSubmit')
            ->assertHasErrors(['family_id', 'title']);
    }

    public function test_confirm_submit_dispatches_open_modal_event(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'إرسال للمراجعة')
            ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'وصف احتياج طبي مفصل')
            ->set('draft.unit_cost', 175)
            ->call('saveItem')
            ->call('confirmSubmit')
            ->assertHasNoErrors()
            ->assertDispatched('open-modal', 'submit-confirm');
    }

    public function test_submit_transitions_to_submitted_and_records_history(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'إرسال للمراجعة')
            ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'وصف احتياج طبي مفصل')
            ->set('draft.unit_cost', 175)
            ->call('saveItem')
            ->set('acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        $aidRequest = AidRequest::first();
        $this->assertNotNull($aidRequest);
        $this->assertSame(AidRequestStatus::Submitted->value, $aidRequest->status);
        $this->assertNotNull($aidRequest->submitted_at);

        $history = AidRequestStatusHistory::where('aid_request_id', $aidRequest->id)->first();
        $this->assertNotNull($history);
        $this->assertSame(AidRequestStatus::Submitted->value, $history->to_status);
    }

    public function test_submit_blocked_without_acknowledgement(): void
    {
        $family = Family::factory()->approved()->create();

        Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'إرسال بدون إقرار')
            ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)->set('draft.need_title', 'عنوان مختصر للطلب')
            ->set('draft.need_description', 'وصف احتياج طبي مفصل')
            ->set('draft.unit_cost', 175)
            ->call('saveItem')
            ->set('acknowledged', false)
            ->call('submit')
            ->assertHasErrors(['acknowledged']);

        $this->assertEquals(0, AidRequest::count());
    }

    public function test_compute_priority_picks_highest(): void
    {
        $family = Family::factory()->approved()->create();

        $component = Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id)
            ->set('title', 'أولويات متعددة');

        foreach ([['عادية', 'وصف احتياج أول'], ['عاجلة جداً', 'وصف احتياج ثان']] as $i => [$priority, $desc]) {
            $component
                ->call('openAddItem')->set('draft.aid_type', AidType::Medical->value)->set('draft.need_title', 'عنوان مختصر للطلب')
                ->set('draft.need_description', $desc)
                ->set('draft.unit_cost', $i === 0 ? 100 : 200)
                ->set('draft.priority', $priority)
                ->call('saveItem');
        }

        $component->call('saveDraft');

        $aidRequest = AidRequest::first();
        $this->assertSame('عاجلة جداً', $aidRequest->priority);
    }

    public function test_edit_loads_draft_items_into_component_state(): void
    {
        $family = Family::factory()->approved()->create();
        $user = User::factory()->create();

        $aidRequest = AidRequest::factory()->for($family)->draft()->create([
            'title' => 'مسودة للتعديل',
            'created_by' => $user->id,
        ]);

        AidRequestItem::create([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => 'مساعدة طبية',
            'description' => 'وصف احتياج محفوظ',
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 125,
            'estimated_total' => 125,
            'recurrence_type' => 'وقتية',
            'priority' => 'مرتفعة',
            'sort_order' => 0,
            'aid_type' => AidType::Medical->value,
        ]);

        Livewire::test(CreateAidRequest::class, ['aidRequest' => $aidRequest->id])
            ->assertSet('aidRequestId', $aidRequest->id)
            ->assertSet('title', 'مسودة للتعديل')
            ->assertCount('items', 1)
            ->assertSet('items.0.aid_type', AidType::Medical->value)
            ->assertSet('items.0.need_title', 'مساعدة طبية')
            ->assertSet('items.0.priority', 'مرتفعة')
            ->assertSet('items.0.unit_cost', '125.00');
    }

    public function test_available_aid_types_filter_by_family_eligibility(): void
    {
        $family = Family::factory()->approved()->create();

        // ربط أهلية الأسرة بنوع واحد فقط (مالية)
        $family->aids()->create([
            'aid_type' => AidType::Financial->value,
            'eligible' => true,
        ]);
        $family->aids()->create([
            'aid_type' => AidType::Medical->value,
            'eligible' => false,
        ]);

        $component = Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id);

        $types = collect($component->get('availableAidTypes'))->pluck('value');

        $this->assertContains(AidType::Financial->value, $types->all());
        $this->assertNotContains(AidType::Medical->value, $types->all());
    }

    public function test_available_aid_types_show_all_when_no_eligibility_data(): void
    {
        $family = Family::factory()->approved()->create();

        $component = Livewire::test(CreateAidRequest::class)
            ->set('family_id', $family->id);

        $types = collect($component->get('availableAidTypes'))->pluck('value');

        // بدون بيانات أهلية، تعرض كل أنواع المساعدة
        $this->assertSame(count(AidType::cases()), $types->count());
    }

    public function test_non_editable_request_redirects_on_mount(): void
    {
        $family = Family::factory()->approved()->create();
        $aidRequest = AidRequest::factory()->for($family)->approved()->create();

        Livewire::test(CreateAidRequest::class, ['aidRequest' => $aidRequest->id])
            ->assertRedirect(route('aid-requests.show', $aidRequest));
    }
}
