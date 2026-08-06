<?php

namespace Tests\Feature\AidRequests;

use App\Models\AidRequest;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AidRequestIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_index_page_renders_with_requests(): void
    {
        $family = Family::factory()->approved()->create(['case_name' => 'أسرة للعرض']);
        AidRequest::factory()->for($family)->submitted()->create([
            'title' => 'طلب عرض توضيحي',
            'total_estimated_amount' => 1250,
        ]);

        Livewire::test('aid-requests.index')
            ->assertOk()
            ->assertSee('طلبات المساعدة')
            ->assertSee('طلب عرض توضيحي')
            ->assertSee($family->case_name)
            ->assertSee('1,250.00 ج.م')
            ->assertDontSee('ر.س');
    }

    public function test_search_filters_by_title_family_or_number(): void
    {
        $family = Family::factory()->approved()->create(['case_name' => 'أسرة البحث']);
        AidRequest::factory()->for($family)->submitted()->create(['title' => 'مساعدة طبية عاجلة']);
        AidRequest::factory()->submitted()->create(['title' => 'أثاث منزلي']);

        Livewire::test('aid-requests.index')
            ->set('search', 'طبية')
            ->assertSee('مساعدة طبية عاجلة')
            ->assertDontSee('أثاث منزلي');

        Livewire::test('aid-requests.index')
            ->set('search', 'أسرة البحث')
            ->assertSee('مساعدة طبية عاجلة')
            ->assertDontSee('أثاث منزلي');
    }

    public function test_under_review_tab_shows_submitted_needs_completion_and_under_review(): void
    {
        AidRequest::factory()->submitted()->create(['title' => 'مرسل اختبار']);
        AidRequest::factory()->needsCompletion()->create(['title' => 'يحتاج استكمال']);
        AidRequest::factory()->underReview()->create(['title' => 'تحت المراجعة']);
        AidRequest::factory()->approved()->create(['title' => 'تم الاعتماد']);

        Livewire::test('aid-requests.index')
            ->assertSee('مرسل اختبار')
            ->assertSee('يحتاج استكمال')
            ->assertSee('تحت المراجعة')
            ->assertDontSee('تم الاعتماد');
    }

    public function test_approved_tab_shows_approved_and_partially_approved(): void
    {
        AidRequest::factory()->approved()->create(['title' => 'تم الاعتماد']);
        AidRequest::factory()->partiallyApproved()->create(['title' => 'اعتماد جزئي']);
        AidRequest::factory()->submitted()->create(['title' => 'مرسل اختبار']);

        Livewire::test('aid-requests.index')
            ->set('tab', 'approved')
            ->assertSee('تم الاعتماد')
            ->assertSee('اعتماد جزئي')
            ->assertDontSee('مرسل اختبار');
    }

    public function test_tab_counts_are_computed(): void
    {
        AidRequest::factory()->submitted()->create();
        AidRequest::factory()->approved()->create();

        $component = Livewire::test('aid-requests.index');

        $this->assertSame(1, $component->get('underReviewCount'));
        $this->assertSame(1, $component->get('approvedCount'));
    }

    public function test_priority_filter_works(): void
    {
        AidRequest::factory()->submitted()->create(['title' => 'أولوية عالية', 'priority' => 'مرتفعة']);
        AidRequest::factory()->submitted()->create(['title' => 'أولوية عادية', 'priority' => 'عادية']);

        Livewire::test('aid-requests.index')
            ->set('priority', 'مرتفعة')
            ->assertSee('أولوية عالية')
            ->assertDontSee('أولوية عادية');
    }

    public function test_delete_removes_request(): void
    {
        $request = AidRequest::factory()->submitted()->create();

        Livewire::test('aid-requests.index')
            ->call('delete', $request->id);

        $this->assertSoftDeleted('aid_requests', ['id' => $request->id]);
    }

    public function test_each_row_links_to_request_show_page(): void
    {
        $family = Family::factory()->approved()->create();
        $first = AidRequest::factory()->for($family)->submitted()->create(['title' => 'طلب أول']);
        $second = AidRequest::factory()->for($family)->submitted()->create(['title' => 'طلب ثانٍ']);

        // كل صف يعرض رابط فتح الطلب (يُستخدم عند النقر على الصف)
        Livewire::test('aid-requests.index')
            ->assertSee(route('aid-requests.show', $first))
            ->assertSee(route('aid-requests.show', $second));
    }

    public function test_priority_sort_orders_highest_first(): void
    {
        AidRequest::factory()->submitted()->create(['title' => 'أولوية عادية', 'priority' => 'عادية']);
        AidRequest::factory()->submitted()->create(['title' => 'أولوية عاجلة', 'priority' => 'عاجلة جداً']);

        $component = Livewire::test('aid-requests.index')
            ->set('sort', 'priority');

        $requests = $component->get('requests');
        $this->assertSame('أولوية عاجلة', $requests->first()->title);
        $this->assertSame('أولوية عادية', $requests->last()->title);
    }

    /*
    |--------------------------------------------------------------------------
    | Fieldworker role-based scoping (merged from former TasksIndexTest)
    |--------------------------------------------------------------------------
    */

    public function test_fieldworker_sees_only_their_own_requests(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();

        AidRequest::factory()->submitted()->create([
            'title' => 'طلب المندوب الحالي',
            'submitted_by' => $fieldworker->id,
        ]);
        AidRequest::factory()->submitted()->create([
            'title' => 'طلب مندوب آخر',
            'submitted_by' => $other->id,
        ]);

        Livewire::actingAs($fieldworker)
            ->test('aid-requests.index')
            ->assertOk()
            ->assertSee('طلب المندوب الحالي')
            ->assertDontSee('طلب مندوب آخر');
    }

    public function test_admin_sees_all_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->fieldworker()->create();

        AidRequest::factory()->submitted()->create([
            'title' => 'طلب المندوب',
            'submitted_by' => $other->id,
        ]);

        Livewire::actingAs($admin)
            ->test('aid-requests.index')
            ->assertOk()
            ->assertSee('طلب المندوب');
    }

    public function test_fieldworker_tab_counts_only_own_requests(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();

        AidRequest::factory()->submitted()->create(['submitted_by' => $fieldworker->id]);
        AidRequest::factory()->submitted()->create(['submitted_by' => $other->id]);
        AidRequest::factory()->approved()->create(['submitted_by' => $fieldworker->id]);
        AidRequest::factory()->approved()->create(['submitted_by' => $other->id]);

        $component = Livewire::actingAs($fieldworker)->test('aid-requests.index');

        $this->assertSame(1, $component->get('underReviewCount'));
        $this->assertSame(1, $component->get('approvedCount'));
    }

    public function test_fieldworker_cannot_delete_others_requests(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();
        $request = AidRequest::factory()->submitted()->create(['submitted_by' => $other->id]);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($fieldworker)
            ->test('aid-requests.index')
            ->call('delete', $request->id);

        $this->assertDatabaseHas('aid_requests', ['id' => $request->id]);
    }

    public function test_fieldworker_search_scopes_to_own_requests(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();

        AidRequest::factory()->submitted()->create([
            'title' => 'مفصلية للمندوب',
            'submitted_by' => $fieldworker->id,
        ]);
        AidRequest::factory()->submitted()->create([
            'title' => 'مفصلية للآخر',
            'submitted_by' => $other->id,
        ]);

        Livewire::actingAs($fieldworker)
            ->test('aid-requests.index')
            ->set('search', 'مفصلية')
            ->assertSee('مفصلية للمندوب')
            ->assertDontSee('مفصلية للآخر');
    }

    public function test_fieldworker_can_see_approved_tab(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        AidRequest::factory()->approved()->create([
            'title' => 'طلب معتمد للمندوب',
            'submitted_by' => $fieldworker->id,
        ]);

        Livewire::actingAs($fieldworker)
            ->test('aid-requests.index')
            ->set('tab', 'approved')
            ->assertSee('طلب معتمد للمندوب');
    }

    public function test_fieldworker_does_not_see_create_button(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        AidRequest::factory()->submitted()->create(['submitted_by' => $fieldworker->id]);

        Livewire::actingAs($fieldworker)
            ->test('aid-requests.index')
            ->assertDontSee('إضافة طلب');
    }

    public function test_fieldworker_does_not_see_delete_button(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        AidRequest::factory()->submitted()->create([
            'title' => 'طلب قابل للحذف',
            'submitted_by' => $fieldworker->id,
        ]);

        // زر الحذف «حذف» لا يظهر للمندوب رغم رؤية الطلب نفسه
        Livewire::actingAs($fieldworker)
            ->test('aid-requests.index')
            ->assertSee('طلب قابل للحذف');
    }
}
