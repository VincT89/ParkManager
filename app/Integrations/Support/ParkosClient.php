<?php

namespace App\Integrations\Support;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ParkosClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.parkos.base_url') ?? '', '/');
        $this->timeout = (int) config('services.parkos.timeout', 20);
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    protected function request(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->withToken($this->accessToken());
    }

    protected function accessToken(): string
    {
        $cacheKey = config('services.parkos.token_cache_key');

        return Cache::remember($cacheKey, config('services.parkos.token_cache_ttl'), function () {
            return $this->authenticate()['access_token'];
        });
    }

    public function authenticate(): array
    {
        $this->ensureConfigured();

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->asForm()
            ->post($this->url(config('services.parkos.auth_path')), [
                'grant_type' => 'password',
                'client_id' => config('services.parkos.client_id'),
                'client_secret' => config('services.parkos.client_secret'),
                'username' => config('services.parkos.username'),
                'password' => config('services.parkos.password'),
            ]);

        $response->throw();

        $data = $response->json();

        return $this->storeTokens($data);
    }



    private function storeTokens(array $data): array
    {

        Cache::put(
            config('services.parkos.token_cache_key'),
            $data['access_token'] ?? null,
            now()->addSeconds(
                (int) config('services.parkos.token_cache_ttl', 3300)
            )
        );

        return $data;
    }

    public function findBookingsByModification(Carbon $from, Carbon $to, ?string $merchantId = null): array
    {
        $records = [];
        $url = $this->url(config('services.parkos.reservations_path', '/v1/reservations'));
        
        $params = [
            'period_type' => 'updated_at',
            'from' => $from->toDateString(),
            'till' => $to->toDateString(),
        ];
        
        if ($merchantId) {
            $params['merchant_id'] = $merchantId;
        }

        do {
            $response = $this->request()->get($url, $params);
            $response->throw();
            
            $data = $response->json();
            $records = array_merge($records, array_values($data['data'] ?? []));

            $url = $data['paginator']['next_page_url'] ?? null;
            $params = []; // Clear params for next page as URL already contains them
        } while ($url);

        return $records;
    }

    private function ensureConfigured(): void
    {
        foreach (['base_url', 'client_id', 'client_secret', 'username', 'password'] as $key) {
            if (blank(config("services.parkos.$key"))) {
                throw new \RuntimeException("Parkos config mancante: {$key}");
            }
        }
    }
}
