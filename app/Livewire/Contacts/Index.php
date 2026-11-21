<?php

namespace App\Livewire\Contacts;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\User;
use App\Models\WahaSetting;
use App\Services\Waha\WahaClient;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Title('Kontak notifikasi')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'active';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $label = '';

    public ?int $userId = null;

    public bool $isActive = true;

    public string $notes = '';

    public function mount(): void
    {
        Gate::authorize('manage-contacts');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $contact = Contact::query()->findOrFail($id);

        $this->editingId = $contact->id;
        $this->name = $contact->name;
        $this->phone = $contact->phone_e164;
        $this->label = (string) $contact->label;
        $this->userId = $contact->user_id;
        $this->isActive = $contact->is_active;
        $this->notes = (string) $contact->notes;
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
        Gate::authorize('manage-contacts');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'label' => ['nullable', 'string', 'max:60'],
            'userId' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'isActive' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $normalized = PhoneNumber::normalize($validated['phone']);
        } catch (InvalidArgumentException $e) {
            $this->addError('phone', $e->getMessage());

            return;
        }

        $duplicate = Contact::withTrashed()
            ->where('phone_e164', $normalized)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();

        if ($duplicate) {
            $this->addError('phone', 'Nomor WhatsApp ini sudah dipakai kontak lain.');

            return;
        }

        $old = [];

        if ($this->editingId) {
            $contact = Contact::query()->findOrFail($this->editingId);
            $old = $contact->only(['name', 'phone_e164', 'label', 'user_id', 'is_active']);
        } else {
            $contact = new Contact;
        }

        $wasActive = $contact->is_active;

        $contact->fill([
            'name' => $validated['name'],
            'phone_e164' => $normalized,
            'label' => $validated['label'] ?: null,
            'user_id' => $validated['userId'],
            'is_active' => $validated['isActive'],
            'notes' => $validated['notes'] ?: null,
        ])->save();

        $action = $this->editingId
            ? (($wasActive && ! $contact->is_active) ? 'contact.deactivated' : 'contact.updated')
            : 'contact.created';

        ActivityLog::record(
            $action,
            ($this->editingId ? 'Mengubah' : 'Membuat').' kontak '.$contact->name,
            $contact,
            $old,
            $contact->only(['name', 'phone_e164', 'label', 'user_id', 'is_active']),
        );

        session()->flash('success', 'Kontak '.$contact->name.' berhasil disimpan.');
        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage-contacts');

        $contact = Contact::query()->findOrFail($id);
        $contact->forceFill(['is_active' => ! $contact->is_active])->save();

        ActivityLog::record(
            $contact->is_active ? 'contact.updated' : 'contact.deactivated',
            ($contact->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' kontak '.$contact->name,
            $contact,
            ['is_active' => ! $contact->is_active],
            ['is_active' => $contact->is_active],
        );

        session()->flash('success', 'Status kontak '.$contact->name.' diperbarui.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('delete-contacts');

        $contact = Contact::query()
            ->withCount(['categories', 'permits', 'deliveries'])
            ->findOrFail($id);

        if ($contact->categories_count > 0 || $contact->permits_count > 0 || $contact->deliveries_count > 0) {
            session()->flash('error', 'Kontak '.$contact->name.' sudah dipakai. Nonaktifkan kontak agar riwayat tetap utuh.');

            return;
        }

        $name = $contact->name;
        $contact->forceDelete();

        ActivityLog::record('contact.deleted', 'Menghapus kontak '.$name);

        session()->flash('success', 'Kontak '.$name.' dihapus.');
    }

    public function sendTest(int $id): void    {
        Gate::authorize('manage-contacts');

        $contact = Contact::query()->findOrFail($id);
        $settings = WahaSetting::current();

        if (! $settings->isConfigured()) {
            session()->flash('error', 'Konfigurasi WAHA belum lengkap. Hubungi admin.');

            return;
        }

        if (! $contact->is_active) {
            session()->flash('error', 'Aktifkan kontak sebelum mengirim pesan tes.');

            return;
        }

        try {
            (new WahaClient($settings))->sendText(
                $contact->phone_e164,
                "Pesan uji dari SIPERIZ.\n\nHalo {$contact->name}, koneksi WhatsApp untuk reminder izin perusahaan sudah berfungsi.",
            );

            session()->flash('success', 'Pesan tes dikirim ke '.$contact->name.'.');
        } catch (Throwable $e) {
            session()->flash('error', 'Pesan tes gagal: '.$e->getMessage());
        }
    }

    protected function resetForm(): void
    {
        $this->reset('editingId', 'name', 'phone', 'label', 'userId', 'isActive', 'notes');
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.contacts.index', [
            'contacts' => Contact::query()
                ->with('user')
                ->withCount(['categories', 'permits', 'deliveries'])
                ->when($this->filter === 'active', fn ($q) => $q->where('is_active', true))
                ->when($this->filter === 'inactive', fn ($q) => $q->where('is_active', false))
                ->when($this->search !== '', function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('phone_e164', 'like', '%'.$this->search.'%')
                            ->orWhere('label', 'like', '%'.$this->search.'%');
                    });
                })
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(15),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
            'wahaConfigured' => WahaSetting::current()->isConfigured(),
        ]);
    }
}
