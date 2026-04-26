<?php

declare(strict_types=1);

namespace NyonCode\Ares\Services;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as Http;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Events\CompanyLookupFailed;
use NyonCode\Ares\Events\CompanyLookupSucceeded;
use NyonCode\Ares\Exceptions\CompanyNotFoundException;
use NyonCode\Ares\Exceptions\InvalidApiResponseException;
use NyonCode\Ares\Exceptions\InvalidIcException;
use Psr\Log\LoggerInterface;
use Throwable;

final class AresClient implements AresClientInterface
{
    private const CACHE_PREFIX = 'ares:v1:company:';

    private const DEFAULT_HTTP_TIMEOUT = 5.0;

    private const DEFAULT_HTTP_CONNECT_TIMEOUT = 3.0;

    private string $processedBaseUrl;

    /**
     * Create a new ARES client instance.
     *
     * @param  string  $baseUrl  The base URL for the ARES API
     * @param  int  $cacheTtl  The cache time-to-live in seconds
     * @param  LoggerInterface  $logger  The logger instance
     * @param  Dispatcher  $events  The event dispatcher
     * @param  Cache  $cache  The cache repository
     * @param  Http  $http  The HTTP client factory
     * @param  float  $httpTimeout  The HTTP request timeout in seconds
     * @param  float  $httpConnectTimeout  The HTTP connection timeout in seconds
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $cacheTtl,
        private readonly LoggerInterface $logger,
        private readonly Dispatcher $events,
        private readonly Cache $cache,
        private readonly Http $http,
        private readonly float $httpTimeout = self::DEFAULT_HTTP_TIMEOUT,
        private readonly float $httpConnectTimeout = self::DEFAULT_HTTP_CONNECT_TIMEOUT,
    ) {
        $this->processedBaseUrl = rtrim($this->baseUrl, '/');
    }

    /**
     * Find a company by its identification number.
     *
     * @param  string  $ic  The company identification number
     * @return CompanyData|null The company data or null if not found/invalid
     */
    public function findCompany(string $ic): ?CompanyData
    {
        $normalizedIc = $this->normalizeIc($ic);

        if (! $this->isValidIc($normalizedIc)) {
            $this->logger->warning('Invalid IC format', ['ic' => $normalizedIc]);

            return null;
        }

        $forceRefresh = false;

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $payloadLookup = $this->findPayload($normalizedIc, $forceRefresh);

            if ($payloadLookup === null) {
                return null;
            }

            try {
                $company = CompanyData::fromApiResponse($payloadLookup['payload']);
            } catch (Throwable $e) {
                if ($payloadLookup['from_cache']) {
                    $this->logger->warning('Invalid cached company payload detected, flushing', [
                        'ic' => $normalizedIc,
                        'key' => $this->cacheKey($normalizedIc),
                        'exception' => $e->getMessage(),
                    ]);

                    $this->cache->forget($this->cacheKey($normalizedIc));
                    $forceRefresh = true;

                    continue;
                }

                $this->reportLookupException($normalizedIc, $e);
                $this->cache->forget($this->cacheKey($normalizedIc));

                return null;
            }

            $this->events->dispatch(new CompanyLookupSucceeded($company));

            return $company;
        }

        return null;
    }

    /**
     * Find a company by its identification number and return raw API data.
     *
     * @param  string  $ic  The company identification number
     * @return array<string, mixed>|null The raw API response data or null if not found
     */
    public function findCompanyRaw(string $ic): ?array
    {
        $normalizedIc = $this->normalizeIc($ic);

        if (! $this->isValidIc($normalizedIc)) {
            $this->logger->warning('Invalid IC format', ['ic' => $normalizedIc]);

            return null;
        }

        $payloadLookup = $this->findPayload($normalizedIc);

        return $payloadLookup['payload'] ?? null;
    }

    /**
     * Find a company by its identification number or throw an exception.
     *
     * @param  string  $ic  The company identification number
     * @return CompanyData The company data
     *
     * @throws InvalidIcException When the IC format is invalid
     * @throws CompanyNotFoundException When the company is not found
     */
    public function findCompanyOrFail(string $ic): CompanyData
    {
        $normalizedIc = $this->normalizeIc($ic);

        if (! $this->isValidIc($normalizedIc)) {
            throw InvalidIcException::forIc($normalizedIc);
        }

        $company = $this->findCompany($normalizedIc);

        if ($company === null) {
            throw CompanyNotFoundException::forIc($normalizedIc);
        }

        return $company;
    }

    /**
     * Remove a company from the cache.
     *
     * @param  string  $ic  The company identification number
     * @return bool True if the cache entry was removed, false otherwise
     */
    public function forgetCompany(string $ic): bool
    {
        return $this->cache->forget($this->cacheKey($this->normalizeIc($ic)));
    }

    public function isValidIc(string $ic): bool
    {
        $ic = $this->normalizeIc($ic);

        if (! preg_match('/^\d{8}$/', $ic)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int) $ic[$i] * (8 - $i);
        }

        $remainder = $sum % 11;
        $checksum = match ($remainder) {
            0 => 1,
            1 => 0,
            default => 11 - $remainder,
        };

        return $checksum === (int) $ic[7];
    }

    /**
     * Normalize the identification number by removing non-digit characters and padding.
     *
     * @param  string  $ic  The identification number to normalize
     * @return string The normalized 8-digit identification number
     */
    public function normalizeIc(string $ic): string
    {
        return str_pad(preg_replace('/\D/', '', $ic) ?? '', 8, '0', STR_PAD_LEFT);
    }

    private function cacheKey(string $ic): string
    {
        return self::CACHE_PREFIX.$ic;
    }

    /**
     * @return array{payload: array<string, mixed>, from_cache: bool}|null
     */
    private function findPayload(string $normalizedIc, bool $forceRefresh = false): ?array
    {
        $cacheKey = $this->cacheKey($normalizedIc);

        if (! $forceRefresh && $this->cache->has($cacheKey)) {
            $payload = $this->cache->get($cacheKey);

            if (is_array($payload)) {
                return [
                    'payload' => $this->payloadData($payload),
                    'from_cache' => true,
                ];
            }

            $this->logger->warning('Invalid cache payload detected, flushing', [
                'key' => $cacheKey,
                'type' => gettype($payload),
            ]);

            $this->cache->forget($cacheKey);
        }

        $payload = $this->requestPayload($normalizedIc);

        if ($payload === null) {
            return null;
        }

        if ($this->cacheTtl > 0) {
            $this->cache->put($cacheKey, $payload, $this->cacheTtl);
        }

        return [
            'payload' => $payload,
            'from_cache' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadData(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw InvalidApiResponseException::invalidPayloadType();
        }

        $normalizedPayload = [];

        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $normalizedPayload[$key] = $value;
            }
        }

        return $normalizedPayload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestPayload(string $normalizedIc): ?array
    {
        try {
            $response = $this->http
                ->timeout($this->httpTimeout)
                ->connectTimeout($this->httpConnectTimeout)
                ->acceptJson()
                ->get($this->companyUrl($normalizedIc));

            if ($response->failed()) {
                $this->logger->warning('ARES lookup failed with HTTP status', [
                    'ic' => $normalizedIc,
                    'status' => $response->status(),
                ]);

                $this->events->dispatch(new CompanyLookupFailed($normalizedIc, $response->status()));

                return null;
            }

            return $this->payloadData($response->json());
        } catch (Throwable $e) {
            $this->reportLookupException($normalizedIc, $e);

            return null;
        }
    }

    private function companyUrl(string $normalizedIc): string
    {
        return "{$this->processedBaseUrl}/ekonomicke-subjekty/{$normalizedIc}";
    }

    private function reportLookupException(string $normalizedIc, Throwable $exception): void
    {
        $this->logger->error('ARES API error', [
            'ic' => $normalizedIc,
            'exception' => $exception->getMessage(),
        ]);

        $this->events->dispatch(new CompanyLookupFailed($normalizedIc, 0, $exception));
    }
}
