<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Validation\ValidationException;

class ExchangeRateResolver
{
    public function rateToUsd(string $currencyCode): string
    {
        if ($currencyCode === 'USD') {
            return '1';
        }

        $rate = ExchangeRate::query()
            ->where('currency_code', $currencyCode)
            ->where('effective_at', '<=', now())
            ->orderByDesc('effective_at')
            ->value('rate_to_usd');

        if ($rate === null) {
            throw ValidationException::withMessages([
                'currency_code' => __('messages.rate_missing'),
            ]);
        }

        return (string) $rate;
    }
}
