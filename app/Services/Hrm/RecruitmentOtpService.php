<?php

namespace App\Services\Hrm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecruitmentOtpService
{
    private const TTL_SECONDS = 600;

    public function __construct(private RecruitmentMessagingService $messaging) {}

    public function send(string $phone): array
    {
        $normalized = $this->normalizePhone($phone);

        if (strlen($normalized) < 10) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid phone number.']);
        }

        $rateKey = 'recruitment_otp_rate:' . $normalized;
        if (Cache::has($rateKey)) {
            throw ValidationException::withMessages(['phone' => 'Please wait before requesting another OTP.']);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->cacheKey($normalized), $otp, self::TTL_SECONDS);
        Cache::put($rateKey, true, 60);

        Log::info('Recruitment OTP generated', ['phone' => $normalized, 'otp' => config('app.debug') ? $otp : '***']);

        $this->messaging->sendOtp($normalized, $otp);

        $message = 'Verification code sent to your phone.';
        if (config('app.debug')) {
            $message .= ' (Dev OTP: ' . $otp . ')';
        }

        return ['message' => $message];
    }

    public function verify(string $phone, string $otp): bool
    {
        $normalized = $this->normalizePhone($phone);
        $cached = Cache::get($this->cacheKey($normalized));

        if (! $cached || ! hash_equals((string) $cached, trim($otp))) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired verification code.']);
        }

        Cache::forget($this->cacheKey($normalized));

        return true;
    }

    private function cacheKey(string $phone): string
    {
        return 'recruitment_otp:' . $phone;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?? trim($phone);
    }
}
