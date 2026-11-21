<?php

namespace App\Services\Reminders;

use App\Models\ReminderDelivery;
use App\Notifications\PermitReminderNotification;

class ReminderNotifier
{
    public function notify(ReminderDelivery $delivery): void
    {
        $delivery->loadMissing(['contact.user', 'permit']);

        $user = $delivery->contact?->user;
        $permit = $delivery->permit;

        if (! $user || ! $permit) {
            return;
        }

        $user->notify(new PermitReminderNotification(
            permitId: $permit->id,
            title: 'Reminder izin: '.$permit->title,
            body: 'Izin '.$permit->title.' '.$permit->remainingLabel().' (kedaluwarsa '.$permit->expiryLabel().').',
            deliveryId: $delivery->id,
        ));
    }
}
