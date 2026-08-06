<?php

namespace App\Livewire\AidRequests;

use App\Enums\AidRequestStatus;
use App\Enums\AidType;
use App\Enums\FamilyStatus;
use App\Models\AidRequest;
use App\Models\Family;
use App\Services\AidRequests\AidRequestService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['title' => 'إضافة طلب مساعدة'])]
class CreateAidRequest extends Component
{
    use WithFileUploads;

    /** معرّف طلب المساعدة عند التعديل */
    public ?int $aidRequestId = null;

    /** الأسرة المختارة */
    public ?int $family_id = null;

    /** المصدر */
    public string $source_type = 'الأسرة مباشرة';

    public ?string $applicant_name = null;

    public ?string $applicant_relation = null;

    public ?string $applicant_phone = null;

    public string $title = '';

    public ?string $description = null;

    public ?string $needed_by = null;

    public ?string $internal_notes = null;

    public bool $acknowledged = false;

    public bool $submitting = false;

    /** نوع المساعدة المحدد لإنشاء بند جديد (قيمة AidType) */
    public ?string $selectedAidType = null;

    /** نموذج بند جديد قيد الإضافة */
    public array $draft = [
        'aid_type' => null,
        'priority' => 'عادية',
        'need_title' => '',
        'need_description' => '',
        'unit_cost' => null,
        'is_recurring' => false,
        'recurrence_interval_days' => null,
        'execution_start_date' => null,
    ];

    /** @var array<int, array<string, mixed>> */
    public array $draftAttachments = [];

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    protected $listeners = ['attachments-updated' => '$refresh'];

    public function mount(mixed $aidRequest = null): void
    {
        if ($aidRequest instanceof AidRequest) {
            $this->loadFromModel($aidRequest);
        } elseif (is_numeric($aidRequest) && $aidRequest > 0) {
            $this->loadExisting((int) $aidRequest);
        } else {
            $this->authorize('create', AidRequest::class);
            // إنشاء طلب جديد: اختر أول أسرة معتمدة تلقائيًا لتحسين تجربة المستخدم
            $query = Family::where('status', FamilyStatus::Approved->value)
                ->orderBy('case_name');

            // المندوب لا يرى إلا أسر محافظته
            $user = auth()->user();
            if ($user?->isFieldworker() && $user->fieldworker) {
                $governorate = $user->fieldworker->governorate;
                $query->whereHas('fieldworker', fn ($q) => $q->where('governorate', $governorate));
            }

            $this->family_id = $query->value('id');
        }
    }

    private function loadFromModel(AidRequest $request): void
    {
        $this->loadExistingData($request);
    }

    private function loadExisting(int $id): void
    {
        $request = AidRequest::with('items')->findOrFail($id);
        $this->loadExistingData($request);
    }

    private function loadExistingData(AidRequest $request): void
    {

        // يمكن التعديل فقط للمسودات أو التي تحتاج استكمالاً
        if (! in_array($request->status, [
            AidRequestStatus::Draft->value,
            AidRequestStatus::NeedsCompletion->value,
        ], true)) {
            $this->redirect(route('aid-requests.show', $request), navigate: true);

            return;
        }

        $this->authorize('update', $request);

        $this->aidRequestId = $request->id;
        $this->family_id = $request->family_id;
        $this->source_type = $request->source_type ?? 'الأسرة مباشرة';
        $this->applicant_name = $request->applicant_name;
        $this->applicant_relation = $request->applicant_relation;
        $this->applicant_phone = $request->applicant_phone;
        $this->title = $request->title ?? '';
        $this->description = $request->description;
        $this->needed_by = $request->needed_by?->format('Y-m-d');
        $this->internal_notes = $request->internal_notes;

        $this->items = $request->items->map(fn ($item) => [
            'id' => $item->id,
            'aid_type' => $item->aid_type,
            'title' => $item->title,
            'need_title' => $item->title,
            'priority' => $item->priority ?? 'عادية',
            'need_description' => $item->description,
            'unit_cost' => $item->unit_cost,
            'is_recurring' => $item->execution_type === 'دورية' || $item->recurrence_type === 'دورية',
            'recurrence_interval_days' => $item->recurrence_interval_days,
            'execution_start_date' => $item->execution_start_date?->format('Y-m-d'),
        ])->toArray();
    }

    #[Computed]
    public function families(): array
    {
        $query = Family::where('status', FamilyStatus::Approved->value)
            ->orderBy('case_name');

        // المندوب لا يرى إلا أسر محافظته
        $user = auth()->user();
        if ($user?->isFieldworker() && $user->fieldworker) {
            $governorate = $user->fieldworker->governorate;
            $query->whereHas('fieldworker', fn ($q) => $q->where('governorate', $governorate));
        }

        return $query
            ->get(['id', 'case_name', 'case_number'])
            ->map(fn (Family $f) => ['id' => $f->id, 'name' => $f->case_name.' ('.$f->case_number.')'])
            ->toArray();
    }

    #[Computed]
    public function selectedFamily(): ?Family
    {
        if (! $this->family_id) {
            return null;
        }

        return Family::with('aids')->find($this->family_id);
    }

    /** أنواع المساعدات المتاحة بناءً على الأهلية (إن وُجدت) أو كلها. */
    #[Computed]
    public function availableAidTypes(): array
    {
        $types = AidType::cases();

        // ملاحظة: $this->selectedFamily->aids يُرجَع عبر Accessor في نموذج Family
        // كمصفوفة مفتاحها aid_type وقيمتها ['eligible' => bool, 'reasons' => ?string]
        $aids = $this->selectedFamily?->aids ?? [];

        if (! empty($aids)) {
            $eligible = collect($aids)
                ->filter(fn ($data) => ! empty($data['eligible']))
                ->keys()
                ->all();

            if (! empty($eligible)) {
                $types = array_filter($types, fn ($t) => in_array($t->value, $eligible, true));
            }
        }

        return collect($types)
            ->map(fn (AidType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'icon' => $t->icon(),
                'description' => $t->description(),
            ])
            ->values()
            ->toArray();
    }

    // ─── Items management ──────────────────────────────────────────────────────

    /** فتح نموذج إضافة بند خدمة جديد (يختار النوع من داخل النموذج). */
    public function openAddItem(): void
    {
        $this->selectedAidType = 'new';
        $this->draft = [
            // تحديد أول نوع متاح تلقائيًا ليعمل الاختيار الأول مباشرة.
            'aid_type' => $this->availableAidTypes[0]['value'] ?? null,
            'priority' => 'عادية',
            'need_title' => '',
            'need_description' => '',
            'unit_cost' => null,
            'is_recurring' => false,
            'recurrence_interval_days' => null,
            'execution_start_date' => null,
        ];
        $this->draftAttachments = [];
    }

    public function cancelAddItem(): void
    {
        $this->selectedAidType = null;
        $this->draft = [
            'aid_type' => null,
            'priority' => 'عادية',
            'need_title' => '',
            'need_description' => '',
            'unit_cost' => null,
            'is_recurring' => false,
            'recurrence_interval_days' => null,
            'execution_start_date' => null,
        ];
        $this->draftAttachments = [];
    }

    public function addDraftAttachment(): void
    {
        $this->draftAttachments[] = [
            'file' => null,
            'name' => '',
        ];
    }

    public function updatedDraftAttachments(): void
    {
        $this->dispatch('attachments-updated');
    }

    public function removeDraftAttachment(int $index): void
    {
        unset($this->draftAttachments[$index]);
        $this->draftAttachments = array_values($this->draftAttachments);
    }

    public function saveItem(): void
    {
        $this->validate([
            'draft.aid_type' => 'required|string',
            'draft.need_title' => 'required|string|min:3|max:120',
            'draft.need_description' => 'required|string|min:5|max:1000',
            'draft.unit_cost' => 'required|numeric|min:0.01',
            'draft.priority' => 'required|in:عادية,متوسطة,مرتفعة,عاجلة جداً',
            'draft.recurrence_interval_days' => 'nullable|integer|min:1|max:365',
            'draft.execution_start_date' => 'nullable|date',
            'draftAttachments.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
        ], [
            'draft.aid_type.required' => 'يجب اختيار نوع الطلب.',
            'draft.need_title.required' => 'العنوان المختصر للطلب مطلوب.',
            'draft.need_title.min' => 'العنوان المختصر يجب أن يكون 3 أحرف على الأقل.',
            'draft.need_description.required' => 'وصف الاحتياج مطلوب.',
            'draft.unit_cost.required' => 'القيمة المالية مطلوبة.',
            'draft.unit_cost.numeric' => 'القيمة المالية يجب أن تكون رقمًا.',
            'draft.unit_cost.min' => 'القيمة المالية يجب أن تكون أكبر من صفر.',
            'draftAttachments.*.file.file' => 'المرفق يجب أن يكون ملفًا صالحًا.',
            'draftAttachments.*.file.mimes' => 'صيغة المرفق غير مسموحة.',
            'draftAttachments.*.file.max' => 'حجم المرفق يجب ألا يتجاوز 10 ميجابايت.',
        ]);

        if ($this->draft['is_recurring']) {
            $this->validate([
                'draft.recurrence_interval_days' => 'required|integer|min:1|max:365',
                'draft.execution_start_date' => 'required|date',
            ], [
                'draft.recurrence_interval_days.required' => 'الفارق الزمني بين مرات التنفيذ مطلوب للمساعدة الدورية.',
                'draft.execution_start_date.required' => 'تاريخ بدء التنفيذ مطلوب للمساعدة الدورية.',
            ]);
        }

        $this->items[] = [
            'id' => null,
            'aid_type' => $this->draft['aid_type'],
            'title' => $this->draft['need_title'],
            'priority' => $this->draft['priority'],
            'need_description' => $this->draft['need_description'],
            'unit_cost' => $this->draft['unit_cost'],
            'is_recurring' => (bool) $this->draft['is_recurring'],
            'recurrence_interval_days' => $this->draft['is_recurring'] ? $this->draft['recurrence_interval_days'] : null,
            'execution_start_date' => $this->draft['is_recurring'] ? $this->draft['execution_start_date'] : null,
            'attachments' => $this->uploadableDraftAttachments(),
        ];

        $this->cancelAddItem();
        $this->dispatch('toast', message: 'تمت إضافة المساعدة للقائمة بنجاح.', type: 'success');
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /** @return array<int, array<string, mixed>> */
    private function uploadableDraftAttachments(): array
    {
        return collect($this->draftAttachments)
            ->filter(fn ($a) => ($a['file'] ?? null) instanceof UploadedFile)
            ->map(fn ($a) => [
                'file' => $a['file'],
                'name' => $a['name'] ?? '',
            ])
            ->values()
            ->toArray();
    }

    // ─── Persist ───────────────────────────────────────────────────────────────

    public function saveDraft(): void
    {
        $this->requireFamily();

        $data = $this->baseData();
        $data['items'] = $this->normalizedItems();

        $aidRequest = app(AidRequestService::class)->createOrUpdateDraft($data, $this->aidRequestId);
        $this->aidRequestId = $aidRequest->id;

        $this->dispatch('toast', message: 'تم حفظ الطلب كمسودة لحين الاعتماد.', type: 'success');
    }

    public function confirmSubmit(): void
    {
        $this->validate([
            'family_id' => 'required|exists:families,id',
            'title' => 'required|string|max:255',
            'items' => 'required|array|min:1',
        ], [
            'title.required' => 'العنوان العام للطلب مطلوب.',
            'items.required' => 'يجب إضافة مساعدة واحدة على الأقل قبل الإرسال.',
            'items.min' => 'يجب إضافة مساعدة واحدة على الأقل قبل الإرسال.',
        ]);

        $this->dispatch('open-modal', 'submit-confirm');
    }

    public function submit(): void
    {
        if (! $this->acknowledged) {
            $this->addError('acknowledged', 'يجب الموافقة على الإقرار قبل الإرسال.');

            return;
        }

        $this->validate([
            'family_id' => 'required|exists:families,id',
            'title' => 'required|string|max:255',
            'items' => 'required|array|min:1',
        ], [
            'title.required' => 'العنوان العام للطلب مطلوب.',
            'items.required' => 'يجب إضافة مساعدة واحدة على الأقل قبل الإرسال.',
            'items.min' => 'يجب إضافة مساعدة واحدة على الأقل قبل الإرسال.',
        ]);

        $this->submitting = true;

        $data = $this->baseData();
        $data['items'] = $this->normalizedItems();

        $aidRequest = app(AidRequestService::class)->submit($data, $this->aidRequestId);
        $this->submitting = false;

        $this->redirect(route('aid-requests.show', $aidRequest), navigate: true);
    }

    private function requireFamily(): void
    {
        $rules = [
            'family_id' => [
                'required',
                Rule::exists('families', 'id')->where('status', FamilyStatus::Approved->value),
            ],
        ];

        // المندوب: التأكد أن الأسرة في نفس محافظته
        $user = auth()->user();
        if ($user?->isFieldworker() && $user->fieldworker) {
            $governorate = $user->fieldworker->governorate;
            $rules['family_id'][] = function ($attribute, $value, $fail) use ($governorate) {
                $family = Family::with('fieldworker')->find($value);
                if (! $family || $family->fieldworker?->governorate !== $governorate) {
                    $fail('الأسرة المختارة ليست في نطاق محافظتك.');
                }
            };
        }

        $this->validate($rules, [
            'family_id.required' => 'يجب اختيار أسرة معتمدة.',
            'family_id.exists' => 'يجب اختيار أسرة معتمدة.',
        ]);
    }

    /** @return array<string, mixed> */
    private function baseData(): array
    {
        return [
            'family_id' => $this->family_id,
            'source_type' => $this->source_type,
            'applicant_name' => $this->applicant_name,
            'applicant_relation' => $this->applicant_relation,
            'applicant_phone' => $this->applicant_phone,
            'request_type' => $this->computeRequestType(),
            'priority' => $this->computePriority(),
            'title' => $this->title,
            'description' => $this->description,
            'needed_by' => $this->needed_by,
            'internal_notes' => $this->internal_notes,
        ];
    }

    /** تحديد نوع الطلب (وقتية/دورية/طارئة) تلقائياً بناءً على البنود. */
    private function computeRequestType(): string
    {
        $recurringCount = collect($this->items)->where('is_recurring', true)->count();

        if ($recurringCount > 0) {
            return 'دورية';
        }

        return 'وقتية';
    }

    /** تحديد الأولوية الإجمالية كأعلى أولوية بين البنود. */
    private function computePriority(): string
    {
        $order = ['عاجلة جداً' => 1, 'مرتفعة' => 2, 'متوسطة' => 3, 'عادية' => 4];
        $priorities = collect($this->items)->pluck('priority')->unique()->values()->all();

        if (empty($priorities)) {
            return 'عادية';
        }

        $sorted = collect($priorities)->sortBy(fn ($p) => $order[$p] ?? 5);

        return $sorted->first();
    }

    /** @return array<int, array<string, mixed>> */
    private function normalizedItems(): array
    {
        return collect($this->items)->map(function ($item, $index) {
            return [
                'aid_type' => $item['aid_type'],
                'category_id' => 1,
                'title' => $item['title'],
                'description' => $item['need_description'],
                'execution_type' => $item['is_recurring'] ? 'دورية' : 'وقتية',
                'quantity' => 1,
                'unit_cost' => $item['unit_cost'],
                'estimated_total' => $item['unit_cost'],
                'recurrence_type' => $item['is_recurring'] ? 'دورية' : 'وقتية',
                'recurrence_interval_days' => $item['is_recurring'] ? (int) $item['recurrence_interval_days'] : null,
                'execution_start_date' => $item['is_recurring'] ? $item['execution_start_date'] : null,
                'priority' => $item['priority'],
                'sort_order' => $index,
                'attachments' => $item['attachments'] ?? [],
            ];
        })->toArray();
    }

    public function render(): View
    {
        return view('livewire.pages.aid-requests.create', [
            'families' => $this->families,
            'selectedFamily' => $this->selectedFamily,
            'aidTypes' => $this->availableAidTypes,
            'aidRequestId' => $this->aidRequestId,
            'submitting' => $this->submitting,
            'items' => $this->items,
            'selectedAidType' => $this->selectedAidType,
            'draft' => $this->draft,
            'draftAttachments' => $this->draftAttachments,
            'acknowledged' => $this->acknowledged,
            'title' => $this->title,
            'description' => $this->description,
            'needed_by' => $this->needed_by,
            'internal_notes' => $this->internal_notes,
            'family_id' => $this->family_id,
        ]);
    }
}
