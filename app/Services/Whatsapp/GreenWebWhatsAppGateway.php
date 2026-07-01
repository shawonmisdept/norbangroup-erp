<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsAppGateway;
use App\Services\Whatsapp\Concerns\NormalizesBdPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GreenWebWhatsAppGateway implements WhatsAppGateway
{
    use NormalizesBdPhone;

    public function __construct(private ?string $token) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->token) {
            Log::warning('GreenWeb WhatsApp: API token missing.');

            return false;
        }

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post(config('whatsapp.providers.greenweb.endpoint'), [
                    'token'   => $this->token,
                    'to'      => $this->normalizeBdPhone($phone),
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::warning('GreenWeb WhatsApp failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('GreenWeb WhatsApp error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
