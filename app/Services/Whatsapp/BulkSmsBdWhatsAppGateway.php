<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsAppGateway;
use App\Services\Whatsapp\Concerns\NormalizesBdPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsBdWhatsAppGateway implements WhatsAppGateway
{
    use NormalizesBdPhone;

    public function __construct(
        private ?string $apiKey,
        private ?string $senderId,
    ) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->apiKey) {
            Log::warning('BulkSMSBD WhatsApp: API key missing.');

            return false;
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post(config('whatsapp.providers.bulksmsbd.endpoint'), array_filter([
                    'api_key' => $this->apiKey,
                    'senderid'=> $this->senderId,
                    'number'  => $this->normalizeBdPhone($phone),
                    'message' => $message,
                ]));

            if (! $response->successful()) {
                Log::warning('BulkSMSBD WhatsApp failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('BulkSMSBD WhatsApp error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
