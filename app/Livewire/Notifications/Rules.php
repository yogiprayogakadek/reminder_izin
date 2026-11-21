<?php

namespace App\Livewire\Notifications;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\PermitCategory;
use App\Models\ReminderRule;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Aturan reminder')]
class Rules extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $categoryId = '';

    public string $offsetDays = '7';

    public string $sendTime = '08:00';

    public ?int $repeatOverdueDays = null;

    public ?int $maxRepeats = null;

    public bool $isActive = true;

    public array $recipientIds = [];

    public function mount(): void
    {
        Gate::authorize('manage-reminders');
    }

    public function openCreate(?int $categoryId = null): void
    {
        $this->resetForm();
        $this->categoryId = $categoryId ? (string) $categoryId : '';
        $this->sendTime = substr((string) Setting::get('reminder_time', '08:00'), 0, 5);
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $rule = ReminderRule::query()->with('recipients')->findOrFail($id);

        $this->editingId = $rule->id;
        $this->categoryId = $rule->category_id ? (string) $rule->category_id : '';
        $this->offsetDays = (string) $rule->offset_days;
        $this->sendTime = substr((string) $rule->send_time, 0, 5);
        $this->repeatOverdueDays = $rule->repeat_overdue_days;
        $this->maxRepeats = $rule->max_repeats;
        $this->isActive = $rule->is_active;
        $this->recipientIds = $rule->recipients->pluck('id')->map(fn ($id) => (string) $id)->all();
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
        Gate::authorize('manage-reminders');

        $validated = $this->validate([
            'categoryId' => ['nullable', 'integer', Rule::exists('permit_categories', 'id')],
            'offsetDays' => ['required', 'integer', 'min:0', 'max:365'],
            'sendTime' => ['required', 'date_format:H:i'],
            'repeatOverdueDays' => ['nullable', 'integer', 'min:1', 'max:60'],
            'maxRepeats' => ['nullable', 'integer', 'min:1', 'max:20'],
            'isActive' => ['boolean'],
            'recipientIds' => ['array'],
            'recipientIds.*' => ['integer', Rule::exists('contacts', 'id')->whereNull('deleted_at')],
        ], [
            'offsetDays.required' => 'Isi jarak hari sebelum kedaluwarsa.',
            'sendTime.date_format' => 'Format jam harus HH:MM.',
        ]);

        $categoryId = $validated['categoryId'] ?: null;

        $duplicate = ReminderRule::query()
            ->where('offset_days', (int) $validated['offsetDays'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId), fn ($q) => $q->whereNull('category_id'))
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();

        if ($duplicate) {
            $this->addError('offsetDays', 'Aturan untuk H-'.(int) $validated['offsetDays'].' pada cakupan ini sudah ada. Ubah aturan tersebut.');

            return;
        }

        if (($validated['repeatOverdueDays'] === null) !== ($validated['maxRepeats'] === null)) {
            $this->addError('repeatOverdueDays', 'Isi interval dan batas pengulangan sekaligus, atau kosongkan keduanya.');

            return;
        }

        $old = [];

        if ($this->editingId) {
            $rule = ReminderRule::query()->findOrFail($this->editingId);
            $old = $rule->only(['category_id', 'offset_days', 'send_time', 'repeat_overdue_days', 'max_repeats', 'is_active']);
        } else {
            $rule = new ReminderRule;
        }

        $rule->fill([
            'category_id' => $categoryId,
            'offset_days' => (int) $validated['offsetDays'],
            'send_time' => $validated['sendTime'].':00',
            'repeat_overdue_days' => $validated['repeatOverdueDays'],
            'max_repeats' => $validated['maxRepeats'],
            'is_active' => $validated['isActive'],
        ])->save();

        $rule->recipients()->sync(array_map('intval', $this->recipientIds));

        ActivityLog::record(
            $this->editingId ? 'reminder.rule_updated' : 'reminder.rule_created',
            ($this->editingId ? 'Mengubah' : 'Membuat').' aturan reminder '.$rule->label().($rule->category ? ' untuk '.$rule->category->name : ' global'),
            $rule,
            $old,
            $rule->only(['category_id', 'offset_days', 'send_time', 'repeat_overdue_days', 'max_repeats', 'is_active']),
        );

        session()->flash('success', 'Aturan reminder '.$rule->label().' disimpan.');
        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage-reminders');

        $rule = ReminderRule::query()->findOrFail($id);
        $rule->forceFill(['is_active' => ! $rule->is_active])->save();

        ActivityLog::record(
            'reminder.rule_updated',
            ($rule->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' aturan reminder '.$rule->label(),
            $rule,
            ['is_active' => ! $rule->is_active],
            ['is_active' => $rule->is_active],
        );

        session()->flash('success', 'Status aturan '.$rule->label().' diperbarui.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-reminders');

        $rule = ReminderRule::query()->findOrFail($id);
        $label = $rule->label().($rule->category ? ' ('.$rule->category->name.')' : ' global');
        $rule->delete();

        ActivityLog::record('reminder.rule_deleted', 'Menghapus aturan reminder '.$label);

        session()->flash('success', 'Aturan reminder '.$label.' dihapus.');
    }

    protected function resetForm(): void
    {
        $this->reset('editingId', 'categoryId', 'offsetDays', 'sendTime', 'repeatOverdueDays', 'maxRepeats', 'isActive', 'recipientIds');
        $this->offsetDays = '7';
        $this->sendTime = '08:00';
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $rules = ReminderRule::query()
            ->with(['category', 'recipients'])
            ->orderByDesc('offset_days')
            ->get();

        return view('livewire.notifications.rules', [
            'globalRules' => $rules->whereNull('category_id')->sortByDesc('offset_days')->values(),
            'categoryRules' => $rules->whereNotNull('category_id')->groupBy('category_id')->sortKeys(),
            'categories' => PermitCategory::query()->withTrashed()->orderBy('name')->get(['id', 'name', 'color', 'is_active', 'deleted_at']),
            'activeCategories' => PermitCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'label']),
            'workdaysOnly' => Setting::get('workdays_only') === '1',
            'skipHolidays' => Setting::get('skip_holidays') === '1',
        ]);
    }
}
