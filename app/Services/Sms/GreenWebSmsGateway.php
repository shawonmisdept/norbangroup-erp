<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GreenWebSmsGateway implements SmsGateway
{
    public function __construct(private ?string $token) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->token) {
            Log::warning('GreenWeb SMS: API token missing.');

            return false;
        }

        try {
            $response = Http::timeout(15)->post(config('sms.providers.greenweb.endpoint'), [
                'token'   => $this->token,
                'to'      => $phone,
                'message' => $message,
            ]);

            if (! $response->successful()) {
                Log::warning('GreenWeb SMS failed', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('GreenWeb SMS error', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
