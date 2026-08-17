<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait GeocodesLocations
{
    protected function geocodeLocation(?string $locationName): ?array
    {
        if (empty(trim($locationName ?? ''))) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Ordo-TaskApp/1.0',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $locationName,
                'limit' => 1,
            ]);

            $results = $response->json();

            if (! empty($results) && isset($results[0]['lat'], $results[0]['lon'])) {
                return [
                    'latitude' => (float) $results[0]['lat'],
                    'longitude' => (float) $results[0]['lon'],
                    'location_name' => $results[0]['display_name'] ?? $locationName,
                ];
            }
        } catch (\Exception $e) {
            // geocoding fail hone par bhi task ban jaayega, bas location khali rahegi
        }

        return null;
    }
}