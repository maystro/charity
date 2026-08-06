<?php

namespace Tests\Feature\Visits;

use App\Models\Family;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitOverdueTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_detects_overdue_visits(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id, 'approved_by' => $admin->id]);

        Visit::create([
            'family_id' => $family->id,
            'status' => 'scheduled',
            'visit_type' => 'other',
            'scheduled_at' => now()->subDays(5),
            'is_overdue' => false,
        ]);

        Visit::create([
            'family_id' => $family->id,
            'status' => 'scheduled',
            'visit_type' => 'other',
            'scheduled_at' => now()->addDays(5),
            'is_overdue' => false,
        ]);

        $this->artisan('app:detect-overdue-visits')
            ->assertSuccessful()
            ->expectsOutputToContain('1 زيارة متأخرة');

        $this->assertEquals(1, Visit::where('is_overdue', true)->count());
    }

    public function test_command_does_not_remark_already_overdue(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id, 'approved_by' => $admin->id]);

        Visit::create([
            'family_id' => $family->id,
            'status' => 'scheduled',
            'visit_type' => 'other',
            'scheduled_at' => now()->subDays(5),
            'is_overdue' => true,
        ]);

        $this->artisan('app:detect-overdue-visits')
            ->assertSuccessful()
            ->expectsOutputToContain('0 زيارة متأخرة');

        $this->assertEquals(1, Visit::where('is_overdue', true)->count());
    }

    public function test_command_skips_completed_visits(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id, 'approved_by' => $admin->id]);

        Visit::create([
            'family_id' => $family->id,
            'status' => 'completed',
            'visit_type' => 'other',
            'scheduled_at' => now()->subDays(5),
            'is_overdue' => false,
        ]);

        $this->artisan('app:detect-overdue-visits')
            ->assertSuccessful()
            ->expectsOutputToContain('0 زيارة متأخرة');
    }
}
