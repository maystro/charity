<?php

namespace App\Livewire\Fieldworkers;

use App\Models\Fieldworker;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'المندوبون والباحثون'])]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $governorate = '';

    public string $status = '';

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    public bool $showCreateModal = false;

    /** @var array<int, string> */
    protected $queryString = [
        'search' => ['except' => ''],
        'governorate' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGovernorate(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo', ['image', 'max:2048']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.code' => ['required', 'string', 'max:50', 'unique:fieldworkers,code'],
            'form.name' => ['required', 'string', 'max:150'],
            'form.phone' => ['nullable', 'string', 'max:20'],
            'form.governorate' => ['nullable', 'string', 'max:100'],
            'form.area' => ['nullable', 'string', 'max:150'],
            'form.status' => ['required', 'in:active,inactive'],
            'form.username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username'],
            'form.password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
            'form.password_confirmation' => ['required', 'string', 'min:6', 'max:100'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function openCreateModal(): void
    {
        $this->form = [
            'code' => 'FW-'.str_pad((string) (Fieldworker::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'name' => '',
            'phone' => '',
            'governorate' => '',
            'area' => '',
            'status' => 'active',
            'username' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->photo = null;
        $this->showCreateModal = true;
    }

    public function saveFieldworker(): void
    {
        $validated = $this->validate()['form'];

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['username'].'@charity.test',
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => User::ROLE_FIELDWORKER,
            ]);

            $photoPath = $this->storePhoto();

            if ($photoPath) {
                $user->update(['photo' => $photoPath]);
            }

            Fieldworker::create([
                'user_id' => $user->id,
                'code' => $validated['code'],
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?: null,
                'governorate' => $validated['governorate'] ?: null,
                'area' => $validated['area'] ?: null,
                'status' => $validated['status'],
            ]);
        });

        $this->dispatch('notify', message: "تمت إضافة المندوب «{$validated['name']}» بنجاح", type: 'success');
        $this->dispatch('close-modal', 'create-fieldworker');
        $this->showCreateModal = false;
        $this->form = [];
        $this->photo = null;
    }

    public function closeCreateModal(): void
    {
        $this->dispatch('close-modal', 'create-fieldworker');
        $this->showCreateModal = false;
        $this->form = [];
        $this->photo = null;
        $this->resetValidation();
    }

    public function render(): View
    {
        $fieldworkers = Fieldworker::query()
            ->with('user:id,name,email,username,photo,role')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('area', 'like', "%{$this->search}%");
                });
            })
            ->when($this->governorate, fn ($q) => $q->where('governorate', $this->governorate))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->withCount('families')
            ->orderBy('name')
            ->paginate(10);

        $governorates = Fieldworker::whereNotNull('governorate')
            ->where('governorate', '!=', '')
            ->distinct()
            ->orderBy('governorate')
            ->pluck('governorate', 'governorate')
            ->toArray();

        return view('livewire.pages.fieldworkers.index', [
            'fieldworkers' => $fieldworkers,
            'governorates' => $governorates,
        ]);
    }

    private function storePhoto(): ?string
    {
        if (! $this->photo instanceof TemporaryUploadedFile) {
            return null;
        }

        /** @var TemporaryUploadedFile $photo */
        $photo = $this->photo;

        $directory = 'fieldworkers/'.now()->format('Y/m');
        $filename = Str::random(40).'.'.$photo->guessExtension();

        return $photo->storeAs($directory, $filename, 'public');
    }
}
