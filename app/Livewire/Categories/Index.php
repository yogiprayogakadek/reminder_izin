<?php

namespace App\Livewire\Categories;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\PermitCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Kategori izin')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $color = '#0369A1';

    public ?int $defaultValidityMonths = null;

    public ?int $defaultPicId = null;

    public array $recipientIds = [];

    public bool $isActive = true;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('manage-categories');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $category = PermitCategory::query()->with('recipients')->findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = (string) $category->description;
        $this->color = $category->color;
        $this->defaultValidityMonths = $category->default_validity_months;
        $this->defaultPicId = $category->default_pic_user_id;
        $this->recipientIds = $category->recipients->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->isActive = $category->is_active;
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
        Gate::authorize('manage-categories');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'defaultValidityMonths' => ['nullable', 'integer', 'min:1', 'max:120'],
            'defaultPicId' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'recipientIds' => ['array'],
            'recipientIds.*' => ['integer', Rule::exists('contacts', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'isActive' => ['boolean'],
        ]);

        $old = [];

        if ($this->editingId) {
            $category = PermitCategory::query()->findOrFail($this->editingId);
            $old = $category->only(['name', 'description', 'color', 'default_validity_months', 'default_pic_user_id', 'is_active']);
        } else {
            $category = new PermitCategory;
        }

        $category->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'color' => strtoupper($validated['color']),
            'default_validity_months' => $validated['defaultValidityMonths'],
            'default_pic_user_id' => $validated['defaultPicId'],
            'is_active' => $validated['isActive'],
        ])->save();

        $category->recipients()->sync(array_map('intval', $this->recipientIds));

        ActivityLog::record(
            $this->editingId ? 'category.updated' : 'category.created',
            ($this->editingId ? 'Mengubah' : 'Membuat').' kategori '.$category->name,
            $category,
            $old,
            $category->only(['name', 'description', 'color', 'default_validity_months', 'default_pic_user_id', 'is_active']),
        );

        session()->flash('success', 'Kategori '.$category->name.' berhasil disimpan.');

        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage-categories');

        $category = PermitCategory::query()->findOrFail($id);
        $category->forceFill(['is_active' => ! $category->is_active])->save();

        ActivityLog::record(
            $category->is_active ? 'category.updated' : 'category.deactivated',
            ($category->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' kategori '.$category->name,
            $category,
            ['is_active' => ! $category->is_active],
            ['is_active' => $category->is_active],
        );

        session()->flash('success', 'Status kategori '.$category->name.' diperbarui.');
    }

    protected function resetForm(): void
    {
        $this->reset('editingId', 'name', 'description', 'color', 'defaultValidityMonths', 'defaultPicId', 'recipientIds', 'isActive');
        $this->color = '#0369A1';
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.categories.index', [
            'categories' => PermitCategory::query()
                ->with('defaultPic')
                ->withCount(['permits', 'recipients'])
                ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'contacts' => Contact::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'label']),
            'pics' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
