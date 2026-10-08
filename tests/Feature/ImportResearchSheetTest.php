<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\SocialResearch;
use App\Models\User;
use App\Services\Families\ResearchSheetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportResearchSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_families_and_researches(): void
    {
        // create admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'username' => 'admin',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        // Call importer service directly to avoid console process isolation in tests.
        $importer = app(ResearchSheetImporter::class);
        $result = $importer->import();
        $this->assertGreaterThanOrEqual(1, $result['imported']);

        // research number 1 should exist
        $this->assertDatabaseHas('social_researches', ['research_number' => '1']);

        // family with wife name from sheet
        $this->assertDatabaseHas('families', ['case_name' => 'ايه رجب عبدالمجيد سلامه']);

        // rejected research 2 -> family status rejected
        $research2 = SocialResearch::where('research_number', '2')->first();
        $this->assertNotNull($research2);
        $this->assertDatabaseHas('families', ['id' => $research2->family_id, 'status' => 'rejected']);
    }
}
