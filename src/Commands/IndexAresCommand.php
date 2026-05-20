<?php

declare(strict_types=1);

namespace NyonCode\Ares\Commands;

use Illuminate\Console\Command;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Jobs\IndexAresSubject;
use NyonCode\Ares\Services\SubjectSearchService;

final class IndexAresCommand extends Command
{
    protected $signature = 'ares:index
        {ics?* : ICO subjektu k indexovani}
        {--refresh-stale : Preindexovat zastarale zaznamy}
        {--stale-days= : Pocet dnu pro zastarale zaznamy}
        {--limit=100 : Maximalni pocet zaznamu k preindexovani}';

    protected $description = 'Indexovat ARES subjekty pro naseptavani';

    public function handle(AresClientInterface $client, SubjectSearchService $search): int
    {
        /** @var array<int, string> $ics */
        $ics = $this->argument('ics');

        if ($this->option('refresh-stale')) {
            return $this->refreshStale($client, $search);
        }

        if ($ics === []) {
            $this->components->info("Celkem indexovano: {$search->subjectCount()} subjektu.");

            $staleDays = $this->configStaleDays();
            $staleCount = $search->staleCount($staleDays);

            if ($staleCount > 0) {
                $this->components->warn("Zastaralych zaznamu (starsi nez {$staleDays} dni): {$staleCount}");
            }

            return self::SUCCESS;
        }

        return $this->indexIcs($client, $ics);
    }

    /**
     * @param  array<int, string>  $ics
     */
    private function indexIcs(AresClientInterface $client, array $ics): int
    {
        $indexed = 0;
        $failed = 0;

        $this->components->task('Indexovani subjektu', function () use ($client, $ics, &$indexed, &$failed) {
            foreach ($ics as $ic) {
                $company = $client->findCompany($ic);

                if ($company === null) {
                    $failed++;

                    continue;
                }

                IndexAresSubject::dispatchSync(
                    ic: $company->ic,
                    name: $company->name,
                    city: $company->registeredOffice?->city,
                );

                $indexed++;
            }
        });

        $this->newLine();
        $this->components->info("Indexovano: {$indexed}, Neuspesnych: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function refreshStale(AresClientInterface $client, SubjectSearchService $search): int
    {
        $staleDays = $this->configStaleDays();
        $limit = (int) $this->option('limit');
        $staleSubjects = $search->staleSubjects($staleDays, $limit);

        if ($staleSubjects->isEmpty()) {
            $this->components->info('Zadne zastarale zaznamy k preindexovani.');

            return self::SUCCESS;
        }

        $this->components->info("Preindexovani {$staleSubjects->count()} zastaralych zaznamu...");

        $refreshed = 0;
        $failed = 0;

        foreach ($staleSubjects as $subject) {
            $company = $client->findCompany($subject->ic);

            if ($company === null) {
                $failed++;

                continue;
            }

            IndexAresSubject::dispatchSync(
                ic: $company->ic,
                name: $company->name,
                city: $company->registeredOffice?->city,
            );

            $refreshed++;
        }

        $this->components->info("Obnoveno: {$refreshed}, Neuspesnych: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function configStaleDays(): int
    {
        $option = $this->option('stale-days');

        if ($option !== null) {
            return (int) $option;
        }

        $configValue = config('ares.indexing.stale_days');

        return is_numeric($configValue) ? (int) $configValue : 30;
    }
}
