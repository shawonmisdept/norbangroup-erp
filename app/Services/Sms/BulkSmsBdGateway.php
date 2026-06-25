<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsBdGateway implements SmsGateway
{
    public function __construct(
        private ?string $apiKey,
        private ?string $senderId,
    ) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->apiKey || ! $this->senderId) {
            Log::warning('BulkSMSBD: API key or sender ID missing.');

            return false;
        }

        try {
            $response = Http::timeout(15)->get(config('sms.providers.bulksmsbd.endpoint'), [
                'api_key'  => $this->apiKey,
                'senderid' => $this->senderId,
                'number'   => $phone,
                'message'  => $message,
            ]);

            if (! $response->successful()) {
                Log::warning('BulkSMSBD SMS failed', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('BulkSMSBD SMS error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
