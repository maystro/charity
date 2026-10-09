<?php

namespace Tests\Feature\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Models\Family;
use App\Models\User;
use App\Services\AidRequests\AidRequestStatusCounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AidRequestStatusCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_tab_counts_group_approved_statuses(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);

        AidRequest::factory()->for($family)->create([
            'created_by' => $user->id,
            'status' => AidRequestStatus::Approved->value,
        ]);
        AidRequest::factory()->for($family)->create([
            'created_by' => $user->id,
            'status' => AidRequestStatus::PartiallyApproved->value,
        ]);
        AidRequest::factory()->for($family)->create([
            'created_by' => $user->id,
            'status' => AidRequestStatus::InExecution->value,
        ]);

        $counts = app(AidRequestStatusCounts::class)->deliveryTabCounts();

        $this->assertSame(2, $counts['ready']);
        $this->assertSame(1, $counts['in_execution']);
    }

    public function test_review_tab_counts_separate_under_review_and_approved(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);

        AidRequest::factory()->for($family)->create([
            'created_by' => $user->id,
            'status' => AidRequestStatus::UnderReview->value,
        ]);
        AidRequest::factory()->for($family)->create([
            'created_by' => $user->id,
            'status' => AidRequestStatus::Approved->value,
        ]);

        $counts = app(AidRequestStatusCounts::class)->reviewTabCounts();

        $this->assertSame(1, $counts['under_review']);
        $this->assertSame(1, $counts['approved']);
    }
}
