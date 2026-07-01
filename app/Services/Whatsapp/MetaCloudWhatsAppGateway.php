<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsAppGateway;
use App\Services\Whatsapp\Concerns\NormalizesBdPhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudWhatsAppGateway implements WhatsAppGateway
{
    use NormalizesBdPhone;

    public function __construct(
        private ?string $accessToken,
        private ?string $phoneNumberId,
    ) {}

    public function send(string $phone, string $message): bool
    {
        if (! $this->accessToken || ! $this->phoneNumberId) {
            Log::warning('Meta WhatsApp Cloud: access token or phone number ID missing.');

            return false;
        }

        $version = config('whatsapp.providers.meta_cloud.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::timeout(20)
                ->withToken($this->accessToken)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to'                => $this->normalizeBdPhone($phone),
                    'type'              => 'text',
                    'text'              => [
                        'preview_url' => false,
                        'body'        => $message,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Meta WhatsApp Cloud failed', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp Cloud error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
