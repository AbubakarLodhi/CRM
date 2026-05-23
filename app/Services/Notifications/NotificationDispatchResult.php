<?php

namespace App\Services\Notifications;

class NotificationDispatchResult
{
    /** @var list<string> */
    public array $sent = [];

    /** @var list<string> */
    public array $skipped = [];

    public bool $success = false;

    public function addSent(string $line): void
    {
        $this->sent[] = $line;
        $this->success = true;
    }

    public function addSkipped(string $line): void
    {
        $this->skipped[] = $line;
    }
}
