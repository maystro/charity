<?php

use App\Http\Controllers\StorageFileController;
use App\Http\Middleware\EnsureRouteAccess;
use App\Livewire\AidRequests\CreateAidRequest;
use App\Livewire\AidRequests\Index as AidRequestIndex;
use App\Livewire\AidRequests\ShowAidRequest;
use App\Livewire\Alerts\AlertsIndex;
use App\Livewire\Backups\Index as BackupsIndex;
use App\Livewire\Debug;
use App\Livewire\Delivery\DeliveryIndex;
use App\Livewire\Deployments\AllowedPaths as DeploymentsAllowedPaths;
use App\Livewire\Deployments\CreateRelease as DeploymentsCreate;
use App\Livewire\Deployments\FtpSettings as DeploymentsFtpSettings;
use App\Livewire\Deployments\Index as DeploymentsIndex;
use App\Livewire\Deployments\Maintenance as DeploymentsMaintenance;
use App\Livewire\Deployments\ShowRelease as DeploymentsShow;
use App\Livewire\Deployments\SmartDeployment as DeploymentsSmartDeployment;
use App\Livewire\Donations\Create as DonationCreate;
use App\Livewire\Donations\Index as DonationIndex;
use App\Livewire\Donors\Index as DonorIndex;
use App\Livewire\Donors\Show as DonorShow;
use App\Livewire\Families\AssessmentHistory as FamilyAssessmentHistory;
use App\Livewire\Families\Create as FamilyCreate;
use App\Livewire\Families\Edit as FamilyEdit;
use App\Livewire\Families\Index as FamilyIndex;
use App\Livewire\Families\ReAssessmentIndex as FamilyReAssessmentIndex;
use App\Livewire\Families\ReviewShow as FamilyReviewShow;
use App\Livewire\Families\Show as FamilyShow;
use App\Livewire\Fieldworkers\Index as FieldworkerIndex;
use App\Livewire\Fieldworkers\Show as FieldworkerShow;
use App\Livewire\Organization\Index as OrganizationIndex;
use App\Livewire\Projects\CreateProject;
use App\Livewire\Projects\Index as ProjectIndex;
use App\Livewire\Research\Index as ResearchIndex;
use App\Livewire\Settings\SettingsIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Visits\Calendar as VisitCalendar;
use App\Livewire\Visits\Create as VisitCreate;
use App\Livewire\Visits\Edit as VisitEdit;
use App\Livewire\Visits\Execute as VisitExecute;
use App\Livewire\Visits\Index as VisitIndex;
use App\Livewire\Visits\Show as VisitShow;
use App\Models\AidRequestAttachment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Auth Routes (Guest)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Volt::route('/login', 'login')
        ->name('login');
});

Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::match(['HEAD', 'GET'], '/health/ping', fn () => response()->noContent());

/*
|--------------------------------------------------------------------------
| Public Media Files
|--------------------------------------------------------------------------
| Serves files from storage/app/public through Laravel (no symlink needed).
| Hostinger blocks direct /storage/ URLs (403) and disables exec()/symlink(),
| so we use a neutral /media/ prefix that reaches the framework.
|--------------------------------------------------------------------------
*/
Route::get('/media/{path}', StorageFileController::class)
    ->where('path', '.*')
    ->name('media.file');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', EnsureRouteAccess::class])->group(function () {
    Volt::route('/', 'dashboard')
        ->name('home');

    Volt::route('/dashboard', 'dashboard')
        ->name('dashboard');

    /*
    | Placeholder Routes
    */
    $placeholder = fn (string $title, string $description) => view('livewire.pages.placeholder', ['title' => $title, 'description' => $description]);

    // Case Management — Families (class-based Livewire)
    Route::get('/families', FamilyIndex::class)->name('families.index');
    Route::get('/families/create', FamilyCreate::class)->name('families.create');
    Route::get('/families/{family}/edit', FamilyEdit::class)->name('families.edit');

    // Families — Re-assessment (must be before {family} catch-all)
    Route::get('/families/re-assessment', FamilyReAssessmentIndex::class)->name('families.re-assessment-index');
    Route::get('/families/{family}', FamilyShow::class)->name('families.show');

    // Families — Assessment history (has {family} param)
    Route::get('/families/{family}/assessment-history', FamilyAssessmentHistory::class)->name('families.assessment-history');

    // Families — Review (under review cases, show page for approval/rejection)
    Route::get('/families/{family}/review', FamilyReviewShow::class)->name('families.review-show');

    Route::get('/research', ResearchIndex::class)->name('research.index');
    Volt::route('/research/create', 'research.create')
        ->name('research.create');
    Route::get('/aid-requests', AidRequestIndex::class)
        ->name('aid-requests.index');

    // Aid Requests – class-based Livewire components
    Route::get('/aid-requests/create', CreateAidRequest::class)
        ->name('aid-requests.create');

    // Show a single aid request
    Route::get('/aid-requests/{aidRequest}', ShowAidRequest::class)
        ->name('aid-requests.show');

    // Edit an existing aid request (draft or needs_completion)
    Route::get('/aid-requests/{aidRequest}/edit', CreateAidRequest::class)
        ->name('aid-requests.edit');
    // Visits
    Route::get('/visits', VisitIndex::class)->name('visits.index');
    Route::get('/visits/create', VisitCreate::class)->name('visits.create');
    Route::get('/visits/calendar', VisitCalendar::class)->name('visits.calendar');
    Route::get('/visits/{visit}', VisitShow::class)->name('visits.show');
    Route::get('/visits/{visit}/edit', VisitEdit::class)->name('visits.edit');
    Route::get('/visits/{visit}/execute', VisitExecute::class)->name('visits.execute');

    // Approval
    Route::get('/committees', fn () => $placeholder('لجان الاعتماد', 'إدارة لجان الاعتماد'))->name('committees.index');
    Route::get('/assistance', fn () => $placeholder('المساعدات والاستحقاقات', 'إدارة المساعدات'))->name('assistance.index');
    Route::get('/delivery', DeliveryIndex::class)->name('delivery.index');

    // Attachment download (local disk)
    Route::get('/attachments/{attachment}', function (AidRequestAttachment $attachment) {
        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    })->name('attachments.download');

    // Fieldwork
    Route::get('/fieldworkers', FieldworkerIndex::class)->name('fieldworkers.index');
    Route::get('/fieldworkers/{fieldworker}', FieldworkerShow::class)->name('fieldworkers.show');

    // Projects
    Route::get('/projects', ProjectIndex::class)->name('projects.index');
    Route::get('/projects/create', CreateProject::class)->name('projects.create');

    // Donors
    Route::get('/donors', DonorIndex::class)->name('donors.index');
    Route::get('/donors/{donor}', DonorShow::class)->name('donors.show');
    Route::get('/donations/create', DonationCreate::class)->name('donations.create');
    Route::get('/donations', DonationIndex::class)->name('donations.index');
    Route::get('/opportunities', fn () => $placeholder('فرص المساهمة', 'إدارة فرص المساهمة'))->name('opportunities.index');
    Route::get('/sponsorships', fn () => $placeholder('الكفلات', 'إدارة الكفلات'))->name('sponsorships.index');
    Route::get('/funds', fn () => $placeholder('الصناديق والمالية', 'إدارة الصناديق المالية'))->name('funds.index');

    // Programs
    Route::get('/programs', fn () => $placeholder('البرامج والحملات', 'إدارة البرامج والحملات'))->name('programs.index');
    Route::get('/warehouses', fn () => $placeholder('المخازن والتبرعات العينية', 'إدارة المخازن'))->name('warehouses.index');

    // Communications
    Route::get('/notifications-center', fn () => $placeholder('مركز الإشعارات', 'إدارة الإشعارات'))->name('notifications-center.index');
    Route::get('/whatsapp', fn () => $placeholder('رسائل واتساب', 'إدارة رسائل واتساب'))->name('whatsapp.index');
    Route::get('/tasks', fn () => $placeholder('المهام والتنبيهات', 'إدارة المهام والتنبيهات'))->name('tasks.index');
    Route::get('/complaints', fn () => $placeholder('الشكاوى والتظلمات', 'إدارة الشكاوى'))->name('complaints.index');
    Route::get('/reports', fn () => $placeholder('التقارير والاستقلامات', 'إدارة التقارير'))->name('reports.index');

    // Admin
    Route::get('/organization', OrganizationIndex::class)->name('organization.index');
    Route::get('/branches', fn () => $placeholder('الفروع والمناطق', 'إدارة الفروع والمناطق'))->name('branches.index');
    Route::get('/reference-data', fn () => $placeholder('البيانات التعريفية', 'إدارة البيانات التعريفية'))->name('reference-data.index');
    Route::get('/users', UsersIndex::class)->name('users.index');
    Route::get('/settings', SettingsIndex::class)->name('settings.index');
    Route::get('/alerts', AlertsIndex::class)->name('alerts.index');
    Route::get('/audit-log', fn () => $placeholder('سجل الحركة', 'سجل حركة النظام'))->name('audit-log.index');

    // Database backups (super admin only — must be before deployments/{release})
    Route::middleware('super_admin')->prefix('superadmin-dashboard')->name('backups.')->group(function () {
        Route::get('/backups', BackupsIndex::class)->name('index');
    });

    // Deployments (super admin only — technical area)
    Route::middleware('super_admin')->prefix('superadmin-dashboard')->name('deployments.')->group(function () {
        Route::get('/', DeploymentsIndex::class)->name('index');
        Route::get('/create', DeploymentsCreate::class)->name('create');
        Route::get('/allowed-paths', DeploymentsAllowedPaths::class)->name('allowed-paths');
        Route::get('/ftp-settings', DeploymentsFtpSettings::class)->name('ftp-settings');
        Route::get('/maintenance', DeploymentsMaintenance::class)->name('maintenance');
        Route::get('/smart-deployment', DeploymentsSmartDeployment::class)->name('smart-deployment');
        Route::get('/{release}', DeploymentsShow::class)->name('show');
    });

    // Developer
    Volt::route('/developer/ui-kit', 'developer.ui-kit')
        ->name('developer.ui-kit');

    Route::get('/debug', Debug::class)
        ->name('debug.index');
});
