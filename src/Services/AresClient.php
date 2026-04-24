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
    private string $baseUrl;

    private int $cacheTtl;

    private LoggerInterface $logger;

    private Dispatcher $events;

    private Cache $cache;

    public function __construct(
        string $baseUrl,
        int $cacheTtl,
        LoggerInterface $logger,
        Dispatcher $events,
        Cache $cache,
        private readonly Http $http,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cacheTtl = $cacheTtl;
        $this->logger = $logger;
        $this->events = $events;
        $this->cache = $cache;
    }

    public function findCompany(string $ic): ?CompanyData
    {
        $ic = $this->normalizeIc($ic);

        if (! $this->isValidIc($ic)) {
            $this->logger->warning('Invalid IC format', ['ic' => $ic]);

            return null;
        }

        $cacheKey = $this->cacheKey($ic);

        return $this->cache->remember($cacheKey, $this->cacheTtl, function () use ($ic) {
            try {
                $response = $this->http
                    ->timeout($this->httpTimeout())
                    ->connectTimeout($this->httpConnectTimeout())
                    ->get("{$this->baseUrl}/ekonomicke-subjekty/{$ic}");

                if ($response->failed()) {
                    $this->events->dispatch(new CompanyLookupFailed($ic, $response->status()));

                    return null;
                }

                $company = CompanyData::fromApiResponse(
                    $this->payloadData($response->json())
                );
                $this->events->dispatch(new CompanyLookupSucceeded($company));

                return $company;
            } catch (Throwable $e) {
                $this->logger->error('ARES API error', [
                    'ic' => $ic,
                    'exception' => $e->getMessage(),
                ]);
                $this->events->dispatch(new CompanyLookupFailed($ic, 0, $e));

                return null;
            }
        });
    }

    public function findCompanyRaw(string $ic): ?array
    {
        return $this->findCompany($ic)?->rawData;
    }

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

    public function normalizeIc(string $ic): string
    {
        return str_pad(preg_replace('/\D/', '', $ic) ?? '', 8, '0', STR_PAD_LEFT);
    }

    private function cacheKey(string $ic): string
    {
        return "ares:company:{$ic}";
    }

    private function httpTimeout(): float
    {
        $timeout = config('ares.http_options.timeout');

        return is_numeric($timeout) ? (float) $timeout : 5.0;
    }

    private function httpConnectTimeout(): float
    {
        $timeout = config('ares.http_options.connect_timeout');

        return is_numeric($timeout) ? (float) $timeout : 3.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadData(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw InvalidApiResponseException::invalidPayloadType();
        }

        $normalized = [];

        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
