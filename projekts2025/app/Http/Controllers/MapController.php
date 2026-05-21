<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Kartes API vietu ielādei
// Ielādē lokācijas pēc bbox, zoom un tipa, izmanto Nominatim un Cache, atgriež vienkāršotu JSON sarakstu

class MapController extends Controller
{
    // API metode lokāciju ielādei kartē
    public function fetchLocations(Request $request)
    {
        // Nolasa parametrus no vaicājuma
        $bboxRaw = (string) $request->query('bbox', '');
        $zoom = (int) $request->query('zoom', 0);
        $type = (string) $request->query('type', 'all');

        // Ja nav bbox, neatgriež datus
        if ($bboxRaw === '') {
            return response()->json([]);
        }

        // Normalizē bbox un ierobežo to pēc robežām un izmēra
        $bbox = $this->normalizeBbox($bboxRaw, $zoom);
        if ($bbox === null) {
            return response()->json([]);
        }

        // Normalizē lokācijas tipu
        $type = $this->normalizeType($type);
        if ($type === null) {
            return response()->json([]);
        }

        // Ierobežo datu apjomu pēc zoom līmeņa
        $allowGasAndService = $zoom >= 10;
        $allowEv = $zoom >= 11;

        // Ja zoom ir par mazu, neatgriež konkrētos tipus
        if ($type === 'gas_station' && ! $allowGasAndService) {
            return response()->json([]);
        }
        if ($type === 'service_center' && ! $allowGasAndService) {
            return response()->json([]);
        }
        if ($type === 'ev_charging' && ! $allowEv) {
            return response()->json([]);
        }

        // Ja tips ir all, apvieno vairākus pieprasījumus
        if ($type === 'all') {
            $types = [];
            if ($allowGasAndService) {
                $types[] = 'gas_station';
                $types[] = 'service_center';
            }
            if ($allowEv) {
                $types[] = 'ev_charging';
            }

            // Ja nekas nav atļauts, atgriež tukšu sarakstu
            if (count($types) === 0) {
                return response()->json([]);
            }

            // Ielasa vairākus tipus un apvieno rezultātus
            return response()->json($this->fetchNominatimMany($bbox, $zoom, $types));
        }

        // Ielasa vienu tipu
        return response()->json($this->fetchNominatimSingle($bbox, $zoom, $type));
    }

    // Ielasa vairākus lokāciju tipus un apvieno tos
    private function fetchNominatimMany(string $bbox, int $zoom, array $types): array
    {
        // Sakārto tipus, lai kešs būtu stabils
        sort($types);

        // Keša atslēga (bbox + tipi)
        $cacheBbox = $this->cacheBbox($bbox, $zoom);
        $cacheKey = 'lv_nominatim_multi_v2_'.md5($cacheBbox.'|'.implode(',', $types));

        // Ja ir kešā, atgriež no keša
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        // Ielasa katru tipu atsevišķi (Nominatim: max ~1 pieprasījums sekundē)
        $merged = [];
        foreach ($types as $i => $t) {
            if ($i > 0) {
                usleep(1_100_000);
            }
            $merged = array_merge($merged, $this->fetchNominatimSingleUncached($bbox, $zoom, $t));
        }

        // Noņem dublikātus pēc koordinātēm un tipa
        $merged = $this->dedupeLocations($merged);

        // Tukšu rezultātu nekešē — citādi pēc rate limit kartē ilgi paliek tukša
        if (count($merged) > 0) {
            Cache::put($cacheKey, $merged, now()->addMinutes(20));
        }

        return $merged;
    }

    // Ielasa vienu lokāciju tipu ar kešošanu
    private function fetchNominatimSingle(string $bbox, int $zoom, string $type): array
    {
        // Keša atslēga (bbox + tips)
        $cacheBbox = $this->cacheBbox($bbox, $zoom);
        $cacheKey = 'lv_nominatim_v2_'.$type.'_'.md5($cacheBbox);

        // Ja ir kešā, atgriež no keša
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        // Ielasa datus no ārējā servisa
        $data = $this->fetchNominatimSingleUncached($bbox, $zoom, $type);
        $data = $this->dedupeLocations($data);

        if (count($data) > 0) {
            Cache::put($cacheKey, $data, now()->addMinutes(20));
        }

        return $data;
    }

    // Ielasa datus no Nominatim bez kešošanas
    private function fetchNominatimSingleUncached(string $bbox, int $zoom, string $type): array
    {
        // Izvelk bbox robežas skaitļos
        [$south, $west, $north, $east] = $this->parseBbox($bbox);

        // Meklēšanas frāze pēc tipa
        $phrase = match ($type) {
            'gas_station' => '[fuel]',
            'service_center' => '[car_repair]',
            'ev_charging' => '[charging_station]',
            default => null,
        };

        // Ja tips nav atbalstīts, atgriež tukšu
        if ($phrase === null) {
            return [];
        }

        // Nominatim viewbox formāts: minLon,minLat,maxLon,maxLat
        $viewbox = sprintf('%.6f,%.6f,%.6f,%.6f', $west, $south, $east, $north);

        // Ierobežo rezultātu skaitu pēc zoom
        $limit = match (true) {
            $zoom >= 14 => 50,
            $zoom >= 12 => 40,
            default => 30,
        };

        // Nominatim URL no env
        $url = rtrim((string) env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'), '/').'/search';

        // E-pasts lietotāja identificēšanai ārējam servisam
        $email = (string) (env('NOMINATIM_EMAIL')
            ?: config('mail.from.address')
            ?: '');

        // Lietotnes nosaukums User-Agent laukam
        $appName = (string) config('app.name', 'Auto apkopes un izdevumu palīgrīks');

        // User-Agent ar kontaktu, ja ir pieejams
        $contact = $email !== '' ? ('; contact: '.$email) : '';
        $userAgent = $appName.' / karte'.$contact;

        // Veic pieprasījumu uz Nominatim (viena atkārtota mēģinājuma iespēja pēc rate limit)
        try {
            $resp = $this->nominatimRequest($url, $phrase, $viewbox, $limit, $userAgent, $email);
            if (! $resp->ok() && in_array($resp->status(), [429, 503], true)) {
                usleep(2_000_000);
                $resp = $this->nominatimRequest($url, $phrase, $viewbox, $limit, $userAgent, $email);
            }
        } catch (\Throwable $e) {
            return [];
        }

        if (! $resp->ok()) {
            return [];
        }

        // Izlasa JSON atbildi
        $items = $resp->json();
        if (! is_array($items)) {
            return [];
        }

        // Pārveido Nominatim datus uz vienkāršu formātu
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            // Pārbauda, vai ieraksts atbilst izvēlētajam tipam
            if (! $this->matchesRequestedType($item, $type)) {
                continue;
            }

            // Koordinātas
            $lat = $item['lat'] ?? null;
            $lon = $item['lon'] ?? null;
            if ($lat === null || $lon === null) {
                continue;
            }

            // Nosaukums un adrese
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

    // HTTP pieprasījums uz Nominatim ar obligāto User-Agent
    private function nominatimRequest(
        string $url,
        string $phrase,
        string $viewbox,
        int $limit,
        string $userAgent,
        string $email
    ) {
        $headers = [
            'User-Agent' => $userAgent,
            'Accept-Language' => 'lv',
        ];

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $headers['From'] = $email;
        }

        return Http::withHeaders($headers)
            ->timeout(15)
            ->connectTimeout(5)
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
    }

    // Pārbauda, vai Nominatim ieraksts atbilst pieprasītajam tipam
    private function matchesRequestedType(array $item, string $requested): bool
    {
        // Nolasa kategoriju un tipu no atbildes
        $cat = '';
        if (isset($item['category'])) {
            $cat = (string) $item['category'];
        } elseif (isset($item['class'])) {
            $cat = (string) $item['class'];
        }

        $typ = isset($item['type']) ? (string) $item['type'] : '';

        // Atbilstības noteikumi katram tipam
        return match ($requested) {
            'gas_station' => ($cat === 'amenity' && $typ === 'fuel'),
            'service_center' => ($cat === 'shop' && $typ === 'car_repair') || ($cat === 'amenity' && $typ === 'car_repair'),
            'ev_charging' => ($cat === 'amenity' && $typ === 'charging_station'),
            default => false,
        };
    }

    // Izvēlas labāko nosaukumu lokācijai
    private function pickName(array $item, string $type): string
    {
        // Primāri lieto name lauku
        $name = isset($item['name']) ? trim((string) $item['name']) : '';
        if ($name !== '') {
            return $name;
        }

        // Ja nav name, mēģina paņemt pirmo daļu no display_name
        $display = isset($item['display_name']) ? trim((string) $item['display_name']) : '';
        if ($display !== '') {
            $parts = explode(',', $display, 2);

            return trim($parts[0]);
        }

        // Ja nav nekā, iedod noklusēto nosaukumu
        return $this->defaultName($type);
    }

    // Noformē adresi no Nominatim atbildes
    private function formatNominatimAddress(array $item): string
    {
        // Mēģina izvilkt adreses daļas
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

    // Noņem duplikātus pēc tipa un koordinātēm
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

    // Noklusētais nosaukums, ja Nominatim neko neiedod
    private function defaultName(string $type): string
    {
        return match ($type) {
            'gas_station' => 'Degvielas uzpildes stacija',
            'service_center' => 'Autoserviss',
            'ev_charging' => 'EV uzlādes punkts',
            default => 'Lokācija',
        };
    }

    // Normalizē un pārbauda lokācijas tipu
    private function normalizeType(string $type): ?string
    {
        $type = Str::of($type)->trim()->lower()->toString();
        $allowed = ['all', 'gas_station', 'service_center', 'ev_charging'];

        // Atļauj tikai iepriekš definētus tipus
        return in_array($type, $allowed, true) ? $type : null;
    }

    // Normalizē bbox un ierobežo to Latvijas robežās
    private function normalizeBbox(string $bboxRaw, int $zoom): ?string
    {
        // Bbox formāts: south,west,north,east
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

        // Ja robežas samainītas vietām, salabo
        if ($south > $north) {
            [$south, $north] = [$north, $south];
        }
        if ($west > $east) {
            [$west, $east] = [$east, $west];
        }

        // Aptuvenās Latvijas robežas (lai neļautu meklēt visu pasauli)
        $lvSouth = 55.60;
        $lvNorth = 58.10;
        $lvWest = 20.90;
        $lvEast = 28.30;

        // Ja bbox ir ārpus Latvijas, atgriež null
        if ($north < $lvSouth || $south > $lvNorth || $east < $lvWest || $west > $lvEast) {
            return null;
        }

        // Apgriež bbox, lai tas paliek Latvijas robežās
        $south = max($south, $lvSouth);
        $north = min($north, $lvNorth);
        $west = max($west, $lvWest);
        $east = min($east, $lvEast);

        // Ierobežo bbox izmēru (pārāk stingrs limits Rīgā/atlasē lika tukšu karti)
        $maxSpan = match (true) {
            $zoom >= 14 => 1.2,
            $zoom >= 12 => 2.5,
            $zoom >= 11 => 4.0,
            $zoom >= 10 => 7.5,
            default => 8.0,
        };

        // Bbox augstums un platums grādos nedrīkst pārsniegt maxSpan
        if (($north - $south) > $maxSpan || ($east - $west) > $maxSpan) {
            return null;
        }

        return sprintf('%.6f,%.6f,%.6f,%.6f', $south, $west, $north, $east);
    }

    // Noapaļo bbox kešošanai atkarībā no zoom level
    private function cacheBbox(string $bbox, int $zoom): string
    {
        $parts = explode(',', $bbox);
        if (count($parts) !== 4) {
            return $bbox;
        }

        // Mazāks zoom nozīmē mazāku precizitāti, lai kešs vairāk trāpītu
        $precision = match (true) {
            $zoom >= 13 => 4,
            $zoom >= 11 => 3,
            default => 2,
        };

        $nums = array_map('floatval', $parts);
        $fmt = '%.'.$precision.'f';

        return sprintf($fmt.','.$fmt.','.$fmt.','.$fmt, $nums[0], $nums[1], $nums[2], $nums[3]);
    }

    // Pārvērš bbox tekstu masīvā ar 4 skaitļiem
    private function parseBbox(string $bbox): array
    {
        $parts = array_map('floatval', explode(',', $bbox));

        return [$parts[0], $parts[1], $parts[2], $parts[3]];
    }
}
