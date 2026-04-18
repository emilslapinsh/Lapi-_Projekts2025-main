<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
     * Tiek izmantots:
     * - Overpass API (OpenStreetMap dati)
     * - Kešošana (Cache), lai nesūtītu pārāk daudz pieprasījumu
     */
    public function fetchLocations(Request $request)
    {
        $bbox = $request->query('bbox');
        $zoom = (int) $request->query('zoom', 0);
        $type = $request->query('type', 'all');

        // Ja nav kartes robežu, neko neatgriežam
        if (!$bbox) {
            return response()->json([]);
        }

        // Zoom kontrole pret pārlieku lielu datu apjomu
        $allowGasAndService = $zoom >= 10;
        $allowEv = $zoom >= 11;

        if ($type === 'gas_station' && !$allowGasAndService) return response()->json([]);
        if ($type === 'service_center' && !$allowGasAndService) return response()->json([]);
        if ($type === 'ev_charging' && !$allowEv) return response()->json([]);

        $results = [];

        // Ja izvēlēts "all", ielādējam vairākus tipus atkarībā no zoom
        if ($type === 'all') {

            if ($allowGasAndService) {
                $results = array_merge($results, $this->fetchOverpass($bbox, 'gas_station'));
                $results = array_merge($results, $this->fetchOverpass($bbox, 'service_center'));
            }

            if ($allowEv) {
                $results = array_merge($results, $this->fetchOverpass($bbox, 'ev_charging'));
            }

            return response()->json($results);
        }

        // Ja konkrēts tips
        return response()->json($this->fetchOverpass($bbox, $type));
    }

    /**
     * Nosūta Overpass API pieprasījumu un apstrādā atbildi.
     * Rezultāts tiek saglabāts kešā uz 20 minūtēm.
     */
    private function fetchOverpass(string $bbox, string $type): array
    {
        $cacheKey = 'lv_overpass_' . $type . '_' . md5($bbox);

        return Cache::remember($cacheKey, now()->addMinutes(20), function () use ($bbox, $type) {

            $query = $this->buildQuery($bbox, $type);

            // Nosūtām POST pieprasījumu uz Overpass API
            $resp = Http::timeout(25)
                ->asForm()
                ->post('https://overpass-api.de/api/interpreter', [
                    'data' => $query
                ]);

            if (!$resp->ok()) return [];

            $json = $resp->json();
            $elements = $json['elements'] ?? [];

            $out = [];

            foreach ($elements as $el) {

                // Iegūst koordinātes
                $lat = $el['lat'] ?? ($el['center']['lat'] ?? null);
                $lon = $el['lon'] ?? ($el['center']['lon'] ?? null);
                if ($lat === null || $lon === null) continue;

                $tags = $el['tags'] ?? [];

                // Lokācijas nosaukums
                $name = $tags['name'] ?? $this->defaultName($type);

                // Adreses izveide
                $addr = $this->buildAddress($tags);
                if (!$addr) {
                    $addr = $tags['operator'] ?? 'Adrese nav norādīta';
                }

                $out[] = [
                    'name' => $name,
                    'type' => $type,
                    'address' => $addr,
                    'latitude' => (string) $lat,
                    'longitude' => (string) $lon,
                ];
            }

            return $out;
        });
    }

    /**
     * Izveido Overpass vaicājumu tikai Latvijai.
     */
    private function buildQuery(string $bbox, string $type): string
    {
        $area = 'area["ISO3166-1"="LV"]->.lv;';

        if ($type === 'gas_station') {
            $selector = '"amenity"="fuel"';
        } elseif ($type === 'service_center') {
            $selector = '"shop"="car_repair"';
        } else {
            $selector = '"amenity"="charging_station"';
        }

        return <<<OVERPASS
[out:json][timeout:25];
$area
(
  node[$selector]($bbox);
  way[$selector]($bbox);
  relation[$selector]($bbox);
);
out center tags;
OVERPASS;
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

    /**
     * Izveido adreses tekstu no OSM tagiem.
     */
    private function buildAddress(array $tags): string
    {
        $parts = [];

        if (!empty($tags['addr:street'])) $parts[] = $tags['addr:street'];
        if (!empty($tags['addr:housenumber'])) $parts[] = $tags['addr:housenumber'];
        if (!empty($tags['addr:city'])) $parts[] = $tags['addr:city'];

        return trim(implode(' ', $parts));
    }
}
