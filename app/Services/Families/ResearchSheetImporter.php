<?php

namespace App\Services\Families;

use App\Enums\AidType;
use App\Enums\FamilyStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Family;
use App\Models\FamilyAid;
use App\Models\FamilyBurden;
use App\Models\FamilyHousing;
use App\Models\FamilyIncomeSource;
use App\Models\FamilyMember;
use App\Models\FamilyStatusHistory;
use App\Models\SocialResearch;
use App\Models\User;
use App\Models\Visit;
use App\Services\Visits\VisitNumberGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ResearchSheetImporter
{
    protected ResearchSheetRecords $records;

    public function __construct(?ResearchSheetRecords $records = null)
    {
        $this->records = $records ?? new ResearchSheetRecords;
    }

    /**
     * Import rows. Returns summary ['imported'=>int,'skipped'=>int]
     *
     * @return array{imported:int,skipped:int}
     */
    public function import(): array
    {
        $rows = $this->records->all();
        $imported = 0;
        $skipped = 0;

        $admin = User::admins()->first();
        if (! $admin) {
            // Create a throwaway admin for import context to ensure import can run
            $admin = User::create([
                'name' => 'importer-admin',
                'email' => 'importer@localhost',
                'username' => 'importer-admin',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]);
        }

        $pendingVisits = [];
        $pendingVisits = [];
        foreach ($rows as $row) {
            // idempotency: skip if research_number exists
            if (SocialResearch::where('research_number', (string) $row['code'])->exists()) {
                $skipped++;

                continue;
            }

            DB::transaction(function () use ($row, $admin, &$imported) {
                // Create family
                $familyData = [
                    'case_type' => $this->inferCaseType($row),
                    'case_name' => $row['wife_name'] ?: ($row['husband_name'] ?? '—'),
                    'community' => $row['community'] ?: null,
                    'detailed_address' => $row['address'] ?: null,
                    'phone' => $this->normalizePhone($row['phone'] ?? ''),
                    'family_type' => 'بسيطة',
                    'members_count' => 0,
                    'status' => FamilyStatus::Draft->value,
                ];

                // compute income from free text if possible
                $incomeSources = $this->extractIncomeSources($row);
                $totalIncome = array_sum(array_column($incomeSources, 'amount'));
                $familyData['total_income'] = $totalIncome;
                $familyData['average_income_per_person'] = 0;

                $familyData['created_by'] = $admin->id;
                $familyData['updated_by'] = $admin->id;
                $familyData['case_number'] = app(FamilyNumberGenerator::class)->generate();

                $family = Family::create($familyData);

                // Members: wife, husband, children
                $members = $this->buildMembers($row);
                foreach ($members as $i => $m) {
                    FamilyMember::create([
                        'family_id' => $family->id,
                        'name' => $m['name'],
                        'national_id' => $m['national_id'] ?: null,
                        'relationship' => $m['relationship'],
                        'occupation' => $m['occupation'] ?? null,
                        'income' => (float) ($m['income'] ?? 0),
                        'sort_order' => $i,
                    ]);
                }

                // update members_count and avg income
                $family->members_count = count($members);
                $family->average_income_per_person = $family->members_count > 0 ? $totalIncome / $family->members_count : 0;
                $family->save();

                // Income sources
                foreach ($incomeSources as $type => $src) {
                    if (! empty($src['amount']) || ! empty($src['is_active'])) {
                        FamilyIncomeSource::create([
                            'family_id' => $family->id,
                            'source_type' => $type,
                            'is_active' => $src['is_active'] ?? true,
                            'amount' => (float) ($src['amount'] ?? 0),
                            'notes' => $src['notes'] ?? null,
                        ]);
                    }
                }

                // Burdens
                $burdens = $this->extractBurdens($row);
                foreach ($burdens as $b) {
                    FamilyBurden::create([
                        'family_id' => $family->id,
                        'burden_type' => $b['type'],
                        'amount' => (float) ($b['amount'] ?? 0),
                        'notes' => $b['notes'] ?? null,
                    ]);
                }

                // Housing
                FamilyHousing::updateOrCreate(
                    ['family_id' => $family->id],
                    [
                        'housing_type' => $this->inferHousingType($row),
                        'residence_status' => $this->inferResidenceStatus($row),
                    ]
                );

                // Aids
                $aids = $this->inferAids($row);
                foreach ($aids as $aidType => $reason) {
                    FamilyAid::create([
                        'family_id' => $family->id,
                        'aid_type' => $aidType,
                        'eligible' => true,
                        'reasons' => $reason,
                    ]);
                }

                // SocialResearch
                $research = SocialResearch::create([
                    'family_id' => $family->id,
                    'research_number' => (string) $row['code'],
                    'research_type' => 'initial',
                    'conducted_at' => $this->parseDate($row['submitted_at']),
                    'approved_at' => $this->parseDate($row['discussion_date']),
                    'eligibility_degree' => null,
                    'average_income' => $family->average_income_per_person,
                    'net_income' => $family->total_income,
                    'recommendation' => $row['details'] ?: null,
                    'committee_decision' => $row['committee_decision'] ?: null,
                    'status' => $this->researchStatus($row),
                    'created_by' => $admin->id,
                    'approved_by' => null,
                ]);

                // If committee rejected -> mark family rejected and history
                if (mb_stripos($row['committee_decision'] ?? '', 'مرفوض') !== false) {
                    $family->update([
                        'status' => FamilyStatus::Rejected->value,
                        'rejected_by' => $admin->id,
                        'rejected_at' => now(),
                        'rejection_reason' => $row['committee_decision'].' - '.($row['details'] ?? ''),
                    ]);
                    // create status history
                    FamilyStatusHistory::create([
                        'family_id' => $family->id,
                        'from_status' => $family->getOriginal('status'),
                        'to_status' => FamilyStatus::Rejected->value,
                        'changed_by' => $admin->id,
                        'notes' => $row['committee_decision'] ?? null,
                        'created_at' => now(),
                    ]);
                } else {
                    // if discussed -> set family under_review and submitted_at
                    if (mb_stripos($row['was_discussed'] ?? '', 'نعم') !== false) {
                        $family->update([
                            'status' => FamilyStatus::UnderReview->value,
                            'submitted_by' => $admin->id,
                            'submitted_at' => $this->parseDate($row['submitted_at']) ?? now(),
                        ]);
                    }
                }

                // Visit: queue creation after transaction to avoid FK issues in some SQLite setups
                if (! empty(trim((string) ($row['visit_result'] ?? '')))) {
                    $pendingVisits[] = [
                        'family_id' => $family->id,
                        'research_id' => $research->id,
                        'researcher_id' => $admin->id,
                        'scheduled_at' => $this->parseDate($row['discussion_date']) ?? now()->toDateTimeString(),
                        'outcome_summary' => $row['visit_result'],
                    ];
                }

                $imported++;
            });
        }

        // create pending visits outside of transaction
        foreach ($pendingVisits as $pv) {
            if (isset($pv['research_id']) && Visit::where('research_id', $pv['research_id'])->exists()) {
                continue;
            }

            Visit::create([
                'visit_number' => app(VisitNumberGenerator::class)->generate(),
                'visit_type' => VisitType::InitialAssessment->value,
                'priority' => 'normal',
                'purpose' => 'نتيجة زيارة ميدانية مستوردة',
                'family_id' => $pv['family_id'],
                'research_id' => $pv['research_id'],
                'researcher_id' => $pv['researcher_id'],
                'scheduled_at' => $pv['scheduled_at'],
                'started_at' => now(),
                'completed_at' => now(),
                'outcome_summary' => $pv['outcome_summary'],
                'status' => VisitStatus::Completed->value,
                'created_by' => $pv['researcher_id'],
                'completed_by' => $pv['researcher_id'],
            ]);
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    protected function normalizePhone(string $p): ?string
    {
        $p = trim($p);
        if ($p === '') {
            return null;
        }
        // ensure leading zero for Egyptian-like numbers
        if (! str_starts_with($p, '0')) {
            $p = '0'.$p;
        }

        return $p;
    }

    /**
     * Build members array from row.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function buildMembers(array $row): array
    {
        $members = [];
        // husband
        if (! empty(trim((string) ($row['husband_name'] ?? '')))) {
            $members[] = [
                'name' => $row['husband_name'],
                'national_id' => $row['husband_national_id'] ?: null,
                'relationship' => 'رب الأسرة',
            ];
        }
        // wife
        if (! empty(trim((string) ($row['wife_name'] ?? '')))) {
            $members[] = [
                'name' => $row['wife_name'],
                'national_id' => $row['wife_national_id'] ?: null,
                'relationship' => 'زوجة',
            ];
        }

        // children (I..Q names with J..R ids)
        for ($i = 1; $i <= 5; $i++) {
            $nameKey = 'child'.$i.'_name';
            $idKey = 'child'.$i.'_national_id';
            if (! empty(trim((string) ($row[$nameKey] ?? '')))) {
                $members[] = [
                    'name' => $row[$nameKey],
                    'national_id' => $row[$idKey] ?: null,
                    'relationship' => 'ابن',
                ];
            }
        }

        return $members;
    }

    protected function extractIncomeSources(array $row): array
    {
        $out = [];
        $details = $row['details'] ?? '';
        // try to find numeric amounts in details (simple)
        preg_match_all('/(\\d{2,7})/', $details, $m);
        $nums = array_map('intval', $m[1] ?? []);
        $sum = array_sum($nums);
        if ($sum > 0) {
            $out['irregular_labor'] = ['is_active' => true, 'amount' => $sum, 'notes' => $details];
        }

        return $out;
    }

    protected function extractBurdens(array $row): array
    {
        $out = [];
        $txt = $row['burdens'] ?? '';
        // look for loan amount
        if (preg_match('/(\\d{3,7})/', $txt, $m)) {
            $out[] = ['type' => 'loans', 'amount' => (float) $m[1], 'notes' => $txt];
        }

        return $out;
    }

    protected function inferHousingType(array $row): ?string
    {
        $txt = $row['details'] ?? '';
        if (mb_stripos($txt, 'منزل') !== false) {
            return 'منزل';
        }
        if (mb_stripos($txt, 'شقة') !== false) {
            return 'شقة';
        }

        return null;
    }

    protected function inferResidenceStatus(array $row): ?string
    {
        $txt = ($row['details'] ?? '').' '.($row['burdens'] ?? '');
        if (mb_stripos($txt, 'ايجار') !== false || mb_stripos($txt, 'إيجار') !== false) {
            return 'إيجار';
        }

        return null;
    }

    protected function inferAids(array $row): array
    {
        $out = [];
        $txt = ($row['requested_aids'] ?? '').' '.($row['details'] ?? '');
        if (mb_stripos($txt, 'عيني') !== false || mb_stripos($txt, 'عينية') !== false) {
            $out[AidType::HousingFurniture->value] = $txt;
        }
        if (mb_stripos($txt, 'علاج') !== false || mb_stripos($txt, 'دواء') !== false) {
            $out[AidType::Medical->value] = $txt;
        }
        if (mb_stripos($txt, 'ايجار') !== false || mb_stripos($txt, 'قرض') !== false || mb_stripos($txt, 'تسديد') !== false) {
            $out[AidType::Financial->value] = $txt;
        }

        return $out;
    }

    protected function parseDate($v): ?string
    {
        if (empty($v)) {
            return null;
        }
        // try common formats d-m-Y or d/m/Y
        $v = trim((string) $v);
        $v = str_replace('/', '-', $v);
        try {
            $dt = Carbon::createFromFormat('d-m-Y', $v) ?: Carbon::parse($v);

            return $dt->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function inferCaseType(array $row): string
    {
        $txt = $row['details'] ?? '';
        if (mb_stripos($txt, 'أرملة') !== false || mb_stripos($txt['wife_name'] ?? '', 'أرملة') !== false) {
            return 'أرملة';
        }
        // default to غارم if debts mentioned
        if (mb_stripos($row['burdens'] ?? '', 'قرض') !== false || preg_match('/\\d{4,7}/', $row['burdens'] ?? '')) {
            return 'غارم';
        }

        return 'يتيم';
    }

    protected function researchStatus(array $row): string
    {
        if (mb_stripos($row['committee_decision'] ?? '', 'مرفوض') !== false) {
            return 'rejected';
        }
        if (mb_stripos($row['was_discussed'] ?? '', 'نعم') !== false) {
            return 'under_review';
        }

        return 'draft';
    }
}
