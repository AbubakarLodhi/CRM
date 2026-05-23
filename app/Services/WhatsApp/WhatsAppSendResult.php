<?php

namespace App\Services\WhatsApp;

class WhatsAppSendResult
{
    public function __construct(
        public bool $success,
        public ?string $to = null,
        public ?string $providerMessageId = null,
        public ?string $error = null,
        /** True when driver=log — message was not sent to WhatsApp servers. */
        public bool $simulated = false,
    ) {}

    public static function success(string $to, ?string $providerMessageId = null, bool $simulated = false): self
    {
        return new self(true, $to, $providerMessageId, simulated: $simulated);
    }

    public static function failed(string $error): self
    {
        return new self(false, error: $error);
    }
}
