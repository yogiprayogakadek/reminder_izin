<?php

namespace App\Livewire\Notifications;

use App\Models\ActivityLog;
use App\Models\PermitCategory;
use App\Models\ReminderTemplate;
use App\Services\Reminders\ReminderMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Template pesan')]
class Templates extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $categoryId = '';

    public string $name = '';

    public string $body = '';

    public bool $isActive = true;

    public function mount(): void
    {
        Gate::authorize('manage-reminders');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->body = ReminderTemplate::defaultBody();
        $this->name = 'Reminder standar';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $template = ReminderTemplate::query()->findOrFail($id);

        $this->editingId = $template->id;
        $this->categoryId = $template->category_id ? (string) $template->category_id : '';
        $this->name = $template->name;
        $this->body = $template->body;
        $this->isActive = $template->is_active;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function useDefaultBody(): void
    {
        $this->body = ReminderTemplate::defaultBody();
    }

    public function insertPlaceholder(string $placeholder): void
    {
        $allowed = array_keys(ReminderMessage::previewData());

        if (! in_array($placeholder, $allowed, true)) {
            return;
        }

        $this->body = rtrim($this->body).' '.$placeholder.' ';
    }

    public function save(): void
    {
        Gate::authorize('manage-reminders');

        $validated = $this->validate([
            'categoryId' => ['nullable', 'integer', Rule::exists('permit_categories', 'id')],
            'name' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:5000'],
            'isActive' => ['boolean'],
        ], [
            'body.required' => 'Isi pesan template terlebih dahulu.',
        ]);

        $old = [];

        if ($this->editingId) {
            $template = ReminderTemplate::query()->findOrFail($this->editingId);
            $old = $template->only(['category_id', 'name', 'body', 'is_active']);
        } else {
            $template = new ReminderTemplate;
        }

        $template->fill([
            'category_id' => $validated['categoryId'] ?: null,
            'name' => $validated['name'],
            'body' => trim($validated['body']),
            'is_active' => $validated['isActive'],
        ])->save();

        ActivityLog::record(
            $this->editingId ? 'reminder.template_updated' : 'reminder.template_created',
            ($this->editingId ? 'Mengubah' : 'Membuat').' template reminder '.$template->name,
            $template,
            $old,
            $template->only(['category_id', 'name', 'body', 'is_active']),
        );

        session()->flash('success', 'Template '.$template->name.' berhasil disimpan.');
        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage-reminders');

        $template = ReminderTemplate::query()->findOrFail($id);

        if ($template->is_active && ReminderTemplate::query()->where('is_active', true)->count() <= 1) {
            session()->flash('error', 'Minimal satu template aktif harus tersedia. Tambahkan template lain sebelum menonaktifkan yang ini.');

            return;
        }

        $template->forceFill(['is_active' => ! $template->is_active])->save();

        ActivityLog::record(
            'reminder.template_updated',
            ($template->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' template '.$template->name,
            $template,
            ['is_active' => ! $template->is_active],
            ['is_active' => $template->is_active],
        );

        session()->flash('success', 'Status template '.$template->name.' diperbarui.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-reminders');

        $template = ReminderTemplate::query()->findOrFail($id);

        if ($template->is_active && ReminderTemplate::query()->where('is_active', true)->count() <= 1) {
            session()->flash('error', 'Minimal satu template aktif harus tersedia. Nonaktifkan template lain terlebih dahulu.');

            return;
        }

        $name = $template->name;
        $template->delete();

        ActivityLog::record('reminder.template_deleted', 'Menghapus template reminder '.$name);

        session()->flash('success', 'Template '.$name.' dihapus.');
    }

    protected function resetForm(): void
    {
        $this->reset('editingId', 'categoryId', 'name', 'body', 'isActive');
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $previewBody = trim($this->body) !== '' ? $this->body : ReminderTemplate::defaultBody();

        return view('livewire.notifications.templates', [
            'templates' => ReminderTemplate::query()
                ->with('category')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'categories' => PermitCategory::query()->orderBy('name')->get(['id', 'name', 'is_active']),
            'preview' => strtr($previewBody, ReminderMessage::previewData()),
            'placeholders' => ReminderMessage::previewData(),
            'activeCount' => ReminderTemplate::query()->where('is_active', true)->count(),
        ]);
    }
}
