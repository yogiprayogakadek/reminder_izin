<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:dispatch')->everyMinute()->withoutOverlapping(10);
Schedule::command('audit:prune')->dailyAt('02:30');
