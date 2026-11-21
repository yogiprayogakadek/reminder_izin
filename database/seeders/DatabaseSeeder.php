<?php

namespace Database\Seeders;

use App\Models\ReminderRule;
use App\Models\ReminderTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $admin = User::query()->firstOrCreate(
            ['email' => env('SIPERIZ_ADMIN_EMAIL', 'admin@siperiz.test')],
            [
                'name' => env('SIPERIZ_ADMIN_NAME', 'Administrator'),
                'password' => env('SIPERIZ_ADMIN_PASSWORD', 'Admin#Siperiz2026'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->wasRecentlyCreated) {
            $admin->forceFill(['role' => 'admin', 'is_active' => true])->save();
        }

        $this->seedReminderRules();
        $this->seedTemplate();
    }

    private function seedReminderRules(): void
    {
        if (ReminderRule::query()->exists()) {
            return;
        }

        $sendTime = Setting::get('reminder_time', '08:00');

        foreach ([30, 14, 7, 3, 1, 0] as $offset) {
            ReminderRule::query()->create([
                'category_id' => null,
                'offset_days' => $offset,
                'send_time' => $sendTime.':00',
                'repeat_overdue_days' => $offset === 0 ? 3 : null,
                'max_repeats' => $offset === 0 ? 5 : null,
                'is_active' => true,
            ]);
        }
    }

    private function seedTemplate(): void
    {
        if (ReminderTemplate::query()->exists()) {
            return;
        }

        ReminderTemplate::query()->create([
            'category_id' => null,
            'name' => 'Reminder standar',
            'body' => ReminderTemplate::defaultBody(),
            'is_active' => true,
        ]);
    }
}
