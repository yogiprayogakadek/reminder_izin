<?php

namespace App\Livewire\Permits;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\PermitFile;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Formulir izin')]
class Form extends Component
{
    use WithFileUploads;

    public ?int $permitId = null;

    public bool $editing = false;

    public ?int $categoryId = null;

    public string $title = '';

    public string $number = '';

    public string $issuer = '';

    public string $issuedAt = '';

    public string $expiresAt = '';

    public ?int $picId = null;

    public string $location = '';

    public string $notes = '';

    public bool $reminderEnabled = true;

    public string $reminderPausedUntil = '';

    public array $recipientIds = [];

    public $document = null;

    public bool $removeExistingFile = false;

    public function mount(?Permit $permit = null): void
    {
        Gate::authorize('manage-permits');

        if ($permit?->exists) {
            if ($permit->archived_at !== null) {
                session()->flash('error', 'Izin yang sudah diarsipkan tidak dapat diubah. Keluarkan dari arsip terlebih dahulu.');

                $this->redirectRoute('permits.show', $permit, navigate: true);

                return;
            }

            $this->permitId = $permit->id;
            $this->editing = true;
            $permit->load(['recipients', 'currentFiles']);

            $this->categoryId = $permit->category_id;
            $this->title = $permit->title;
            $this->number = (string) $permit->number;
            $this->issuer = $permit->issuer;
            $this->issuedAt = $permit->issued_at?->toDateString() ?? '';
            $this->expiresAt = $permit->expires_at->toDateString();
            $this->picId = $permit->pic_user_id;
            $this->location = (string) $permit->location;
            $this->notes = (string) $permit->notes;
            $this->reminderEnabled = $permit->reminder_enabled;
            $this->reminderPausedUntil = $permit->reminder_paused_until?->toDateString() ?? '';
            $this->recipientIds = $permit->recipients->pluck('id')->map(fn ($id) => (string) $id)->all();

            return;
        }

        $this->issuedAt = CarbonImmutable::today()->toDateString();

        $categories = PermitCategory::query()->active()->orderBy('name')->get();

        if ($categories->count() === 1) {
            $this->categoryId = $categories->first()->id;
            $this->applyCategoryDefaults();
        }
    }

    public function updatedCategoryId(): void
    {
        if (! $this->editing) {
            $this->applyCategoryDefaults();
        }
    }

    public function applyCategoryDefaults(): void
    {
        if (! $this->categoryId) {
            return;
        }

        $category = PermitCategory::query()->with('recipients')->find($this->categoryId);

        if (! $category) {
            return;
        }

        if ($category->default_pic_user_id) {
            $this->picId = $category->default_pic_user_id;
        }

        $this->recipientIds = $category->recipients
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($this->issuedAt && $category->default_validity_months) {
            $this->expiresAt = CarbonImmutable::parse($this->issuedAt)
                ->addMonthsNoOverflow($category->default_validity_months)
                ->toDateString();
        }
    }

    public function save(): void
    {
        Gate::authorize('manage-permits');

        $maxKb = max(1, (int) Setting::get('file_max_mb', 10)) * 1024;

        $validated = $this->validate([
            'categoryId' => ['required', 'integer', Rule::exists('permit_categories', 'id')],
            'title' => ['required', 'string', 'max:180'],
            'number' => ['nullable', 'string', 'max:100'],
            'issuer' => ['required', 'string', 'max:150'],
            'issuedAt' => ['nullable', 'date', 'before_or_equal:expiresAt'],
            'expiresAt' => ['required', 'date'],
            'picId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'location' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reminderEnabled' => ['boolean'],
            'reminderPausedUntil' => ['nullable', 'date'],
            'recipientIds' => ['array'],
            'recipientIds.*' => ['integer', Rule::exists('contacts', 'id')],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxKb],
            'removeExistingFile' => ['boolean'],
        ], [
            'document.mimes' => 'Dokumen harus berformat PDF, JPG, atau PNG.',
            'document.max' => 'Ukuran dokumen maksimal '.(int) Setting::get('file_max_mb', 10).' MB.',
        ]);

        $category = PermitCategory::query()->findOrFail($validated['categoryId']);
        $permit = $this->permitId ? Permit::query()->findOrFail($this->permitId) : null;

        if (! $category->is_active && ($permit === null || $permit->category_id !== $category->id)) {
            $this->addError('categoryId', 'Kategori tidak aktif dan tidak dapat dipilih untuk izin baru.');

            return;
        }

        $old = $permit ? $permit->only(['category_id', 'title', 'number', 'issuer', 'issued_at', 'expires_at', 'pic_user_id', 'location', 'notes', 'reminder_enabled', 'reminder_paused_until']) : [];

        if ($permit && $this->document && $permit->renewals()->exists() && ! auth()->user()->isAdmin()) {
            $this->addError('document', 'File izin yang sudah memiliki riwayat perpanjangan hanya dapat diganti oleh Admin.');

            return;
        }

        if ($permit && $this->removeExistingFile && $permit->renewals()->exists() && ! auth()->user()->isAdmin()) {
            $this->addError('removeExistingFile', 'File izin yang sudah memiliki riwayat perpanjangan hanya dapat dihapus oleh Admin.');

            return;
        }

        if (! $permit) {
            $permit = new Permit;
            $permit->created_by = auth()->id();
        }

        $permit->fill([
            'category_id' => $validated['categoryId'],
            'title' => $validated['title'],
            'number' => $validated['number'] ?: null,
            'issuer' => $validated['issuer'],
            'issued_at' => $validated['issuedAt'] ?: null,
            'expires_at' => $validated['expiresAt'],
            'pic_user_id' => $validated['picId'],
            'location' => $validated['location'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'reminder_enabled' => $validated['reminderEnabled'],
            'reminder_paused_until' => $validated['reminderPausedUntil'] ?: null,
        ])->save();

        $permit->recipients()->sync(array_map('intval', $this->recipientIds));

        if ($this->document) {
            $this->deleteCurrentFiles($permit);
            $this->storeDocument($permit);
        } elseif ($this->removeExistingFile) {
            $this->deleteCurrentFiles($permit);
        }

        ActivityLog::record(
            $this->editing ? 'permit.updated' : 'permit.created',
            ($this->editing ? 'Mengubah' : 'Membuat').' izin '.$permit->title,
            $permit,
            $old,
            $permit->only(['category_id', 'title', 'number', 'issuer', 'issued_at', 'expires_at', 'pic_user_id', 'location', 'notes', 'reminder_enabled', 'reminder_paused_until']),
        );

        session()->flash('success', 'Izin '.$permit->title.' berhasil '.($this->editing ? 'diperbarui' : 'ditambahkan').'.');

        $this->redirectRoute('permits.show', $permit, navigate: true);
    }

    protected function storeDocument(Permit $permit): void
    {
        $path = $this->document->store('permits/'.$permit->id, 'local');

        PermitFile::query()->create([
            'permit_id' => $permit->id,
            'path' => $path,
            'original_name' => $this->document->getClientOriginalName(),
            'mime_type' => $this->document->getMimeType() ?: 'application/octet-stream',
            'size' => $this->document->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    protected function deleteCurrentFiles(Permit $permit): void
    {
        foreach ($permit->currentFiles()->get() as $file) {
            Storage::disk('local')->delete($file->path);
            $file->delete();
        }
    }

    public function render(): View
    {
        $permit = $this->permitId ? Permit::query()->with('currentFiles')->find($this->permitId) : null;

        $categories = PermitCategory::query()
            ->where(function ($query) use ($permit) {
                $query->where('is_active', true);

                if ($permit) {
                    $query->orWhere('id', $permit->category_id);
                }
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $contacts = Contact::query()
            ->where(function ($query) {
                $query->where('is_active', true);

                if ($this->recipientIds !== []) {
                    $query->orWhereIn('id', $this->recipientIds);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'is_active']);

        $pics = User::query()
            ->where(function ($query) use ($permit) {
                $query->where('is_active', true);

                if ($permit?->pic_user_id) {
                    $query->orWhere('id', $permit->pic_user_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $selectedCategory = $this->categoryId ? $categories->firstWhere('id', $this->categoryId) : null;

        return view('livewire.permits.form', [
            'permit' => $permit,
            'categories' => $categories,
            'contacts' => $contacts,
            'pics' => $pics,
            'selectedCategory' => $selectedCategory,
            'fileMaxMb' => (int) Setting::get('file_max_mb', 10),
        ]);
    }
}
