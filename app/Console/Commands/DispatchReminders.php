<?php

namespace App\Console\Commands;

use App\Services\Reminders\ReminderDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class DispatchReminders extends Command
{
    protected $signature = 'reminders:dispatch {--date= : Tanggal simulasi dengan format YYYY-MM-DD}';

    protected $description = 'Membuat antrean reminder izin yang jatuh tempo hari ini.';

    public function handle(ReminderDispatcher $dispatcher): int
    {
        $today = $this->option('date')
            ? CarbonImmutable::parse($this->option('date'))->startOfDay()
            : null;

        $summary = $dispatcher->dispatchDue($today);

        $this->info(sprintf(
            'Reminder dibuat: %d, duplikat dilewati: %d%s',
            $summary['created'],
            $summary['duplicate'],
            $summary['reason'] ? ', alasan: '.$summary['reason'] : '',
        ));

        return self::SUCCESS;
    }
}
