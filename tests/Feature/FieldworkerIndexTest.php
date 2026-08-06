<?php

namespace Tests\Feature;

use App\Livewire\Fieldworkers\Index;
use App\Livewire\Fieldworkers\Show;
use App\Models\Family;
use App\Models\Fieldworker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FieldworkerIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_index_page_renders_fieldworkers_table(): void
    {
        Fieldworker::factory()->create(['code' => 'FW-0001', 'name' => 'مندوب تجريبي', 'governorate' => 'دمشق']);

        Livewire::test(Index::class)
            ->assertOk()
            ->assertSee('مندوب تجريبي')
            ->assertSee('FW-0001')
            ->assertSee('دمشق');
    }

    public function test_search_filters_by_name(): void
    {
        Fieldworker::factory()->create(['name' => 'أحمد الباحث']);
        Fieldworker::factory()->create(['name' => 'سامي المندوب']);

        Livewire::test(Index::class)
            ->set('search', 'أحمد')
            ->assertSee('أحمد الباحث')
            ->assertDontSee('سامي المندوب');
    }

    public function test_filter_by_governorate(): void
    {
        Fieldworker::factory()->create(['name' => 'من دمشق', 'governorate' => 'دمشق']);
        Fieldworker::factory()->create(['name' => 'من حلب', 'governorate' => 'حلب']);

        Livewire::test(Index::class)
            ->set('governorate', 'دمشق')
            ->assertSee('من دمشق')
            ->assertDontSee('من حلب');
    }

    public function test_can_create_fieldworker_via_modal(): void
    {
        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('form.code', 'FW-9999')
            ->set('form.name', 'مندوب جديد')
            ->set('form.governorate', 'حمص')
            ->set('form.status', 'active')
            ->set('form.username', 'fieldworker_new')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password')
            ->call('saveFieldworker')
            ->assertHasNoErrors(['form.code', 'form.name', 'form.status', 'form.username', 'form.password']);

        $this->assertDatabaseHas('fieldworkers', [
            'code' => 'FW-9999',
            'name' => 'مندوب جديد',
            'governorate' => 'حمص',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'fieldworker_new',
            'role' => User::ROLE_FIELDWORKER,
        ]);
    }

    public function test_create_validates_unique_code(): void
    {
        Fieldworker::factory()->create(['code' => 'FW-0007']);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('form.code', 'FW-0007')
            ->set('form.name', 'تكرار')
            ->set('form.status', 'active')
            ->set('form.username', 'dup_code_user')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password')
            ->call('saveFieldworker')
            ->assertHasErrors(['form.code']);
    }

    public function test_show_page_displays_fieldworker_and_families(): void
    {
        $fieldworker = Fieldworker::factory()->create([
            'name' => 'الباحث سامي',
            'code' => 'FW-0042',
            'governorate' => 'حلب',
        ]);

        Family::factory()->approved()->create([
            'case_name' => 'أسرة أحمد',
            'fieldworker_id' => $fieldworker->id,
        ]);
        Family::factory()->approved()->create([
            'case_name' => 'أسرة خالد',
            'fieldworker_id' => $fieldworker->id,
        ]);

        Livewire::test(Show::class, ['fieldworker' => $fieldworker])
            ->assertOk()
            ->assertSee('الباحث سامي')
            ->assertSee('FW-0042')
            ->assertSee('أسرة أحمد')
            ->assertSee('أسرة خالد');
    }

    public function test_show_page_stats_count_families_by_status(): void
    {
        $fieldworker = Fieldworker::factory()->create();
        Family::factory()->approved()->count(3)->create(['fieldworker_id' => $fieldworker->id]);
        Family::factory()->underReview()->count(2)->create(['fieldworker_id' => $fieldworker->id]);
        Family::factory()->draft()->count(1)->create(['fieldworker_id' => $fieldworker->id]);

        Livewire::test(Show::class, ['fieldworker' => $fieldworker])
            ->assertViewHas('stats.total', 6)
            ->assertViewHas('stats.approved', 3)
            ->assertViewHas('stats.underReview', 2)
            ->assertViewHas('stats.drafts', 1);
    }

    public function test_index_route_loads_via_http(): void
    {
        Fieldworker::factory()->create(['name' => 'مسار HTTP']);

        $this->get(route('fieldworkers.index'))
            ->assertOk()
            ->assertSee('مسار HTTP')
            ->assertSee('المندوبون والباحثون');
    }

    public function test_show_route_loads_via_http(): void
    {
        $fieldworker = Fieldworker::factory()->create(['name' => 'مسار تفاصيل']);

        $this->get(route('fieldworkers.show', $fieldworker))
            ->assertOk()
            ->assertSee('مسار تفاصيل');
    }
}
