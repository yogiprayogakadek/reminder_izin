<?php

namespace App\Livewire\Permits;

use App\Models\Permit;
use App\Models\PermitCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Daftar izin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategori', except: '')]
    public string $categoryId = '';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    #[Url(as: 'pic', except: '')]
    public string $picId = '';

    #[Url(as: 'dari', except: '')]
    public string $from = '';

    #[Url(as: 'sampai', except: '')]
    public string $to = '';

    #[Url(as: 'urut', except: 'expires_asc')]
    public string $sort = 'expires_asc';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'categoryId', 'status', 'picId', 'from', 'to', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'categoryId', 'status', 'picId', 'from', 'to', 'sort');
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Permit::query()
            ->with(['category', 'pic'])
            ->when($this->status === 'all', fn ($q) => $q->whereNull('archived_at'))
            ->when($this->status === 'archived', fn ($q) => $q->whereNotNull('archived_at'))
            ->when(! in_array($this->status, ['all', 'archived'], true), function ($q) {
                $q->whereNull('archived_at')->status($this->status);
            })
            ->when($this->categoryId !== '', fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->picId !== '', fn ($q) => $q->where('pic_user_id', $this->picId))
            ->when($this->from !== '', fn ($q) => $q->whereDate('expires_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('expires_at', '<=', $this->to))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('number', 'like', $term)
                        ->orWhere('issuer', 'like', $term)
                        ->orWhere('location', 'like', $term)
                        ->orWhereHas('pic', fn ($pic) => $pic->where('name', 'like', $term));
                });
            });

        $query = match ($this->sort) {
            'expires_desc' => $query->orderByDesc('expires_at'),
            'name' => $query->orderBy('title'),
            'updated' => $query->orderByDesc('updated_at'),
            default => $query->orderBy('expires_at')->orderBy('title'),
        };

        return view('livewire.permits.index', [
            'permits' => $query->paginate(12)->withQueryString(),
            'categories' => PermitCategory::query()->orderBy('name')->get(['id', 'name', 'is_active']),
            'pics' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'counts' => [
                'all' => Permit::query()->whereNull('archived_at')->count(),
                'active' => Permit::query()->status('active')->count(),
                'expiring' => Permit::query()->status('expiring')->count(),
                'expired' => Permit::query()->status('expired')->count(),
                'renewing' => Permit::query()->status('renewing')->count(),
                'archived' => Permit::query()->whereNotNull('archived_at')->count(),
            ],
            'hasActiveCategories' => PermitCategory::query()->where('is_active', true)->exists(),
            'hasFilters' => $this->search !== ''
                || $this->categoryId !== ''
                || $this->status !== 'all'
                || $this->picId !== ''
                || $this->from !== ''
                || $this->to !== ''
                || $this->sort !== 'expires_asc',
        ]);
    }
}
