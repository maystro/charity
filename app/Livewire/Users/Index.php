<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'المستخدمون والصلاحيات'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public bool $showForm = false;

    public ?int $editingUserId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<string, string> */
    public array $permissionOptions = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->permissionOptions = Navigation::permissionOptions();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->editingUserId = null;
        $this->form = $this->defaultForm();
        $this->showForm = true;

        $this->dispatch('open-modal', 'user-form');
    }

    public function openEditModal(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'role' => $user->role,
            'password' => '',
            'password_confirmation' => '',
            'menu_abilities' => $user->menuAbilities(),
        ];
        $this->showForm = true;

        $this->dispatch('open-modal', 'user-form');
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules())['form'];

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'menu_abilities' => $validated['role'] === User::ROLE_USER
                ? array_values(array_unique(array_filter($validated['menu_abilities'] ?? [])))
                : [],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $user->fill($data);
            $user->save();
            $message = 'تم تحديث المستخدم بنجاح.';
        } else {
            User::query()->create($data);
            $message = 'تمت إضافة المستخدم بنجاح.';
        }

        $this->resetForm();
        $this->dispatch('close-modal', 'user-form');
        $this->dispatch('notify', message: $message, type: 'success');
    }

    public function delete(int $userId): void
    {
        $currentUserId = auth()->id();

        if ($currentUserId === $userId) {
            $this->dispatch('notify', message: 'لا يمكن حذف حسابك الحالي.', type: 'warning');

            return;
        }

        $user = User::query()->findOrFail($userId);

        if ($user->isAdmin()) {
            $adminCount = User::query()->where('role', User::ROLE_ADMIN)->count();

            if ($adminCount <= 1) {
                $this->dispatch('notify', message: 'لا يمكن حذف آخر مدير في النظام.', type: 'warning');

                return;
            }
        }

        $user->delete();

        if ($this->editingUserId === $userId) {
            $this->resetForm();
        }

        $this->dispatch('notify', message: 'تم حذف المستخدم بنجاح.', type: 'success');
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->dispatch('close-modal', 'user-form');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $userId = $this->editingUserId;

        $permissionKeys = array_keys($this->permissionOptions);

        $rules = [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'form.username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($userId)],
            'form.role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
            'form.password' => [$userId ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'form.password_confirmation' => [$userId ? 'nullable' : 'required', 'string', 'min:6'],
            'form.menu_abilities' => ['nullable', 'array'],
            'form.menu_abilities.*' => ['string', Rule::in($permissionKeys)],
        ];

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultForm(): array
    {
        return [
            'name' => '',
            'email' => '',
            'username' => '',
            'role' => User::ROLE_USER,
            'password' => '',
            'password_confirmation' => '',
            'menu_abilities' => [],
        ];
    }

    protected function resetForm(): void
    {
        $this->editingUserId = null;
        $this->form = $this->defaultForm();
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::query()
            ->with('fieldworker:id,user_id,code,name,governorate,status')
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_USER])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($subquery): void {
                    $subquery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('username', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->roleFilter !== '', fn ($query) => $query->where('role', $this->roleFilter))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.pages.users.index', [
            'users' => $users,
            'permissionGroups' => Navigation::permissionGroups(),
            'permissionLabels' => Navigation::permissionOptions(),
        ]);
    }
}
