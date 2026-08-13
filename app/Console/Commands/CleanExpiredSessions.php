<?php

namespace App\Console\Commands;

use App\Models\CheckoutSession;
use Illuminate\Console\Command;

class CleanExpiredSessions extends Command
{
    protected $signature   = 'sessions:clean';
    protected $description = 'Clean expired checkout sessions';

    public function handle(): void
    {
        $deleted = CheckoutSession::where('expires_at', '<', now())
            ->where('status', 'pending')
            ->delete();

        $this->info("Cleaned {$deleted} expired sessions");
    }
}