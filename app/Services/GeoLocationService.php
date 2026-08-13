<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    private array $countryToCurrency = [
        'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
        'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'PT' => 'EUR',
        'FI' => 'EUR', 'IE' => 'EUR', 'GR' => 'EUR', 'LU' => 'EUR',
        'SK' => 'EUR', 'SI' => 'EUR', 'EE' => 'EUR', 'LV' => 'EUR',
        'LT' => 'EUR', 'CY' => 'EUR', 'MT' => 'EUR',
        'GB' => 'GBP',
        'US' => 'USD', 'CA' => 'CAD',
        'AU' => 'AUD', 'NZ' => 'NZD',
        'SG' => 'SGD', 'JP' => 'JPY',
        'IN' => 'INR', 'HK' => 'HKD',
        'AE' => 'AED', 'SA' => 'SAR',
        'SE' => 'SEK', 'NO' => 'NOK', 'DK' => 'DKK',
        'CH' => 'CHF', 'PL' => 'PLN',
        'ZA' => 'ZAR', 'MY' => 'MYR',
        'TH' => 'THB', 'BR' => 'BRL',
        'MX' => 'MXN', 'KR' => 'KRW',
    ];

    private array $currencyMeta = [
        'USD' => ['symbol' => '$',    'name' => 'US Dollar',          'flag' => '🇺🇸', 'decimals' => 2],
        'EUR' => ['symbol' => '€',    'name' => 'Euro',               'flag' => '🇪🇺', 'decimals' => 2],
        'GBP' => ['symbol' => '£',    'name' => 'British Pound',      'flag' => '🇬🇧', 'decimals' => 2],
        'AUD' => ['symbol' => 'A$',   'name' => 'Australian Dollar',  'flag' => '🇦🇺', 'decimals' => 2],
        'CAD' => ['symbol' => 'CA$',  'name' => 'Canadian Dollar',    'flag' => '🇨🇦', 'decimals' => 2],
        'SGD' => ['symbol' => 'S$',   'name' => 'Singapore Dollar',   'flag' => '🇸🇬', 'decimals' => 2],
        'AED' => ['symbol' => 'AED',  'name' => 'UAE Dirham',         'flag' => '🇦🇪', 'decimals' => 2],
        'JPY' => ['symbol' => '¥',    'name' => 'Japanese Yen',       'flag' => '🇯🇵', 'decimals' => 0],
        'INR' => ['symbol' => '₹',    'name' => 'Indian Rupee',       'flag' => '🇮🇳', 'decimals' => 2],
        'CHF' => ['symbol' => 'CHF',  'name' => 'Swiss Franc',        'flag' => '🇨🇭', 'decimals' => 2],
        'SEK' => ['symbol' => 'kr',   'name' => 'Swedish Krona',      'flag' => '🇸🇪', 'decimals' => 2],
        'NOK' => ['symbol' => 'kr',   'name' => 'Norwegian Krone',    'flag' => '🇳🇴', 'decimals' => 2],
        'DKK' => ['symbol' => 'kr',   'name' => 'Danish Krone',       'flag' => '🇩🇰', 'decimals' => 2],
        'NZD' => ['symbol' => 'NZ$',  'name' => 'New Zealand Dollar', 'flag' => '🇳🇿', 'decimals' => 2],
        'HKD' => ['symbol' => 'HK$',  'name' => 'Hong Kong Dollar',   'flag' => '🇭🇰', 'decimals' => 2],
        'SAR' => ['symbol' => 'SAR',  'name' => 'Saudi Riyal',        'flag' => '🇸🇦', 'decimals' => 2],
        'ZAR' => ['symbol' => 'R',    'name' => 'South African Rand', 'flag' => '🇿🇦', 'decimals' => 2],
        'PLN' => ['symbol' => 'zł',   'name' => 'Polish Zloty',       'flag' => '🇵🇱', 'decimals' => 2],
        'MYR' => ['symbol' => 'RM',   'name' => 'Malaysian Ringgit',  'flag' => '🇲🇾', 'decimals' => 2],
        'THB' => ['symbol' => '฿',    'name' => 'Thai Baht',          'flag' => '🇹🇭', 'decimals' => 2],
        'BRL' => ['symbol' => 'R$',   'name' => 'Brazilian Real',     'flag' => '🇧🇷', 'decimals' => 2],
        'MXN' => ['symbol' => 'MX$',  'name' => 'Mexican Peso',       'flag' => '🇲🇽', 'decimals' => 2],
        'KRW' => ['symbol' => '₩',    'name' => 'South Korean Won',   'flag' => '🇰🇷', 'decimals' => 0],
    ];

    public function detect(string $ip): array
    {
        // Skip for localhost
        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) {
            return $this->buildResponse('US');
        }

        $cacheKey = 'geo_ip_' . md5($ip);

        return Cache::remember($cacheKey, now()->addHours(
            config('app.geoip_cache_hours', 24)
        ), function () use ($ip) {
            return $this->lookup($ip);
        });
    }

    private function lookup(string $ip): array
    {
        try {
            $response = Http::timeout(3)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,countryCode,country,regionName,city,zip,lat,lon',
                ]);

            if ($response->ok()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'success') {
                    return $this->buildResponse(
                        $data['countryCode'] ?? 'US',
                        $data
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('GeoIP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return $this->buildResponse('US');
    }

    private function buildResponse(string $countryCode, array $geoData = []): array
    {
        $currency = $this->countryToCurrency[$countryCode] ?? 'USD';
        $meta     = $this->currencyMeta[$currency] ?? $this->currencyMeta['USD'];

        return [
            'country_code'  => $countryCode,
            'country_name'  => $geoData['country'] ?? $countryCode,
            'region'        => $geoData['regionName'] ?? null,
            'city'          => $geoData['city'] ?? null,
            'currency'      => $currency,
            'currency_name' => $meta['name'],
            'symbol'        => $meta['symbol'],
            'flag'          => $meta['flag'],
            'decimals'      => $meta['decimals'],
        ];
    }

    public function getCurrencyMeta(): array
    {
        return $this->currencyMeta;
    }

    public function getCurrencyForCountry(string $countryCode): string
    {
        return $this->countryToCurrency[strtoupper($countryCode)] ?? 'USD';
    }
}