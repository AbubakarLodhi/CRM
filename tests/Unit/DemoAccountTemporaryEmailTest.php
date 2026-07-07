<?php

namespace Tests\Unit;

use App\Support\DemoAccount;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoAccountTemporaryEmailTest extends TestCase
{
    public function test_temporary_email_is_derived_from_session_id(): void
    {
        $sessionId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

        $email = DemoAccount::temporaryEmailForSession($sessionId);

        $this->assertSame('demo-a1b2c3d4e5f67890abcdef1234567890@crmdemo.com', $email);
        $this->assertTrue(DemoAccount::isTemporaryDemoEmail($email));
    }

    public function test_shared_demo_email_is_not_temporary(): void
    {
        $this->assertFalse(DemoAccount::isTemporaryDemoEmail(DemoAccount::email()));
        $this->assertFalse(DemoAccount::isTemporaryDemoEmail('user@example.com'));
    }

    public function test_temporary_email_uses_uuid_without_dashes(): void
    {
        $sessionId = Str::uuid()->toString();

        $this->assertTrue(DemoAccount::isTemporaryDemoEmail(
            DemoAccount::temporaryEmailForSession($sessionId)
        ));
    }
}
