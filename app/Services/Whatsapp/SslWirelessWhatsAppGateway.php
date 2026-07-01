<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsAppGateway;
use App\Services\Whatsapp\Concerns\NormalizesBdPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslWirelessWhatsAppGateway implements WhatsAppGateway
{
    use NormalizesBdPhone;

    public function __construct(
        private ?string $apiToken,
        private ?string $senderId,
    ) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->apiToken) {
            Log::warning('SSL Wireless WhatsApp: API token missing.');

            return false;
        }

        $endpoint = config('whatsapp.providers.sslwireless.endpoint');

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post($endpoint, array_filter([
                    'api_token' => $this->apiToken,
                    'sid'       => $this->senderId,
                    'msisdn'    => $this->normalizeBdPhone($phone),
                    'message'   => $message,
                    'text'      => $message,
                ]));

            if (! $response->successful()) {
                Log::warning('SSL Wireless WhatsApp failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SSL Wireless WhatsApp error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
