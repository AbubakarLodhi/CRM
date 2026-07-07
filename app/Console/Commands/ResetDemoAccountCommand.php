<?php

namespace App\Console\Commands;

use App\Models\DemoVisitorSession;
use App\Services\Demo\TemporaryDemoCleanup;
use Illuminate\Console\Command;

class ResetDemoAccountCommand extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Clear all demo visitor sessions and temporary demo merchants';

    public function handle(TemporaryDemoCleanup $cleanup): int
    {
        $this->components->info('Resetting demo environments…');

        $cleanup->purgeAllTemporaryMerchants();
        DemoVisitorSession::query()->delete();

        $this->components->info('All demo visitor sessions and temporary merchants cleared.');

        return self::SUCCESS;
    }
}
