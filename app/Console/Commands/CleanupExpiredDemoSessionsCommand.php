<?php

namespace App\Console\Commands;

use App\Services\Demo\TemporaryDemoCleanup;
use Illuminate\Console\Command;

class CleanupExpiredDemoSessionsCommand extends Command
{
    protected $signature = 'demo:cleanup-expired';

    protected $description = 'Delete temporary demo merchants for expired visitor sessions';

    public function handle(TemporaryDemoCleanup $cleanup): int
    {
        $purged = $cleanup->purgeExpiredSessions();

        if ($purged > 0) {
            $this->components->info("Purged {$purged} expired demo merchant(s).");
        }

        return self::SUCCESS;
    }
}
