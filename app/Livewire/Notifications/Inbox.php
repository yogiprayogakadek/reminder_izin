<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Notifikasi saya')]
class Inbox extends Component
{
    use WithPagination;

    public function markRead(string $id): void
    {
        $notification = auth()->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();

            if (isset($notification->data['permit_id'])) {
                $this->redirectRoute('permits.show', ['permit' => $notification->data['permit_id']], navigate: true);
            }
        }
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->dispatch('notifications-read');
    }

    public function render(): View
    {
        return view('livewire.notifications.inbox', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(15),
        ]);
    }
}
