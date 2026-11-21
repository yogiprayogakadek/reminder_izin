<?php

namespace App\Livewire\Settings;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pengguna & akses')]
class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'secretary';

    public bool $isActive = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        Gate::authorize('manage-users');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'roleFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::query()->findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->role->value;
        $this->isActive = $user->is_active;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('manage-users');

        $this->email = mb_strtolower(trim($this->email));

        $editing = $this->editingId ? User::query()->findOrFail($this->editingId) : null;

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', Rule::unique('users', 'email')->ignore($editing?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'isActive' => ['boolean'],
            'password' => [$editing ? 'nullable' : 'required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ];

        $validated = $this->validate($rules, [
            'email.unique' => 'Email ini sudah dipakai pengguna lain.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($editing && $editing->id === auth()->id()) {
            if (! $validated['isActive']) {
                $this->addError('isActive', 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.');

                return;
            }

            if ($validated['role'] !== UserRole::Admin->value) {
                $this->addError('role', 'Anda tidak dapat menurunkan peran akun sendiri. Minta admin lain mengubahnya.');

                return;
            }
        }

        $isNew = $editing === null;
        $old = $editing ? $editing->only(['name', 'email', 'phone', 'role', 'is_active']) : [];

        $user = $editing ?? new User;
        $passwordChanged = filled($validated['password']);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'role' => $validated['role'],
            'is_active' => $validated['isActive'],
        ]);

        if ($passwordChanged) {
            $user->password = $validated['password'];
        }

        $user->save();

        ActivityLog::record(
            $isNew ? 'user.created' : ($passwordChanged ? 'user.password_reset' : 'user.updated'),
            ($isNew ? 'Membuat' : ($passwordChanged ? 'Mengatur ulang kata sandi' : 'Mengubah')).' pengguna '.$user->name,
            $user,
            $old,
            $user->only(['name', 'email', 'phone', 'role', 'is_active']),
        );

        session()->flash('success', 'Pengguna '.$user->name.' berhasil disimpan.');
        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage-users');

        $user = User::query()->findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.');

            return;
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        ActivityLog::record(
            $user->is_active ? 'user.updated' : 'user.deactivated',
            ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' pengguna '.$user->name,
            $user,
            ['is_active' => ! $user->is_active],
            ['is_active' => $user->is_active],
        );

        session()->flash('success', 'Status pengguna '.$user->name.' diperbarui.');
    }

    protected function resetForm(): void
    {
        $this->reset('editingId', 'name', 'email', 'phone', 'role', 'isActive', 'password', 'password_confirmation');
        $this->role = UserRole::Secretary->value;
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.settings.users', [
            'users' => User::query()
                ->withCount(['permits', 'createdPermits'])
                ->when($this->search !== '', function ($q) {
                    $term = '%'.$this->search.'%';
                    $q->where(function ($inner) use ($term) {
                        $inner->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    });
                })
                ->when($this->roleFilter !== '', fn ($q) => $q->where('role', $this->roleFilter))
                ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
                ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(15),
            'roles' => UserRole::cases(),
            'counts' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'admin' => User::query()->where('role', UserRole::Admin->value)->count(),
            ],
        ]);
    }
}
