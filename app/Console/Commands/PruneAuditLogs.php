<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Menghapus audit log yang lebih lama dari masa retensi.';

    public function handle(): int
    {
        $days = max(7, (int) Setting::get('log_retention_days', 90));
        $deleted = ActivityLog::query()->where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Audit log dihapus: {$deleted} baris (retensi {$days} hari).");

        return self::SUCCESS;
    }
}
