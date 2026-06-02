<?php

namespace App\Services\WhatsApp;

use App\Models\OutboundMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    public function isEnabled(): bool
    {
        return (bool) config('whatsapp.enabled');
    }

    public function usesLogDriver(): bool
    {
        return config('whatsapp.driver', 'log') === 'log';
    }

    public function canDeliverToPhones(): bool
    {
        return match (config('whatsapp.driver', 'log')) {
            'api' => filled(config('whatsapp.access_token'))
                && filled(config('whatsapp.phone_number_id')),
            'twilio' => filled(config('whatsapp.twilio.sid'))
                && filled(config('whatsapp.twilio.token'))
                && filled(config('whatsapp.twilio.whatsapp_from')),
            default => false,
        };
    }

    public function resolveRecipient(?string $phone): ?string
    {
        if (config('whatsapp.test_mode')) {
            return self::normalizePhone((string) config('whatsapp.test_phone'));
        }

        return self::normalizePhone($phone);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendText(
        string $to,
        string $body,
        ?string $merchantId = null,
        ?string $subject = null,
        array $payload = [],
    ): WhatsAppSendResult {
        $normalizedTo = $this->resolveRecipient($to);

        if (! $normalizedTo) {
            return WhatsAppSendResult::failed('No valid WhatsApp recipient phone number.');
        }

        $driver = config('whatsapp.driver', 'log');

        try {
            $providerMessageId = match ($driver) {
                'api' => $this->sendViaApi($normalizedTo, $body),
                'twilio' => $this->sendViaTwilio($normalizedTo, $body),
                default => $this->sendViaLog($normalizedTo, $body),
            };

            $simulated = $driver === 'log';

            $this->recordOutbound(
                merchantId: $merchantId,
                recipient: '+' . $normalizedTo,
                subject: $subject,
                body: $body,
                payload: $payload,
                status: $simulated ? 'simulated' : 'sent',
                provider: $driver,
                providerMessageId: $providerMessageId,
            );

            return WhatsAppSendResult::success($normalizedTo, $providerMessageId, $simulated);
        } catch (\Throwable $exception) {
            $this->recordOutbound(
                merchantId: $merchantId,
                recipient: '+' . $normalizedTo,
                subject: $subject,
                body: $body,
                payload: $payload,
                status: 'failed',
                provider: $driver,
                errorMessage: $exception->getMessage(),
            );

            Log::error('WhatsApp send failed', [
                'to' => $normalizedTo,
                'driver' => $driver,
                'error' => $exception->getMessage(),
            ]);

            return WhatsAppSendResult::failed($exception->getMessage());
        }
    }

    private function sendViaLog(string $to, string $body): string
    {
        $messageId = 'log_' . Str::uuid()->toString();

        Log::info('WhatsApp message (log driver — not delivered to a real phone)', [
            'from' => config('whatsapp.sender_phone'),
            'to' => $to,
            'test_mode' => config('whatsapp.test_mode'),
            'body_preview' => Str::limit($body, 500),
            'message_id' => $messageId,
        ]);

        return $messageId;
    }

    private function sendViaApi(string $to, string $body): string
    {
        $token = config('whatsapp.access_token');
        $phoneNumberId = config('whatsapp.phone_number_id');

        if (! filled($token) || ! filled($phoneNumberId)) {
            throw new \RuntimeException(
                'Meta WhatsApp API is not configured. Set WHATSAPP_ACCESS_TOKEN and WHATSAPP_PHONE_NUMBER_ID, or use WHATSAPP_DRIVER=twilio.'
            );
        }

        $version = config('whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'WhatsApp API error: ' . ($response->json('error.message') ?? $response->body())
            );
        }

        return (string) ($response->json('messages.0.id') ?? Str::uuid()->toString());
    }

    private function sendViaTwilio(string $to, string $body): string
    {
        $sid = config('whatsapp.twilio.sid');
        $token = config('whatsapp.twilio.token');
        $from = config('whatsapp.twilio.whatsapp_from');

        if (! filled($sid) || ! filled($token) || ! filled($from)) {
            throw new \RuntimeException(
                'Twilio WhatsApp is not configured. Set TWILIO_SID, TWILIO_TOKEN, and TWILIO_WHATSAPP_FROM in .env.'
            );
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, [
                'From' => self::twilioWhatsAppAddress($from),
                'To' => self::twilioWhatsAppAddress('+' . $to),
                'Body' => $body,
            ]);

        if (! $response->successful()) {
            $message = $response->json('message') ?? $response->body();

            throw new \RuntimeException('Twilio WhatsApp error: ' . $message);
        }

        return (string) ($response->json('sid') ?? Str::uuid()->toString());
    }

    private static function twilioWhatsAppAddress(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with(strtolower($phone), 'whatsapp:')) {
            return $phone;
        }

        $normalized = self::normalizePhone($phone);

        return 'whatsapp:+' . ($normalized ?? ltrim($phone, '+'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordOutbound(
        ?string $merchantId,
        string $recipient,
        ?string $subject,
        string $body,
        array $payload,
        string $status,
        string $provider,
        ?string $providerMessageId = null,
        ?string $errorMessage = null,
    ): void {
        if (! $merchantId) {
            return;
        }

        OutboundMessage::query()->create([
            'id' => (string) Str::uuid(),
            'merchant_id' => $merchantId,
            'channel' => 'whatsapp',
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'payload' => $payload,
            'status' => $status,
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
            'error_message' => $errorMessage,
        ]);
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '92' . substr($digits, 1);
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }
}
