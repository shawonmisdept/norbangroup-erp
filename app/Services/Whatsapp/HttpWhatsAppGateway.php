<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsAppGateway;
use App\Services\Whatsapp\Concerns\NormalizesBdPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpWhatsAppGateway implements WhatsAppGateway
{
    use NormalizesBdPhone;

    public function __construct(
        private ?string $url,
        private ?string $apiKey,
        private ?string $senderId,
    ) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->url) {
            Log::warning('WhatsApp custom HTTP gateway URL is not configured.');

            return false;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(array_filter([
                    'Authorization' => $this->apiKey ? 'Bearer ' . $this->apiKey : null,
                    'Accept'        => 'application/json',
                ]))
                ->post($this->url, array_filter([
                    'phone'    => $this->normalizeBdPhone($phone),
                    'to'       => $this->normalizeBdPhone($phone),
                    'message'  => $message,
                    'text'     => $message,
                    'sender'   => $this->senderId,
                    'sender_id'=> $this->senderId,
                ]));

            if (! $response->successful()) {
                Log::warning('WhatsApp custom HTTP gateway failed', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp custom HTTP gateway error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
