<?php

namespace App\Services\DemoData;

use App\Enums\AidRequestStatus;
use App\Enums\DonationMethod;
use App\Enums\DonationType;
use App\Enums\DonorType;
use App\Enums\FamilyStatus;
use App\Enums\ProjectStatus;
use App\Models\AidRequest;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Family;
use App\Models\FamilyAssessment;
use App\Models\Fieldworker;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\SocialResearch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemoDataService
{
    private const int FAMILY_CASE_NUMBER_START = 90000001;

    private const string FIELDWORKER_EMAIL_PREFIX = 'demo-fieldworker-';

    private const string RESEARCH_NUMBER_PREFIX = 'DEMO-RES-';

    private const string REQUEST_NUMBER_PREFIX = 'DEMO-AR-';

    private const string DONOR_NOTE_PREFIX = 'DEMO-DONOR-';

    private const string DONATION_NOTE_PREFIX = 'DEMO-DONATION-';

    private const string PROJECT_NAME_PREFIX = 'مشروع تجريبي';

    /**
     * Create or refresh the full demo dataset.
     *
     * @return array<string, int>
     */
    public function seed(): array
    {
        return DB::transaction(function (): array {
            $this->purge();

            $admin = User::query()->admins()->first();

            if (! $admin) {
                $admin = User::query()->firstOrCreate(
                    ['username' => 'admin'],
                    [
                        'name' => 'مدير النظام',
                        'email' => 'admin@charity.org',
                        'password' => 'password',
                        'role' => User::ROLE_ADMIN,
                    ]
                );
            }

            $fieldworkers = $this->seedFieldworkers();
            $families = $this->seedFamilies($admin, $fieldworkers);
            $this->seedResearches($admin, $families);
            $this->seedAidRequests($admin, $families, $fieldworkers);
            $projects = $this->seedProjects($admin);
            $donors = $this->seedDonors();
            $this->seedDonations($admin, $projects, $donors);

            return [
                'fieldworkers' => $fieldworkers->count(),
                'families' => $families->count(),
                'projects' => $projects->count(),
                'donors' => $donors->count(),
            ];
        });
    }

    /**
     * Remove every demo record that was created by this service.
     *
     * @return array<string, int>
     */
    public function purge(): array
    {
        return DB::transaction(function (): array {
            $deletedDonations = Donation::query()
                ->where('notes', 'like', self::DONATION_NOTE_PREFIX.'%')
                ->delete();

            $deletedProjects = Project::query()
                ->where('name', 'like', self::PROJECT_NAME_PREFIX.'%')
                ->delete();

            $deletedDonors = Donor::query()
                ->where('notes', 'like', self::DONOR_NOTE_PREFIX.'%')
                ->delete();

            $deletedFamilies = Family::query()
                ->whereBetween('case_number', [self::FAMILY_CASE_NUMBER_START, self::FAMILY_CASE_NUMBER_START + 9])
                ->forceDelete();

            $deletedFieldworkers = Fieldworker::query()
                ->where('code', 'like', 'DEMO-FW-%')
                ->delete();

            $deletedUsers = User::query()
                ->where('email', 'like', self::FIELDWORKER_EMAIL_PREFIX.'%@charity.test')
                ->delete();

            return [
                'donations' => (int) $deletedDonations,
                'projects' => (int) $deletedProjects,
                'donors' => (int) $deletedDonors,
                'families' => (int) $deletedFamilies,
                'fieldworkers' => (int) $deletedFieldworkers,
                'users' => (int) $deletedUsers,
            ];
        });
    }

    /**
     * @return Collection<int, Fieldworker>
     */
    protected function seedFieldworkers(): Collection
    {
        $names = [
            'أحمد سمير',
            'مريم خالد',
            'محمد عبد الله',
            'آية حسن',
            'يوسف علي',
            'سارة محمود',
            'عمر السيد',
            'هبة إبراهيم',
            'خالد فاروق',
            'شيماء نادر',
        ];

        $governorates = array_values(array_slice(config('governorates.egypt'), 0, 10));

        return collect($names)
            ->values()
            ->map(function (string $name, int $index) use ($governorates): Fieldworker {
                $user = User::query()->firstOrCreate(
                    ['email' => self::FIELDWORKER_EMAIL_PREFIX.($index + 1).'@charity.test'],
                    [
                        'name' => $name,
                        'username' => self::FIELDWORKER_EMAIL_PREFIX.($index + 1),
                        'password' => 'password',
                        'role' => User::ROLE_FIELDWORKER,
                    ]
                );

                return Fieldworker::query()->updateOrCreate(
                    ['code' => sprintf('DEMO-FW-%02d', $index + 1)],
                    [
                        'user_id' => $user->id,
                        'name' => $name,
                        'phone' => '01'.str_pad((string) (100000000 + $index), 9, '0', STR_PAD_LEFT),
                        'governorate' => $governorates[$index] ?? $governorates[0],
                        'area' => 'منطقة '.($index + 1),
                        'status' => 'active',
                        'latitude' => 26.0 + ($index * 0.15),
                        'longitude' => 31.0 + ($index * 0.1),
                        'notes' => 'موظف تجريبي لإدارة البيانات التجريبية',
                    ]
                );
            });
    }

    /**
     * @param  Collection<int, Fieldworker>  $fieldworkers
     * @return Collection<int, Family>
     */
    protected function seedFamilies(User $admin, Collection $fieldworkers): Collection
    {
        $families = collect([
            [
                'case_name' => 'أسرة النور',
                'status' => FamilyStatus::Approved->value,
                'approved_at' => now()->subMonth(),
                'fieldworker_index' => 0,
            ],
            [
                'case_name' => 'أسرة الأمل',
                'status' => FamilyStatus::Approved->value,
                'approved_at' => now()->subMonths(3),
                'fieldworker_index' => 1,
            ],
            [
                'case_name' => 'أسرة الرحمة',
                'status' => FamilyStatus::Approved->value,
                'approved_at' => now()->subMonths(4),
                'fieldworker_index' => 2,
            ],
            [
                'case_name' => 'أسرة السكينة',
                'status' => FamilyStatus::UnderReview->value,
                'fieldworker_index' => 3,
            ],
            [
                'case_name' => 'أسرة الهدى',
                'status' => FamilyStatus::UnderReview->value,
                'fieldworker_index' => 4,
            ],
            [
                'case_name' => 'أسرة الصفاء',
                'status' => FamilyStatus::NeedsCompletion->value,
                'fieldworker_index' => 5,
            ],
            [
                'case_name' => 'أسرة السلام',
                'status' => FamilyStatus::Draft->value,
                'fieldworker_index' => 6,
            ],
            [
                'case_name' => 'أسرة الكرامة',
                'status' => FamilyStatus::Rejected->value,
                'fieldworker_index' => 7,
            ],
            [
                'case_name' => 'أسرة الفجر',
                'status' => FamilyStatus::Approved->value,
                'approved_at' => now()->subMonths(2),
                'fieldworker_index' => 8,
            ],
            [
                'case_name' => 'أسرة الحياة',
                'status' => FamilyStatus::Approved->value,
                'approved_at' => now()->subWeek(),
                'fieldworker_index' => 9,
            ],
        ]);

        return $families->values()->map(function (array $data, int $index) use ($admin, $fieldworkers): Family {
            $caseNumber = self::FAMILY_CASE_NUMBER_START + $index;
            $fieldworker = $fieldworkers[$data['fieldworker_index']];
            $base = [
                'case_number' => $caseNumber,
                'case_type' => ['يتيم', 'أرملة', 'مطلقة', 'غارم', 'ذوي احتياجات', 'مسن'][$index % 6],
                'case_name' => $data['case_name'],
                'community' => config('governorates.egypt')[$index] ?? 'محافظة تجريبية',
                'detailed_address' => 'عنوان تجريبي رقم '.($index + 1),
                'phone' => '01'.str_pad((string) (300000000 + $index), 9, '0', STR_PAD_LEFT),
                'family_type' => $index % 2 === 0 ? 'بسيطة' : 'مركبة',
                'members_count' => 3 + ($index % 5),
                'total_income' => 900 + ($index * 150),
                'average_income_per_person' => 300 + ($index * 25),
                'created_by' => $admin->id,
                'fieldworker_id' => $fieldworker->id,
            ];

            if (($data['status'] ?? null) === FamilyStatus::Approved->value) {
                $family = Family::query()->updateOrCreate(
                    ['case_number' => $caseNumber],
                    $base + [
                        'status' => FamilyStatus::Approved->value,
                        'submitted_by' => $fieldworker->user_id,
                        'submitted_at' => $data['approved_at']->copy()->subWeeks(2),
                        'approved_by' => $admin->id,
                        'approved_at' => $data['approved_at'],
                        'rejected_by' => null,
                        'rejected_at' => null,
                    ]
                );

                $assessment = FamilyAssessment::query()->updateOrCreate(
                    [
                        'family_id' => $family->id,
                        'round' => 1,
                    ],
                    [
                        'status' => FamilyStatus::Approved->value,
                        'case_type' => $family->case_type,
                        'case_name' => $family->case_name,
                        'community' => $family->community,
                        'detailed_address' => $family->detailed_address,
                        'phone' => $family->phone,
                        'family_type' => $family->family_type,
                        'members_count' => $family->members_count,
                        'total_income' => $family->total_income,
                        'average_income_per_person' => $family->average_income_per_person,
                        'created_by' => $admin->id,
                        'approved_by' => $admin->id,
                        'approved_at' => $data['approved_at'],
                    ]
                );

                $family->update(['current_assessment_id' => $assessment->id]);

                return $family->refresh();
            }

            return Family::query()->updateOrCreate(
                ['case_number' => $caseNumber],
                $base + match ($data['status']) {
                    FamilyStatus::UnderReview->value => [
                        'status' => FamilyStatus::UnderReview->value,
                        'submitted_by' => $fieldworker->user_id,
                        'submitted_at' => now()->subDays(5),
                    ],
                    FamilyStatus::NeedsCompletion->value => [
                        'status' => FamilyStatus::NeedsCompletion->value,
                        'submitted_by' => $fieldworker->user_id,
                        'submitted_at' => now()->subDays(8),
                    ],
                    FamilyStatus::Draft->value => [
                        'status' => FamilyStatus::Draft->value,
                    ],
                    FamilyStatus::Rejected->value => [
                        'status' => FamilyStatus::Rejected->value,
                        'submitted_by' => $fieldworker->user_id,
                        'submitted_at' => now()->subDays(9),
                        'rejected_by' => $admin->id,
                        'rejected_at' => now()->subDays(2),
                        'rejection_reason' => 'بيانات غير مكتملة في نموذج العرض التجريبي',
                    ],
                    default => [],
                }
            );
        });
    }

    /**
     * @param  Collection<int, Family>  $families
     */
    protected function seedResearches(User $admin, Collection $families): void
    {
        $families->values()->each(function (Family $family, int $index) use ($admin): void {
            $researchStatus = match ($family->status) {
                FamilyStatus::Approved->value => 'approved',
                FamilyStatus::Rejected->value => 'rejected',
                FamilyStatus::Draft->value => 'draft',
                default => 'under_review',
            };

            SocialResearch::query()->updateOrCreate(
                ['research_number' => sprintf(self::RESEARCH_NUMBER_PREFIX.'%02d', $index + 1)],
                [
                    'family_id' => $family->id,
                    'research_type' => $index % 2 === 0 ? 'initial' : 'follow_up',
                    'conducted_at' => now()->subDays(30 - $index),
                    'approved_at' => in_array($researchStatus, ['approved'], true) ? now()->subDays(20 - $index) : null,
                    'expiry_date' => now()->addMonths(3),
                    'eligibility_degree' => $index % 2 === 0 ? 'مستحق' : 'مستحق جزئياً',
                    'average_income' => 700 + ($index * 75),
                    'net_income' => 500 + ($index * 60),
                    'recommendation' => 'توصية تجريبية رقم '.($index + 1),
                    'committee_decision' => 'قرار تجريبي رقم '.($index + 1),
                    'status' => $researchStatus,
                    'created_by' => $admin->id,
                    'approved_by' => in_array($researchStatus, ['approved'], true) ? $admin->id : null,
                ]
            );
        });
    }

    /**
     * @param  Collection<int, Family>  $families
     * @param  Collection<int, Fieldworker>  $fieldworkers
     */
    protected function seedAidRequests(User $admin, Collection $families, Collection $fieldworkers): void
    {
        $approvedFamilies = $families->where('status', FamilyStatus::Approved->value)->values();
        $statuses = [
            AidRequestStatus::Submitted->value,
            AidRequestStatus::UnderReview->value,
            AidRequestStatus::Approved->value,
            AidRequestStatus::PartiallyApproved->value,
            AidRequestStatus::Rejected->value,
            AidRequestStatus::NeedsCompletion->value,
            AidRequestStatus::Draft->value,
            AidRequestStatus::Approved->value,
            AidRequestStatus::PartiallyApproved->value,
            AidRequestStatus::Completed->value,
        ];

        collect($statuses)->values()->each(function (string $status, int $index) use ($admin, $approvedFamilies, $fieldworkers): void {
            $family = $approvedFamilies[$index % max($approvedFamilies->count(), 1)];
            $fieldworker = $fieldworkers[$index % max($fieldworkers->count(), 1)];

            AidRequest::query()->updateOrCreate(
                ['request_number' => sprintf(self::REQUEST_NUMBER_PREFIX.'%03d', $index + 1)],
                [
                    'family_id' => $family->id,
                    'branch_id' => null,
                    'area_id' => null,
                    'representative_id' => $fieldworker->id,
                    'created_by' => $admin->id,
                    'submitted_by' => in_array($status, [AidRequestStatus::Draft->value], true) ? null : $fieldworker->user_id,
                    'source_type' => $index % 2 === 0 ? 'الأسرة مباشرة' : 'المندوب الميداني',
                    'applicant_name' => $family->case_name,
                    'applicant_relation' => 'رب الأسرة',
                    'applicant_phone' => $family->phone,
                    'request_type' => ['وقتية', 'دورية', 'طارئة'][$index % 3],
                    'priority' => ['عادية', 'متوسطة', 'مرتفعة', 'عاجلة جداً'][$index % 4],
                    'title' => 'طلب مساعدة تجريبي '.($index + 1),
                    'description' => 'وصف تجريبي لطلب المساعدة رقم '.($index + 1),
                    'requested_at' => now()->subDays(14 - $index),
                    'needed_by' => now()->addDays(7 + $index),
                    'campaign_id' => null,
                    'status' => $status,
                    'internal_notes' => 'DEMO-AID-REQUEST-'.($index + 1),
                    'exception_reason' => $status === AidRequestStatus::Rejected->value ? 'تم الرفض في نموذج العرض' : null,
                    'duplicate_reason' => null,
                    'total_estimated_amount' => 500 + ($index * 125),
                    'submitted_at' => in_array($status, [AidRequestStatus::Draft->value], true) ? null : now()->subDays(13 - $index),
                ]
            );
        });
    }

    /**
     * @return Collection<int, Project>
     */
    protected function seedProjects(User $admin): Collection
    {
        $projects = collect([
            ['name' => 'مشروع تجريبي - سلة غذائية', 'status' => ProjectStatus::Planning->value],
            ['name' => 'مشروع تجريبي - كساء الشتاء', 'status' => ProjectStatus::Active->value],
            ['name' => 'مشروع تجريبي - سقيا', 'status' => ProjectStatus::Active->value],
            ['name' => 'مشروع تجريبي - علاج مرضى', 'status' => ProjectStatus::Completed->value],
            ['name' => 'مشروع تجريبي - ترميم منازل', 'status' => ProjectStatus::Suspended->value],
            ['name' => 'مشروع تجريبي - كفالة أيتام', 'status' => ProjectStatus::Cancelled->value],
            ['name' => 'مشروع تجريبي - دفء الشتاء', 'status' => ProjectStatus::Planning->value],
            ['name' => 'مشروع تجريبي - جهاز عروسة', 'status' => ProjectStatus::Active->value],
            ['name' => 'مشروع تجريبي - توصيل مياه', 'status' => ProjectStatus::Completed->value],
            ['name' => 'مشروع تجريبي - دعم طلاب', 'status' => ProjectStatus::Suspended->value],
        ]);

        return $projects->values()->map(function (array $projectData, int $index) use ($admin): Project {
            $project = Project::query()->updateOrCreate(
                ['name' => $projectData['name']],
                [
                    'description' => 'تفاصيل تجريبية للمشروع '.$projectData['name'],
                    'governorate' => config('governorates.egypt')[$index] ?? null,
                    'status' => $projectData['status'],
                    'total_budget' => 50000 + ($index * 12500),
                    'start_date' => now()->subMonths(10 - $index)->format('Y-m-d'),
                    'end_date' => now()->addMonths(4 + $index)->format('Y-m-d'),
                    'created_by' => $admin->id,
                ]
            );

            ProjectPhase::query()->updateOrCreate(
                ['project_id' => $project->id, 'sort_order' => 1],
                [
                    'name' => 'مرحلة التحضير',
                    'description' => 'تحضير وتجهيزات المشروع التجريبي',
                    'cost' => 15000 + ($index * 1000),
                ]
            );

            ProjectPhase::query()->updateOrCreate(
                ['project_id' => $project->id, 'sort_order' => 2],
                [
                    'name' => 'مرحلة التنفيذ',
                    'description' => 'تنفيذ أنشطة المشروع التجريبي',
                    'cost' => 25000 + ($index * 1200),
                ]
            );

            return $project;
        });
    }

    /**
     * @return Collection<int, Donor>
     */
    protected function seedDonors(): Collection
    {
        $names = [
            ['name' => 'أحمد فؤاد', 'type' => DonorType::Individual->value],
            ['name' => 'مؤسسة الخير', 'type' => DonorType::Organization->value],
            ['name' => 'محمد حسام', 'type' => DonorType::Individual->value],
            ['name' => 'جمعية النور', 'type' => DonorType::Organization->value],
            ['name' => 'سمر ياسر', 'type' => DonorType::Individual->value],
            ['name' => 'شركة البركة', 'type' => DonorType::Organization->value],
            ['name' => 'عادل منصور', 'type' => DonorType::Individual->value],
            ['name' => 'مؤسسة الرحمة', 'type' => DonorType::Organization->value],
            ['name' => 'أميرة شوقي', 'type' => DonorType::Individual->value],
            ['name' => 'شركة العطاء', 'type' => DonorType::Organization->value],
        ];

        return collect($names)->values()->map(function (array $donorData, int $index): Donor {
            return Donor::query()->updateOrCreate(
                ['name' => $donorData['name']],
                [
                    'phone' => '01'.str_pad((string) (400000000 + $index), 9, '0', STR_PAD_LEFT),
                    'email' => 'demo-donor-'.($index + 1).'@charity.test',
                    'type' => $donorData['type'],
                    'city' => config('governorates.egypt')[$index] ?? null,
                    'notes' => self::DONOR_NOTE_PREFIX.($index + 1),
                ]
            );
        });
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, Donor>  $donors
     */
    protected function seedDonations(User $admin, Collection $projects, Collection $donors): void
    {
        $methods = [
            DonationMethod::Cash->value,
            DonationMethod::EWallet->value,
            DonationMethod::Instapay->value,
            DonationMethod::Cash->value,
            DonationMethod::Instapay->value,
            DonationMethod::EWallet->value,
            DonationMethod::Cash->value,
            DonationMethod::Instapay->value,
            DonationMethod::Cash->value,
            DonationMethod::EWallet->value,
        ];

        collect($methods)->values()->each(function (string $method, int $index) use ($admin, $projects, $donors): void {
            $project = $projects[$index];
            $donor = $donors[$index];

            Donation::query()->updateOrCreate(
                ['notes' => self::DONATION_NOTE_PREFIX.($index + 1)],
                [
                    'donor_id' => $donor->id,
                    'project_id' => $project->id,
                    'donor_name' => $donor->name,
                    'donor_type' => $donor->type->value,
                    'amount' => 2000 + ($index * 750),
                    'method' => $method,
                    'type' => $index % 4 === 0 ? DonationType::InKind->value : DonationType::Cash->value,
                    'donated_at' => now()->subDays(20 - $index)->format('Y-m-d'),
                    'notes' => self::DONATION_NOTE_PREFIX.($index + 1),
                    'created_by' => $admin->id,
                ]
            );
        });
    }
}
