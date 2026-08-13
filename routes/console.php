<?php

use Illuminate\Support\Facades\Schedule;

// Sync all stores every 5 minutes
Schedule::command('stores:sync')->everyFiveMinutes()->withoutOverlapping();

// Health monitor every 5 minutes
Schedule::command('system:monitor')->everyFiveMinutes()->withoutOverlapping();

// Clean expired sessions every hour
Schedule::command('sessions:clean')->hourly();

// Process queued jobs
Schedule::command('queue:work --stop-when-empty --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('orders:sync')->everyFiveMinutes();

Schedule::command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();
