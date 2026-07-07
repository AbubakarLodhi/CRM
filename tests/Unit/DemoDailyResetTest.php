<?php

namespace Tests\Unit;

use App\Models\DemoVisitorSession;
use App\Support\DemoAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoDailyResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_demo_allowed_after_daily_reset_time(): void
    {
        config(['demo.daily_reset_at' => '17:00']);

        $this->travelTo(now()->setTime(17, 30));

        $session = DemoVisitorSession::query()->create([
            'id' => Str::uuid()->toString(),
            'visitor_hash' => 'hash',
            'ip_address' => '127.0.0.1',
            'started_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
        ]);

        $this->assertTrue(DemoAccount::canStartFreshDemoAfterReset($session));
    }

    public function test_fresh_demo_blocked_before_daily_reset_on_same_day(): void
    {
        config(['demo.daily_reset_at' => '17:00']);

        $this->travelTo(now()->setTime(16, 0));

        $session = DemoVisitorSession::query()->create([
            'id' => Str::uuid()->toString(),
            'visitor_hash' => 'hash',
            'ip_address' => '127.0.0.1',
            'started_at' => now()->subHours(2),
            'expires_at' => now()->subMinutes(30),
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $this->assertFalse(DemoAccount::canStartFreshDemoAfterReset($session));
    }
}
