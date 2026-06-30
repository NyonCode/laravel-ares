<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\SubjectData;
use NyonCode\Ares\Jobs\IndexAresSubject;
use NyonCode\Ares\Models\AresSubject;
use NyonCode\Ares\Services\SubjectSearchService;

beforeEach(function () {
    $this->app['config']->set('database.default', 'testing');
    $this->app['config']->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
});

it('indexes a subject when findCompany succeeds and auto_index is enabled', function () {
    Http::fake([
        'https://ares.gov.cz/*' => Http::response([
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'sidlo' => [
                'nazevObce' => 'Praha',
            ],
        ]),
    ]);

    $client = app(AresClientInterface::class);
    $client->findCompany('27074358');

    IndexAresSubject::dispatchSync(
        ic: '27074358',
        name: 'Asseco Central Europe, a.s.',
        city: 'Praha',
    );

    expect(AresSubject::find('27074358'))
        ->not->toBeNull()
        ->name->toBe('Asseco Central Europe, a.s.')
        ->city->toBe('Praha');
});

it('searches subjects by name', function () {
    AresSubject::query()->create(['ic' => '27074358', 'name' => 'Asseco Central Europe, a.s.', 'city' => 'Praha']);
    AresSubject::query()->create(['ic' => '25596641', 'name' => 'Skoda Auto a.s.', 'city' => 'Mlada Boleslav']);
    AresSubject::query()->create(['ic' => '00177041', 'name' => 'Skoda Investment a.s.', 'city' => 'Praha']);

    $search = app(SubjectSearchService::class);
    $results = $search->search('Skoda');

    expect($results)->toHaveCount(2)
        ->and($results->first()->name)->toContain('Skoda');
});

it('searches subjects by ic prefix', function () {
    AresSubject::query()->create(['ic' => '27074358', 'name' => 'Asseco Central Europe, a.s.', 'city' => 'Praha']);
    AresSubject::query()->create(['ic' => '27082440', 'name' => 'Another Company s.r.o.', 'city' => 'Brno']);
    AresSubject::query()->create(['ic' => '25596641', 'name' => 'Skoda Auto a.s.', 'city' => 'Mlada Boleslav']);

    $search = app(SubjectSearchService::class);
    $results = $search->search('2707');

    expect($results)->toHaveCount(1)
        ->and($results->first()->ic)->toBe('27074358');
});

it('respects the search limit', function () {
    for ($i = 1; $i <= 5; $i++) {
        AresSubject::query()->create([
            'ic' => str_pad((string) $i, 8, '0', STR_PAD_LEFT),
            'name' => "Test Company {$i}",
            'city' => 'Praha',
        ]);
    }

    $search = app(SubjectSearchService::class);
    $results = $search->search('Test', 3);

    expect($results)->toHaveCount(3);
});

it('returns empty collection for empty query', function () {
    $search = app(SubjectSearchService::class);
    $results = $search->search('');

    expect($results)->toBeEmpty();
});

it('returns SubjectData DTOs from search', function () {
    AresSubject::query()->create(['ic' => '27074358', 'name' => 'Asseco Central Europe, a.s.', 'city' => 'Praha']);

    $search = app(SubjectSearchService::class);
    $results = $search->search('Asseco');

    expect($results->first())
        ->toBeInstanceOf(SubjectData::class)
        ->ic->toBe('27074358')
        ->name->toBe('Asseco Central Europe, a.s.')
        ->city->toBe('Praha');
});

it('indexes subject via job', function () {
    IndexAresSubject::dispatchSync(
        ic: '27074358',
        name: 'Asseco Central Europe, a.s.',
        city: 'Praha',
    );

    $subject = AresSubject::find('27074358');

    expect($subject)
        ->not->toBeNull()
        ->name->toBe('Asseco Central Europe, a.s.')
        ->city->toBe('Praha');
});

it('updates existing subject via job', function () {
    AresSubject::query()->create([
        'ic' => '27074358',
        'name' => 'Old Name',
        'city' => 'Brno',
    ]);

    IndexAresSubject::dispatchSync(
        ic: '27074358',
        name: 'New Name',
        city: 'Praha',
    );

    $subject = AresSubject::find('27074358');

    expect($subject)
        ->name->toBe('New Name')
        ->city->toBe('Praha');
});

it('searches via the client facade', function () {
    AresSubject::query()->create(['ic' => '27074358', 'name' => 'Asseco Central Europe, a.s.', 'city' => 'Praha']);

    $results = app(AresClientInterface::class)->search('Asseco');

    expect($results)->toHaveCount(1)
        ->and($results->first()->ic)->toBe('27074358');
});

it('escapes LIKE wildcards in name search', function () {
    AresSubject::query()->create(['ic' => '27074358', 'name' => 'Test 100% Company', 'city' => 'Praha']);
    AresSubject::query()->create(['ic' => '25596641', 'name' => 'Test Normal Company', 'city' => 'Brno']);

    $search = app(SubjectSearchService::class);

    $results = $search->search('100%');

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Test 100% Company');
});

it('escapes LIKE wildcards in ic search', function () {
    AresSubject::query()->create(['ic' => '27074358', 'name' => 'Company A', 'city' => 'Praha']);
    AresSubject::query()->create(['ic' => '25596641', 'name' => 'Company B', 'city' => 'Brno']);

    $search = app(SubjectSearchService::class);

    $results = $search->search('2707_358');

    expect($results)->toBeEmpty();
});

it('serializes SubjectData to JSON', function () {
    $subject = new SubjectData(
        ic: '27074358',
        name: 'Asseco Central Europe, a.s.',
        city: 'Praha',
    );

    $json = json_encode($subject, JSON_THROW_ON_ERROR);

    expect(json_decode($json, true))->toBe([
        'ic' => '27074358',
        'name' => 'Asseco Central Europe, a.s.',
        'city' => 'Praha',
    ]);
});

it('returns unique job id based on ic', function () {
    $job = new IndexAresSubject(
        ic: '27074358',
        name: 'Test',
        city: 'Praha',
    );

    expect($job->uniqueId())->toBe('27074358');
});

it('reports stale subjects count', function () {
    AresSubject::query()->create([
        'ic' => '27074358',
        'name' => 'Old Company',
        'city' => 'Praha',
        'indexed_at' => now()->subDays(60),
    ]);

    AresSubject::query()->create([
        'ic' => '25596641',
        'name' => 'Fresh Company',
        'city' => 'Brno',
        'indexed_at' => now(),
    ]);

    $search = app(SubjectSearchService::class);

    expect($search->staleCount(30))->toBe(1)
        ->and($search->subjectCount())->toBe(2);
});
