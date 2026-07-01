<?php

namespace App\Services\Whatsapp\Concerns;

trait NormalizesBdPhone
{
    protected function normalizeBdPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? $phone;

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '88' . $digits;
        }

        return '880' . $digits;
    }
}
