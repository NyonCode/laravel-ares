<?php

declare(strict_types=1);

namespace NyonCode\Ares\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Models\AresSubject;

final class IndexAresSubject implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $ic,
        private readonly string $name,
        private readonly ?string $city,
    ) {
        $queue = config('ares.indexing.queue');
        $connection = config('ares.indexing.connection');

        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }

        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }
    }

    public static function fromCompanyData(CompanyData $company): self
    {
        return new self(
            ic: $company->ic,
            name: $company->name,
            city: $company->registeredOffice?->city,
        );
    }

    public function uniqueId(): string
    {
        return $this->ic;
    }

    public function handle(): void
    {
        AresSubject::query()->updateOrCreate(
            ['ic' => $this->ic],
            [
                'name' => $this->name,
                'city' => $this->city,
                'indexed_at' => now(),
            ],
        );
    }
}
