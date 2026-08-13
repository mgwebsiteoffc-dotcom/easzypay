<?php

namespace App\Http\Controllers;

use App\Services\GeoLocationService;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function __construct(
        private GeoLocationService  $geoService,
        private ExchangeRateService $rateService,
    ) {}

    // ============================================================
    // Detect location from IP
    // ============================================================
    public function detect(Request $request): JsonResponse
    {
        $ip = $this->getClientIp($request);

        $geoData = $this->geoService->detect($ip);

        return response()->json([
            'ip'            => $ip,
            'country_code'  => $geoData['country_code'],
            'country_name'  => $geoData['country_name'],
            'region'        => $geoData['region'],
            'city'          => $geoData['city'],
            'currency'      => $geoData['currency'],
            'currency_name' => $geoData['currency_name'],
            'symbol'        => $geoData['symbol'],
            'flag'          => $geoData['flag'],
            'decimals'      => $geoData['decimals'],
            'currencies'    => $this->geoService->getCurrencyMeta(),
        ]);
    }

    // ============================================================
    // Exchange rate
    // ============================================================
    public function exchangeRate(Request $request): JsonResponse
    {
        $from   = strtoupper($request->get('from', 'USD'));
        $to     = strtoupper($request->get('to', 'USD'));
        $amount = (int) $request->get('amount', 0);

        $result = $this->rateService->convert($from, $to, $amount);

        return response()->json($result);
    }

    // ============================================================
    // Get states/provinces for a country
    // ============================================================
    public function getStates(Request $request): JsonResponse
    {
        $country = strtoupper($request->get('country', 'US'));

        $cacheKey = "states_{$country}";

        $states = Cache::remember($cacheKey, now()->addDay(), function () use ($country) {
            return $this->fetchStates($country);
        });

        return response()->json(['states' => $states]);
    }

    // ============================================================
    // Get cities for a country + state
    // ============================================================
    public function getCities(Request $request): JsonResponse
    {
        $country = strtoupper($request->get('country', 'US'));
        $state   = $request->get('state', '');

        if (empty($state)) {
            return response()->json(['cities' => []]);
        }

        $cacheKey = "cities_{$country}_{$state}";

        $cities = Cache::remember($cacheKey, now()->addDay(), function () use ($country, $state) {
            return $this->fetchCities($country, $state);
        });

        return response()->json(['cities' => $cities]);
    }

    // ============================================================
    // Validate postcode
    // ============================================================
    public function suggestAddress(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $country = strtoupper((string) $request->get('country', ''));
        if (strlen($q) < 3) {
            return response()->json(['suggestions' => []]);
        }

        $cacheKey = 'addr2_' . md5($country . '|' . mb_strtolower($q));
        $suggestions = Cache::remember($cacheKey, now()->addHours(6), function () use ($q, $country) {
            try {
                $params = [
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'limit' => 8,
                    'q' => $q,
                ];
                if (strlen($country) === 2) {
                    $params['countrycodes'] = strtolower($country);
                }

                $res = Http::timeout(8)
                    ->withHeaders([
                        'User-Agent' => 'EaszyPayCheckout/1.0 (easzypay.lead365.in)',
                        'Accept-Language' => 'en',
                    ])
                    ->get('https://nominatim.openstreetmap.org/search', $params);

                if (!$res->ok()) {
                    return [];
                }

                $out = [];
                foreach (($res->json() ?? []) as $row) {
                    $addr = is_array($row['address'] ?? null) ? $row['address'] : [];
                    $cc = strtoupper((string) ($addr['country_code'] ?? ''));
                    $line1 = trim(implode(' ', array_filter([
                        $addr['house_number'] ?? '',
                        $addr['road'] ?? $addr['pedestrian'] ?? $addr['residential'] ?? '',
                    ])));
                    if ($line1 === '') {
                        $line1 = (string) ($row['name'] ?? '');
                    }
                    if ($line1 === '') {
                        $line1 = explode(',', (string) ($row['display_name'] ?? ''))[0] ?? '';
                    }
                    if ($line1 === '') {
                        continue;
                    }

                    $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['hamlet'] ?? $addr['suburb'] ?? $addr['county'] ?? '';
                    $state = $addr['state'] ?? $addr['region'] ?? '';
                    $iso = (string) ($addr['ISO3166-2-lvl4'] ?? '');
                    $stateCode = '';
                    if (str_contains($iso, '-')) {
                        $stateCode = strtoupper((string) substr($iso, strpos($iso, '-') + 1));
                    }
                    if ($stateCode === '' && $cc === 'US') {
                        $stateCode = $this->usStateCode($state) ?? '';
                    }
                    if ($stateCode === '' && $cc === 'IN') {
                        $stateCode = $this->indiaStateCode($state) ?? '';
                    }

                    $out[] = [
                        'label' => implode(', ', array_filter([
                            $line1,
                            $city,
                            $state,
                            $addr['postcode'] ?? null,
                        ])),
                        'line1' => $line1,
                        'city' => $city,
                        'state' => $state,
                        'state_code' => $stateCode,
                        'postcode' => (string) ($addr['postcode'] ?? ''),
                        'country' => $cc,
                    ];
                }

                usort($out, function ($a, $b) {
                    return (int) ($b['postcode'] !== '') <=> (int) ($a['postcode'] !== '');
                });

                return array_values(array_slice($out, 0, 6));
            } catch (\Throwable $e) {
                Log::warning('Address suggest failed', ['error' => $e->getMessage()]);
                return [];
            }
        });

        return response()->json(['suggestions' => $suggestions]);
    }

    public function validatePostcode(Request $request): JsonResponse
    {
        $postcode = $request->get('postcode', '');
        $country  = strtoupper($request->get('country', 'US'));

        if (empty($postcode)) {
            return response()->json(['valid' => false, 'message' => 'Postcode required']);
        }

        $lookup = $this->lookupPostcode(trim($postcode), $country);
        $formatOk = $this->validatePostcodeFormat($postcode, $country);

        return response()->json([
            'valid'   => $lookup['valid'] || $formatOk,
            'message' => $lookup['valid'] ? 'Valid postcode' : ($formatOk ? 'Valid format' : 'Invalid postcode'),
            'city'    => $lookup['city'],
            'state'   => $lookup['state'],
            'state_code' => $lookup['state_code'],
            'country' => $country,
        ]);
    }

    // ============================================================
    // Private helpers
    // ============================================================
    private function fetchStates(string $country): array
    {
        // Built-in states for common countries
        $states = [
            'US' => [
                'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas',
                'CA'=>'California','CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware',
                'FL'=>'Florida','GA'=>'Georgia','HI'=>'Hawaii','ID'=>'Idaho',
                'IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas',
                'KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland',
                'MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi',
                'MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada',
                'NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York',
                'NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma',
                'OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina',
                'SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah',
                'VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia',
                'WI'=>'Wisconsin','WY'=>'Wyoming','DC'=>'District of Columbia',
            ],
            'CA' => [
                'AB'=>'Alberta','BC'=>'British Columbia','MB'=>'Manitoba',
                'NB'=>'New Brunswick','NL'=>'Newfoundland and Labrador',
                'NS'=>'Nova Scotia','ON'=>'Ontario','PE'=>'Prince Edward Island',
                'QC'=>'Quebec','SK'=>'Saskatchewan','NT'=>'Northwest Territories',
                'NU'=>'Nunavut','YT'=>'Yukon',
            ],
            'GB' => [
                'ENG'=>'England','SCT'=>'Scotland','WLS'=>'Wales','NIR'=>'Northern Ireland',
            ],
            'AU' => [
                'NSW'=>'New South Wales','VIC'=>'Victoria','QLD'=>'Queensland',
                'WA'=>'Western Australia','SA'=>'South Australia','TAS'=>'Tasmania',
                'ACT'=>'Australian Capital Territory','NT'=>'Northern Territory',
            ],
            'IN' => [
                'AP'=>'Andhra Pradesh','AR'=>'Arunachal Pradesh','AS'=>'Assam',
                'BR'=>'Bihar','CG'=>'Chhattisgarh','GA'=>'Goa','GJ'=>'Gujarat',
                'HR'=>'Haryana','HP'=>'Himachal Pradesh','JH'=>'Jharkhand',
                'KA'=>'Karnataka','KL'=>'Kerala','MP'=>'Madhya Pradesh',
                'MH'=>'Maharashtra','MN'=>'Manipur','ML'=>'Meghalaya',
                'MZ'=>'Mizoram','NL'=>'Nagaland','OD'=>'Odisha','PB'=>'Punjab',
                'RJ'=>'Rajasthan','SK'=>'Sikkim','TN'=>'Tamil Nadu','TG'=>'Telangana',
                'TR'=>'Tripura','UP'=>'Uttar Pradesh','UK'=>'Uttarakhand','WB'=>'West Bengal',
                'AN'=>'Andaman and Nicobar Islands','CH'=>'Chandigarh',
                'DN'=>'Dadra and Nagar Haveli','DD'=>'Daman and Diu',
                'DL'=>'Delhi','LD'=>'Lakshadweep','PY'=>'Puducherry',
            ],
            'AE' => [
                'AZ'=>'Abu Dhabi','AJ'=>'Ajman','DU'=>'Dubai','FU'=>'Fujairah',
                'RK'=>'Ras Al Khaimah','SH'=>'Sharjah','UQ'=>'Umm Al Quwain',
            ],
            'DE' => [
                'BB'=>'Brandenburg','BE'=>'Berlin','BW'=>'Baden-Württemberg',
                'BY'=>'Bavaria','HB'=>'Bremen','HE'=>'Hesse','HH'=>'Hamburg',
                'MV'=>'Mecklenburg-Vorpommern','NI'=>'Lower Saxony','NW'=>'North Rhine-Westphalia',
                'RP'=>'Rhineland-Palatinate','SH'=>'Schleswig-Holstein',
                'SL'=>'Saarland','SN'=>'Saxony','ST'=>'Saxony-Anhalt','TH'=>'Thuringia',
            ],
        ];

        if (isset($states[$country])) {
            return collect($states[$country])->map(fn($name, $code) => [
                'code' => $code,
                'name' => $name,
            ])->values()->toArray();
        }

        // Try API for other countries
        try {
            $response = Http::timeout(5)
                ->post('https://countriesnow.space/api/v0.1/countries/states', [
                    'country' => $this->getCountryName($country),
                ]);

            if ($response->ok()) {
                $data = $response->json();
                if (!($data['error'] ?? true) && !empty($data['data']['states'])) {
                    return collect($data['data']['states'])->map(fn($s) => [
                        'code' => $s['state_code'] ?? $s['name'],
                        'name' => $s['name'],
                    ])->toArray();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('States API failed', ['country' => $country]);
        }

        return [];
    }

    private function fetchCities(string $country, string $state): array
    {
        try {
            $response = Http::timeout(5)
                ->post('https://countriesnow.space/api/v0.1/countries/state/cities', [
                    'country' => $this->getCountryName($country),
                    'state'   => $state,
                ]);

            if ($response->ok()) {
                $data = $response->json();
                if (!($data['error'] ?? true) && !empty($data['data'])) {
                    return collect($data['data'])->map(fn($city) => [
                        'name' => $city,
                    ])->toArray();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Cities API failed', ['country' => $country, 'state' => $state]);
        }

        return [];
    }

    private function lookupPostcode(string $postcode, string $country): array
    {
        $empty = ['valid' => false, 'city' => null, 'state' => null, 'state_code' => null];
        $postcode = trim($postcode);
        if ($postcode === '') {
            return $empty;
        }

        $cacheKey = 'zip_' . $country . '_' . preg_replace('/\s+/', '', strtoupper($postcode));
        return Cache::remember($cacheKey, now()->addDay(), function () use ($postcode, $country, $empty) {
            try {
                if ($country === 'IN') {
                    $pin = preg_replace('/\D/', '', $postcode);
                    $res = Http::timeout(6)->get("https://api.postalpincode.in/pincode/{$pin}");
                    if ($res->ok()) {
                        $row = $res->json()[0] ?? [];
                        $po = $row['PostOffice'][0] ?? null;
                        if (($row['Status'] ?? '') === 'Success' && $po) {
                            return [
                                'valid' => true,
                                'city' => $po['District'] ?? $po['Name'] ?? null,
                                'state' => $po['State'] ?? null,
                                'state_code' => $this->indiaStateCode($po['State'] ?? ''),
                            ];
                        }
                    }
                }

                $slug = strtolower($country);
                $zip = rawurlencode(str_replace(' ', '', $postcode));
                $res = Http::timeout(6)->get("https://api.zippopotam.us/{$slug}/{$zip}");
                if ($res->ok()) {
                    $place = ($res->json('places') ?? [])[0] ?? null;
                    if ($place) {
                        return [
                            'valid' => true,
                            'city' => $place['place name'] ?? null,
                            'state' => $place['state'] ?? null,
                            'state_code' => $place['state abbreviation'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Postcode lookup failed', ['error' => $e->getMessage(), 'country' => $country]);
            }
            return $empty;
        });
    }

    private function usStateCode(string $name): ?string
    {
        $map = [
            'alabama'=>'AL','alaska'=>'AK','arizona'=>'AZ','arkansas'=>'AR','california'=>'CA',
            'colorado'=>'CO','connecticut'=>'CT','delaware'=>'DE','florida'=>'FL','georgia'=>'GA',
            'hawaii'=>'HI','idaho'=>'ID','illinois'=>'IL','indiana'=>'IN','iowa'=>'IA',
            'kansas'=>'KS','kentucky'=>'KY','louisiana'=>'LA','maine'=>'ME','maryland'=>'MD',
            'massachusetts'=>'MA','michigan'=>'MI','minnesota'=>'MN','mississippi'=>'MS','missouri'=>'MO',
            'montana'=>'MT','nebraska'=>'NE','nevada'=>'NV','new hampshire'=>'NH','new jersey'=>'NJ',
            'new mexico'=>'NM','new york'=>'NY','north carolina'=>'NC','north dakota'=>'ND','ohio'=>'OH',
            'oklahoma'=>'OK','oregon'=>'OR','pennsylvania'=>'PA','rhode island'=>'RI','south carolina'=>'SC',
            'south dakota'=>'SD','tennessee'=>'TN','texas'=>'TX','utah'=>'UT','vermont'=>'VT',
            'virginia'=>'VA','washington'=>'WA','west virginia'=>'WV','wisconsin'=>'WI','wyoming'=>'WY',
            'district of columbia'=>'DC',
        ];
        return $map[strtolower(trim($name))] ?? null;
    }

    private function indiaStateCode(string $name): ?string
    {
        $map = [
            'andhra pradesh'=>'AP','arunachal pradesh'=>'AR','assam'=>'AS','bihar'=>'BR',
            'chhattisgarh'=>'CG','goa'=>'GA','gujarat'=>'GJ','haryana'=>'HR',
            'himachal pradesh'=>'HP','jharkhand'=>'JH','karnataka'=>'KA','kerala'=>'KL',
            'madhya pradesh'=>'MP','maharashtra'=>'MH','manipur'=>'MN','meghalaya'=>'ML',
            'mizoram'=>'MZ','nagaland'=>'NL','odisha'=>'OD','punjab'=>'PB',
            'rajasthan'=>'RJ','sikkim'=>'SK','tamil nadu'=>'TN','telangana'=>'TG',
            'tripura'=>'TR','uttar pradesh'=>'UP','uttarakhand'=>'UK','west bengal'=>'WB',
            'delhi'=>'DL','chandigarh'=>'CH','puducherry'=>'PY',
        ];
        return $map[strtolower(trim($name))] ?? null;
    }

    private function validatePostcodeFormat(string $postcode, string $country): bool
    {
        $patterns = [
            'US' => '/^\d{5}(-\d{4})?$/',
            'GB' => '/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/i',
            'CA' => '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i',
            'AU' => '/^\d{4}$/',
            'DE' => '/^\d{5}$/',
            'FR' => '/^\d{5}$/',
            'IN' => '/^\d{6}$/',
            'AE' => '/^\d{5}$/',
            'SG' => '/^\d{6}$/',
            'JP' => '/^\d{3}-?\d{4}$/',
            'NL' => '/^\d{4}\s?[A-Z]{2}$/i',
            'SE' => '/^\d{3}\s?\d{2}$/',
            'NO' => '/^\d{4}$/',
            'DK' => '/^\d{4}$/',
            'CH' => '/^\d{4}$/',
            'NZ' => '/^\d{4}$/',
        ];

        if (isset($patterns[$country])) {
            return (bool) preg_match($patterns[$country], trim($postcode));
        }

        return strlen(trim($postcode)) >= 3;
    }

    private function getCountryName(string $code): string
    {
        $names = [
            'US'=>'United States','GB'=>'United Kingdom','CA'=>'Canada',
            'AU'=>'Australia','DE'=>'Germany','FR'=>'France','IT'=>'Italy',
            'ES'=>'Spain','NL'=>'Netherlands','AE'=>'United Arab Emirates',
            'SG'=>'Singapore','IN'=>'India','JP'=>'Japan','SE'=>'Sweden',
            'NO'=>'Norway','DK'=>'Denmark','CH'=>'Switzerland','NZ'=>'New Zealand',
            'IE'=>'Ireland','ZA'=>'South Africa','BR'=>'Brazil','MX'=>'Mexico',
        ];
        return $names[$code] ?? $code;
    }

    private function getClientIp(Request $request): string
    {
        $ip = $request->header('CF-Connecting-IP')
            ?? $request->header('X-Forwarded-For')
            ?? $request->header('X-Real-IP')
            ?? $request->ip();

        // Take first IP if comma-separated
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
}