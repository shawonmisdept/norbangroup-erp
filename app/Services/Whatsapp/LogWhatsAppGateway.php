<?php

namespace App\Services\Whatsapp;

use App\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Log;

class LogWhatsAppGateway implements WhatsAppGateway
{
    public function send(string $phone, string $message): bool
    {
        Log::info('WhatsApp sent (log driver)', [
            'phone'   => $phone,
            'message' => $message,
        ]);

        return true;
    }
}
