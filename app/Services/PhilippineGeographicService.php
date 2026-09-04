<?php

namespace App\Services;

use App\Exceptions\PhilippineGeographicException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PhilippineGeographicService
{
    public function regions(): array
    {
        return $this->mapLocations($this->list('regions'));
    }

    public function provinces(string $regionCode): array
    {
        $this->assertCode($regionCode);

        $provinces = $this->mapLocations($this->list("regions/{$regionCode}/provinces"));

        if ($provinces !== []) {
            return $provinces;
        }

        // NCR has cities but no provinces in PSGC. TESDA forms still need a
        // province value, so Metro Manila is supplied and cities load by region.
        return [[
            'code' => $regionCode,
            'name' => 'Metro Manila',
            'label' => 'Metro Manila',
            'city_parent' => 'region',
        ]];
    }

    public function citiesByProvince(string $provinceCode): array
    {
        $this->assertCode($provinceCode);

        return $this->mapLocations($this->list("provinces/{$provinceCode}/cities-municipalities"));
    }

    public function citiesByRegion(string $regionCode): array
    {
        $this->assertCode($regionCode);

        return $this->mapLocations($this->list("regions/{$regionCode}/cities-municipalities"));
    }

    public function barangays(string $cityCode): array
    {
        $this->assertCode($cityCode);

        return $this->mapLocations($this->list("cities-municipalities/{$cityCode}/barangays"));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{code: string, name: string, label: string, city_parent?: string}>
     */
    private function mapLocations(array $rows): array
    {
        return collect($rows)
            ->map(function (mixed $row): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $code = trim((string) ($row['code'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                $regionName = trim((string) ($row['regionName'] ?? ''));

                if ($code === '' || $name === '') {
                    return null;
                }

                $label = $regionName !== '' && strcasecmp($regionName, $name) !== 0
                    ? "{$name} — {$regionName}"
                    : $name;

                return [
                    'code' => $code,
                    'name' => $name,
                    'label' => $label,
                ];
            })
            ->filter()
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function list(string $path): array
    {
        $payload = $this->fetch($path);

        return array_is_list($payload) ? $payload : [];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function fetch(string $path): array
    {
        $url = $this->url($path);
        $cacheKey = 'psgc:'.$path;

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.psgc.timeout', 5))
                ->connectTimeout(3)
                ->retry(1, 200, static fn ($exception): bool => $exception instanceof ConnectionException, throw: false)
                ->get($url);
        } catch (ConnectionException $exception) {
            // A stale cache entry (from a previous successful fetch) is far
            // more useful to the applicant than an empty error, so serve it
            // when PSGC is currently unreachable.
            if (is_array($cached)) {
                return $cached;
            }

            throw new PhilippineGeographicException('Address lookup is temporarily unavailable.', 0, $exception);
        }

        if (! $response->successful()) {
            if (is_array($cached)) {
                return $cached;
            }

            throw new PhilippineGeographicException('Address lookup is temporarily unavailable.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            if (is_array($cached)) {
                return $cached;
            }

            throw new PhilippineGeographicException('Address lookup is temporarily unavailable.');
        }

        // Only persist successful, non-empty responses so a transient upstream
        // failure never poisons the cache with an empty province list that
        // silently breaks the address cascade.
        if ($payload !== []) {
            Cache::put($cacheKey, $payload, now()->addDays(7));
        }

        return $payload;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.psgc.base_url'), '/').'/'.trim($path, '/').'/';
    }

    private function assertCode(string $code): void
    {
        if (! preg_match('/^\d{9,10}$/', $code)) {
            throw new PhilippineGeographicException('The requested geographic code is invalid.');
        }
    }
}
