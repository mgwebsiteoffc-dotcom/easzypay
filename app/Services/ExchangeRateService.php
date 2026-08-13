<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    public function convert(string $from, string $to, int $amountCents): array
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        if ($from === $to) {
            return [
                'from_currency'    => $from,
                'to_currency'      => $to,
                'original_amount'  => $amountCents,
                'converted_amount' => $amountCents,
                'rate'             => 1.0,
            ];
        }

        $rate = $this->getRate($from, $to);

        if (!$rate) {
            return [
                'from_currency'    => $from,
                'to_currency'      => $from,
                'original_amount'  => $amountCents,
                'converted_amount' => $amountCents,
                'rate'             => 1.0,
                'fallback'         => true,
            ];
        }

        $converted = (int) round($amountCents * $rate);

        return [
            'from_currency'    => $from,
            'to_currency'      => $to,
            'original_amount'  => $amountCents,
            'converted_amount' => $converted,
            'rate'             => $rate,
        ];
    }

    public function getRate(string $from, string $to): ?float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        $cacheKey = "exchange_rate_{$from}_{$to}";

        $cachedRate = Cache::get($cacheKey);

        if ($cachedRate !== null) {
            $cachedRate = (float) $cachedRate;

            if ($this->isRatePlausible($from, $to, $cachedRate)) {
                return $cachedRate;
            }

            Log::warning('Ignoring implausible cached exchange rate', [
                'from' => $from,
                'to'   => $to,
                'rate' => $cachedRate,
            ]);

            Cache::forget($cacheKey);
        }

        $rate = Cache::remember($cacheKey, now()->addHour(), function () use ($from, $to) {
            // Check database first
            $dbRate = ExchangeRate::where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('fetched_at', '>=', now()->subHour())
                ->first();

            if ($dbRate) {
                $rate = (float) $dbRate->rate;

                if ($this->isRatePlausible($from, $to, $rate)) {
                    return $rate;
                }

                Log::warning('Ignoring implausible cached exchange rate', [
                    'from' => $from,
                    'to'   => $to,
                    'rate' => $rate,
                ]);

                $dbRate->delete();
            }

            return $this->fetchRate($from, $to);
        });

        return $rate === null ? null : (float) $rate;
    }

    private function fetchRate(string $from, string $to): ?float
    {
        try {
            $response = Http::timeout(5)
                ->get("https://open.er-api.com/v6/latest/{$from}");

            if ($response->ok()) {
                $data = $response->json();

                if (($data['result'] ?? '') === 'success') {
                    $rate = $data['rates'][$to] ?? null;

                    if ($rate) {
                        $rate = (float) $rate;

                        if (!$this->isRatePlausible($from, $to, $rate)) {
                            Log::warning('Ignoring implausible fetched exchange rate', [
                                'from' => $from,
                                'to'   => $to,
                                'rate' => $rate,
                            ]);

                            return null;
                        }

                        // Store in DB
                        ExchangeRate::updateOrCreate(
                            ['from_currency' => $from, 'to_currency' => $to],
                            [
                                'rate'       => $rate,
                                'source'     => 'open.er-api.com',
                                'fetched_at' => now(),
                            ]
                        );

                        return $rate;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Exchange rate fetch failed', [
                'from'  => $from,
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function isRatePlausible(string $from, string $to, float $rate): bool
    {
        if ($rate <= 0) {
            return false;
        }

        if (strtoupper($from) === strtoupper($to)) {
            return abs($rate - 1.0) < 0.000001;
        }

        $usdRates = [
            'USD' => 1.0,
            'GBP' => 0.9,
            'EUR' => 1.1,
            'AUD' => 1.8,
            'CAD' => 1.7,
            'SGD' => 1.7,
            'AED' => 4.5,
            'INR' => 110.0,
            'JPY' => 180.0,
            'NZD' => 2.0,
            'HKD' => 9.5,
            'CHF' => 1.2,
            'SEK' => 13.0,
            'NOK' => 13.0,
            'DKK' => 8.5,
            'ZAR' => 25.0,
            'BRL' => 8.0,
            'MXN' => 25.0,
        ];

        $from = strtoupper($from);
        $to   = strtoupper($to);

        if (!isset($usdRates[$from], $usdRates[$to])) {
            return $rate < 10000;
        }

        $expectedUpper = ($usdRates[$to] / $usdRates[$from]) * 1.5;
        $expectedLower = ($usdRates[$to] / $usdRates[$from]) / 1.5;

        return $rate >= $expectedLower && $rate <= $expectedUpper;
    }
}
