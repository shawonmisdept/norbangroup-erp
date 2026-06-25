<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslWirelessSmsGateway implements SmsGateway
{
    public function __construct(
        private ?string $apiToken,
        private ?string $senderId,
    ) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->apiToken || ! $this->senderId) {
            Log::warning('SSL Wireless SMS: API token or sender ID missing.');

            return false;
        }

        try {
            $response = Http::timeout(15)->get(config('sms.providers.sslwireless.endpoint'), [
                'api_token' => $this->apiToken,
                'sid'       => $this->senderId,
                'msisdn'    => $this->normalizePhone($phone),
                'sms'       => $message,
                'csmsid'    => uniqid('sms_', true),
            ]);

            if (! $response->successful()) {
                Log::warning('SSL Wireless SMS failed', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SSL Wireless SMS error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? $phone;

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '88' . $digits;
        }

        return '880' . $digits;
    }
}
