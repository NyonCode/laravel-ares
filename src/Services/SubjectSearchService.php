<?php

declare(strict_types=1);

namespace NyonCode\Ares\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use NyonCode\Ares\Data\SubjectData;
use NyonCode\Ares\Models\AresSubject;

final class SubjectSearchService
{
    /**
     * Search indexed subjects by name or IC.
     *
     * @return Collection<int, SubjectData>
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        if (ctype_digit($query)) {
            return $this->searchByIc($query, $limit);
        }

        return $this->searchByName($query, $limit);
    }

    /**
     * @return Collection<int, SubjectData>
     */
    private function searchByIc(string $query, int $limit): Collection
    {
        return AresSubject::query()
            ->whereRaw('ic LIKE ? ESCAPE ?', [self::escapeLike($query).'%', '\\'])
            ->orderBy('ic')
            ->limit($limit)
            ->get()
            ->map(fn (AresSubject $subject): SubjectData => $subject->toSubjectData());
    }

    /**
     * @return Collection<int, SubjectData>
     */
    private function searchByName(string $query, int $limit): Collection
    {
        $builder = AresSubject::query();

        $this->applyNameSearch($builder, $query);

        return $builder
            ->limit($limit)
            ->get()
            ->map(fn (AresSubject $subject): SubjectData => $subject->toSubjectData());
    }

    /**
     * @param  Builder<AresSubject>  $builder
     */
    private function applyNameSearch(Builder $builder, string $query): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $term = str_replace(['+', '-', '*', '~', '<', '>', '(', ')', '"'], '', $query);

            $builder
                ->whereRaw('MATCH (name) AGAINST (? IN BOOLEAN MODE)', ['*'.$term.'*'])
                ->orderByRaw('MATCH (name) AGAINST (? IN BOOLEAN MODE) DESC', ['*'.$term.'*']);

            return;
        }

        $escaped = self::escapeLike($query);

        $builder
            ->whereRaw('name LIKE ? ESCAPE ?', ['%'.$escaped.'%', '\\'])
            ->orderBy('name');
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function indexSubject(string $ic, string $name, ?string $city): void
    {
        AresSubject::query()->updateOrCreate(
            ['ic' => $ic],
            [
                'name' => $name,
                'city' => $city,
                'indexed_at' => now(),
            ],
        );
    }

    public function subjectCount(): int
    {
        return AresSubject::query()->count();
    }

    public function staleCount(int $days): int
    {
        return AresSubject::query()
            ->where('indexed_at', '<', now()->subDays($days))
            ->count();
    }

    /**
     * @return Collection<int, AresSubject>
     */
    public function staleSubjects(int $days, int $limit = 100): Collection
    {
        return AresSubject::query()
            ->where('indexed_at', '<', now()->subDays($days))
            ->orderBy('indexed_at')
            ->limit($limit)
            ->get();
    }
}
