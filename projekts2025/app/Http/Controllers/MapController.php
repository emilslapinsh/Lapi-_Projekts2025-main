<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MapController extends Controller
{
    /**
     * API metode lokāciju ielādei kartē.
     *
     * Parametri:
     * - bbox  → redzamās kartes robežas (south,west,north,east)
     * - zoom  → kartes pietuvinājuma līmenis
     * - type  → lokācijas tips (all, gas_station, service_center, ev_charging)
     *
     * Avots:
     * - Ārējs ģeokodēšanas serviss (OpenStreetMap dati), ar skata rāmja (bbox) ierobežojumu.
     *
     * Piezīme:
     * - Ārējiem servisiem parasti ir jānorāda identificējams lietotājvārds/User-Agent un kontaktpersona/e-pasts.
     */
    public function fetchLocations(Request $request)
    {
        $bboxRaw = (string) $request->query('bbox', '');
        $zoom = (int) $request->query('zoom', 0);
        $type = (string) $request->query('type', 'all');

        if ($bboxRaw === '') {
            return response()->json([]);
        }

        $bbox = $this->normalizeBbox($bboxRaw, $zoom);
        if ($bbox === null) {
            return response()->json([]);
        }

        $type = $this->normalizeType($type);
        if ($type === null) {
            return response()->json([]);
        }

        // Zoom kontrole pret pārlieku lielu datu apjomu
        $allowGasAndService = $zoom >= 10;
        $allowEv = $zoom >= 11;

        if ($type === 'gas_station' && ! $allowGasAndService) {
            return response()->json([]);
        }
        if ($type === 'service_center' && ! $allowGasAndService) {
            return response()->json([]);
        }
        if ($type === 'ev_charging' && ! $allowEv) {
            return response()->json([]);
        }

        if ($type === 'all') {
            $types = [];
            if ($allowGasAndService) {
                $types[] = 'gas_station';
                $types[] = 'service_center';
            }
            if ($allowEv) {
                $types[] = 'ev_charging';
            }

            if (count($types) === 0) {
                return response()->json([]);
            }

            return response()->json($this->fetchNominatimMany($bbox, $zoom, $types));
        }

        return response()->json($this->fetchNominatimSingle($bbox, $zoom, $type));
    }

    /**
     * @param  array<int, string>  $types
     * @return array<int, array<string, string>>
     */
    private function fetchNominatimMany(string $bbox, int $zoom, array $types): array
    {
        sort($types);

        $cacheBbox = $this->cacheBbox($bbox, $zoom);
        $cacheKey = 'lv_nominatim_multi_v2_'.md5($cacheBbox.'|'.implode(',', $types));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $merged = [];
        foreach ($types as $t) {
            $merged = array_merge($merged, $this->fetchNominatimSingleUncached($bbox, $zoom, $t));
        }

        $merged = $this->dedupeLocations($merged);

        Cache::put($cacheKey, $merged, now()->addMinutes(count($merged) > 0 ? 20 : 2));

        return $merged;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function fetchNominatimSingle(string $bbox, int $zoom, string $type): array
    {
        $cacheBbox = $this->cacheBbox($bbox, $zoom);
        $cacheKey = 'lv_nominatim_v2_'.$type.'_'.md5($cacheBbox);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $data = $this->fetchNominatimSingleUncached($bbox, $zoom, $type);
        $data = $this->dedupeLocations($data);

        Cache::put($cacheKey, $data, now()->addMinutes(count($data) > 0 ? 20 : 2));

        return $data;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function fetchNominatimSingleUncached(string $bbox, int $zoom, string $type): array
    {
        [$south, $west, $north, $east] = $this->parseBbox($bbox);

        $phrase = match ($type) {
            'gas_station' => '[fuel]',
            'service_center' => '[car_repair]',
            'ev_charging' => '[charging_station]',
            default => null,
        };

        if ($phrase === null) {
            return [];
        }

        // Ārējā servisa viewbox: minLon,minLat,maxLon,maxLat
        $viewbox = sprintf('%.6f,%.6f,%.6f,%.6f', $west, $south, $east, $north);

        $limit = match (true) {
            $zoom >= 14 => 50,
            $zoom >= 12 => 40,
            default => 30,
        };

        $url = rtrim((string) env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'), '/').'/search';

        $email = (string) (env('NOMINATIM_EMAIL')
            ?: config('mail.from.address')
            ?: '');

        $appName = (string) config('app.name', 'Auto apkopes un izdevumu palīgrīks');

        $contact = $email !== '' ? ('; contact: '.$email) : '';
        $userAgent = $appName.' / karte'.$contact;

        try {
            $headers = [
                'User-Agent' => $userAgent,
                'Accept-Language' => 'lv',
            ];

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $headers['From'] = $email;
            }

            $resp = Http::withHeaders($headers)
                ->timeout(12)
                ->connectTimeout(4)
                ->get($url, [
                    'format' => 'jsonv2',
                    'q' => $phrase,
                    'bounded' => 1,
                    'viewbox' => $viewbox,
                    'countrycodes' => 'lv',
                    'limit' => $limit,
                    'addressdetails' => 1,
                    'dedupe' => 0,
                ]);
        } catch (\Throwable $e) {
            return [];
        }

        if (! $resp->ok()) {
            return [];
        }

        $items = $resp->json();
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! $this->matchesRequestedType($item, $type)) {
                continue;
            }

            $lat = $item['lat'] ?? null;
            $lon = $item['lon'] ?? null;
            if ($lat === null || $lon === null) {
                continue;
            }

            $name = $this->pickName($item, $type);
            $address = $this->formatNominatimAddress($item);

            $out[] = [
                'name' => $name,
                'type' => $type,
                'address' => $address,
                'latitude' => (string) $lat,
                'longitude' => (string) $lon,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function matchesRequestedType(array $item, string $requested): bool
    {
        // jsonv2 atbildēs parasti ir `category` + `type` (nevis `class` + `type`).
        $cat = '';
        if (isset($item['category'])) {
            $cat = (string) $item['category'];
        } elseif (isset($item['class'])) {
            $cat = (string) $item['class'];
        }

        $typ = isset($item['type']) ? (string) $item['type'] : '';

        return match ($requested) {
            'gas_station' => ($cat === 'amenity' && $typ === 'fuel'),
            'service_center' => ($cat === 'shop' && $typ === 'car_repair') || ($cat === 'amenity' && $typ === 'car_repair'),
            'ev_charging' => ($cat === 'amenity' && $typ === 'charging_station'),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function pickName(array $item, string $type): string
    {
        $name = isset($item['name']) ? trim((string) $item['name']) : '';
        if ($name !== '') {
            return $name;
        }

        $display = isset($item['display_name']) ? trim((string) $item['display_name']) : '';
        if ($display !== '') {
            $parts = explode(',', $display, 2);

            return trim($parts[0]);
        }

        return $this->defaultName($type);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function formatNominatimAddress(array $item): string
    {
        $addr = $item['address'] ?? null;
        if (is_array($addr)) {
            $road = isset($addr['road']) ? (string) $addr['road'] : '';
            $house = isset($addr['house_number']) ? (string) $addr['house_number'] : '';
            $city = isset($addr['city']) ? (string) $addr['city']
                : (isset($addr['town']) ? (string) $addr['town'] : (isset($addr['village']) ? (string) $addr['village'] : ''));

            $line1 = trim(trim($road.' '.$house));
            $line2 = trim($city);

            $parts = array_values(array_filter([$line1, $line2], fn ($p) => $p !== ''));
            if (count($parts) > 0) {
                return implode(', ', $parts);
            }
        }

        $display = isset($item['display_name']) ? trim((string) $item['display_name']) : '';

        return $display !== '' ? $display : 'Adrese nav norādīta';
    }

    /**
     * @param  array<int, array<string, string>>  $items
     * @return array<int, array<string, string>>
     */
    private function dedupeLocations(array $items): array
    {
        $seen = [];
        $out = [];

        foreach ($items as $it) {
            $lat = (string) ($it['latitude'] ?? '');
            $lon = (string) ($it['longitude'] ?? '');
            $type = (string) ($it['type'] ?? '');
            $key = $type.'|'.$lat.'|'.$lon;

            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $it;
        }

        return $out;
    }

    private function defaultName(string $type): string
    {
        return match ($type) {
            'gas_station' => 'Degvielas uzpildes stacija',
            'service_center' => 'Autoserviss',
            'ev_charging' => 'EV uzlādes punkts',
            default => 'Lokācija',
        };
    }

    private function normalizeType(string $type): ?string
    {
        $type = Str::of($type)->trim()->lower()->toString();
        $allowed = ['all', 'gas_station', 'service_center', 'ev_charging'];

        return in_array($type, $allowed, true) ? $type : null;
    }

    /**
     * Normalizē bbox uz drošu formātu un ierobežo to Latvijas robežās.
     * Atgriež formātu: "south,west,north,east".
     */
    private function normalizeBbox(string $bboxRaw, int $zoom): ?string
    {
        $bboxRaw = trim($bboxRaw);
        $parts = preg_split('/\s*,\s*/', $bboxRaw);
        if (! $parts || count($parts) !== 4) {
            return null;
        }

        $nums = [];
        foreach ($parts as $p) {
            if (! is_numeric($p)) {
                return null;
            }
            $nums[] = (float) $p;
        }

        [$south, $west, $north, $east] = $nums;

        if ($south > $north) {
            [$south, $north] = [$north, $south];
        }
        if ($west > $east) {
            [$west, $east] = [$east, $west];
        }

        $lvSouth = 55.60;
        $lvNorth = 58.10;
        $lvWest = 20.90;
        $lvEast = 28.30;

        if ($north < $lvSouth || $south > $lvNorth || $east < $lvWest || $west > $lvEast) {
            return null;
        }

        $south = max($south, $lvSouth);
        $north = min($north, $lvNorth);
        $west = max($west, $lvWest);
        $east = min($east, $lvEast);

        $maxSpan = match (true) {
            $zoom >= 12 => 1.2,
            $zoom >= 11 => 1.8,
            $zoom >= 10 => 3.0,
            default => 6.0,
        };

        if (($north - $south) > $maxSpan || ($east - $west) > $maxSpan) {
            return null;
        }

        return sprintf('%.6f,%.6f,%.6f,%.6f', $south, $west, $north, $east);
    }

    /**
     * Kešošanai noapaļojam bbox atkarībā no zoom, lai samazinātu atslēgu skaitu.
     */
    private function cacheBbox(string $bbox, int $zoom): string
    {
        $parts = explode(',', $bbox);
        if (count($parts) !== 4) {
            return $bbox;
        }

        $precision = match (true) {
            $zoom >= 13 => 4,
            $zoom >= 11 => 3,
            default => 2,
        };

        $nums = array_map('floatval', $parts);
        $fmt = '%.'.$precision.'f';

        return sprintf($fmt.','.$fmt.','.$fmt.','.$fmt, $nums[0], $nums[1], $nums[2], $nums[3]);
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function parseBbox(string $bbox): array
    {
        $parts = array_map('floatval', explode(',', $bbox));

        return [$parts[0], $parts[1], $parts[2], $parts[3]];
    }
}
