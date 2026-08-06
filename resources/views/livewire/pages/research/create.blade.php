<?php

use App\Enums\FamilyStatus;
use App\Models\Family;
use App\Models\SocialResearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new
    #[Layout('layouts.app', ['title' => 'إنشاء بحث اجتماعي'])]
    class extends Component {
        public int $step = 1;

        public string $researchType = 'initial';

        public ?int $familyId = null;

        public ?string $conductedAt = null;

        public ?string $expiryDate = null;

        public ?string $eligibilityDegree = null;

        public string $averageIncome = '0';

        public string $netIncome = '0';

        public ?string $recommendation = null;

        public ?string $committeeDecision = null;

        public array $steps = [
            ['label' => 'بيانات البحث', 'description' => 'نوع البحث والأسرة'],
            ['label' => 'البيانات المالية', 'description' => 'الدخل والاستحقاق'],
            ['label' => 'التوصية', 'description' => 'التوصية والقرار'],
            ['label' => 'المراجعة', 'description' => 'مراجعة وحفظ'],
        ];

        public function with(): array
        {
            return ['families' => $this->families()];
        }

        public function nextStep(): void
        {
            $this->validateCurrentStep();

            if ($this->step < count($this->steps)) {
                $this->step++;
            }
        }

        public function prevStep(): void
        {
            if ($this->step > 1) {
                $this->step--;
            }
        }

        public function saveDraft(): void
        {
            $this->persist('draft');
        }

        public function submit(): void
        {
            $this->validate([
                'familyId' => ['required', 'exists:families,id'],
                'researchType' => ['required', 'in:initial,follow_up,assessment'],
                'conductedAt' => ['required', 'date'],
                'expiryDate' => ['nullable', 'date', 'after_or_equal:conductedAt'],
                'eligibilityDegree' => ['required', 'string', 'max:100'],
                'averageIncome' => ['required', 'numeric', 'min:0'],
                'netIncome' => ['required', 'numeric', 'min:0'],
                'recommendation' => ['required', 'string', 'max:2000'],
                'committeeDecision' => ['nullable', 'string', 'max:2000'],
            ]);

            $this->persist('approved');
        }

        private function persist(string $status): void
        {
            $this->validate([
                'familyId' => ['required', 'exists:families,id'],
                'researchType' => ['required', 'in:initial,follow_up,assessment'],
                'conductedAt' => ['nullable', 'date'],
                'expiryDate' => ['nullable', 'date', 'after_or_equal:conductedAt'],
                'averageIncome' => ['required', 'numeric', 'min:0'],
                'netIncome' => ['required', 'numeric', 'min:0'],
            ]);

            abort_unless($this->familyQuery()->whereKey($this->familyId)->exists(), 403);

            $research = DB::transaction(function () use ($status): SocialResearch {
                return SocialResearch::create([
                    'family_id' => $this->familyId,
                    'research_number' => $this->nextResearchNumber(),
                    'research_type' => $this->researchType,
                    'conducted_at' => $this->conductedAt,
                    'expiry_date' => $this->expiryDate,
                    'eligibility_degree' => $this->eligibilityDegree,
                    'average_income' => $this->averageIncome,
                    'net_income' => $this->netIncome,
                    'recommendation' => $this->recommendation,
                    'committee_decision' => $this->committeeDecision,
                    'status' => $status,
                    'created_by' => auth()->id(),
                    'approved_by' => $status === 'approved' ? auth()->id() : null,
                    'approved_at' => $status === 'approved' ? now() : null,
                ]);
            });

            $this->dispatch('notify', message: $status === 'approved' ? 'تم حفظ البحث واعتماده.' : 'تم حفظ البحث كمسودة.', type: 'success');
            $this->redirect(route('research.index'), navigate: true);
        }

        private function validateCurrentStep(): void
        {
            if ($this->step === 1) {
                $this->validate(['familyId' => ['required', 'exists:families,id'], 'researchType' => ['required', 'in:initial,follow_up,assessment'], 'conductedAt' => ['required', 'date']]);
            }

            if ($this->step === 2) {
                $this->validate(['eligibilityDegree' => ['required', 'string', 'max:100'], 'averageIncome' => ['required', 'numeric', 'min:0'], 'netIncome' => ['required', 'numeric', 'min:0']]);
            }

            if ($this->step === 3) {
                $this->validate(['recommendation' => ['required', 'string', 'max:2000']]);
            }
        }

        private function families(): array
        {
            return $this->familyQuery()->where('status', FamilyStatus::Approved->value)->orderBy('case_name')->get(['id', 'case_name', 'case_number'])->toArray();
        }

        private function familyQuery(): Builder
        {
            $user = auth()->user();

            if ($user?->isAdmin()) {
                return Family::query();
            }

            return Family::query()->where(fn (Builder $query) => $query->where('submitted_by', $user?->id)->orWhere('created_by', $user?->id)->orWhereHas('fieldworker', fn (Builder $fieldworker) => $fieldworker->where('user_id', $user?->id)));
        }

        private function nextResearchNumber(): string
        {
            do {
                $number = 'RES-'.now()->year.'-'.Str::upper(Str::random(6));
            } while (SocialResearch::where('research_number', $number)->exists());

            return $number;
        }
    };

?>

<div class="space-y-6">
    <x-layout.page-header title="إنشاء بحث اجتماعي" subtitle="تسجيل بحث ميداني مرتبط بأسرة معتمدة." :breadcrumbs="[['label' => 'البحوث الميدانية', 'route' => 'research.index'], ['label' => 'إنشاء بحث']]" />

    <x-ui.stepper :steps="$steps" :current="$step" />

    <x-ui.card padding>
        @if($step === 1)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="flex flex-col gap-1.5"><label class="text-sm font-medium">الأسرة</label><select wire:model="familyId" class="h-11 rounded-[var(--radius-md)] border border-[var(--color-border)] px-4"><option value="">اختر الأسرة</option>@foreach($families as $family)<option value="{{ $family['id'] }}">{{ $family['case_name'] }} ({{ $family['case_number'] }})</option>@endforeach</select>@error('familyId')<span class="text-xs text-red-500">{{ $message }}</span>@enderror</div>
                <x-ui.select label="نوع البحث" name="researchType" wire:model="researchType" :options="['initial' => 'بحث أولي', 'follow_up' => 'متابعة', 'assessment' => 'تقييم']" />
                <x-ui.input label="تاريخ الإجراء" name="conductedAt" type="date" wire:model="conductedAt" />
                <x-ui.input label="تاريخ انتهاء الصلاحية" name="expiryDate" type="date" wire:model="expiryDate" />
            </div>
        @elseif($step === 2)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3"><x-ui.input label="درجة الاستحقاق" name="eligibilityDegree" wire:model="eligibilityDegree" placeholder="مثال: مرتفع" /><x-ui.input label="متوسط الدخل" name="averageIncome" type="number" wire:model="averageIncome" /><x-ui.input label="صافي الدخل" name="netIncome" type="number" wire:model="netIncome" /></div>
        @elseif($step === 3)
            <div class="space-y-4"><x-ui.textarea label="توصية الباحث" name="recommendation" wire:model="recommendation" rows="5" /><x-ui.textarea label="قرار اللجنة" name="committeeDecision" wire:model="committeeDecision" rows="4" /></div>
        @else
            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"><div>الأسرة: {{ collect($families)->firstWhere('id', $familyId)['case_name'] ?? '—' }}</div><div>نوع البحث: {{ ['initial' => 'بحث أولي', 'follow_up' => 'متابعة', 'assessment' => 'تقييم'][$researchType] }}</div><div>الدخل: {{ $netIncome }}</div><div>درجة الاستحقاق: {{ $eligibilityDegree ?: '—' }}</div></div>
        @endif
    </x-ui.card>

    <div class="flex items-center justify-between"><div>@if($step > 1)<x-ui.button variant="ghost" wire:click="prevStep">السابق</x-ui.button>@endif</div><div class="flex gap-2"> <x-ui.button variant="secondary" wire:click="saveDraft">حفظ كمسودة</x-ui.button>@if($step < count($steps))<x-ui.button variant="primary" wire:click="nextStep">التالي</x-ui.button>@else<x-ui.button variant="success" wire:click="submit">إرسال واعتماد</x-ui.button>@endif</div></div>
</div>
