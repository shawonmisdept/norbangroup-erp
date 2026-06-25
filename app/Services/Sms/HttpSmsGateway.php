<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpSmsGateway implements SmsGateway
{
    public function __construct(
        private ?string $url,
        private ?string $apiKey,
        private string $sender,
    ) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->url) {
            Log::warning('Recruitment SMS HTTP gateway URL is not configured.');

            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(array_filter([
                    'Authorization' => $this->apiKey ? 'Bearer ' . $this->apiKey : null,
                    'Accept'        => 'application/json',
                ]))
                ->post($this->url, [
                    'phone'   => $phone,
                    'message' => $message,
                    'sender'  => $this->sender,
                ]);

            if (! $response->successful()) {
                Log::warning('Recruitment SMS HTTP gateway failed', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Recruitment SMS HTTP gateway error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
