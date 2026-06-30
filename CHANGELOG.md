# Changelog

All notable changes to `laravel-ares` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.6]

### Removed
- Removed the experimental Livewire search/lookup components (`AresSearch`,
  `AresLookup`) and the `livewire/livewire` runtime dependency. The package is
  now a lean API/indexing library; UI can be built on top of `Ares::search()`.

### Fixed
- Subject name search now performs a true substring match (`LIKE '%term%'`) on
  every database driver. Previously the MySQL branch used an invalid
  `MATCH … AGAINST('*term*' IN BOOLEAN MODE)` expression whose leading `*` was
  ignored, silently degrading to prefix matching and diverging from the
  documented behaviour.
- Fixed a broken PHPStan configuration (`excludePaths` referenced a removed
  directory) and applied Pint formatting, restoring a green CI pipeline.

### Changed
- The `ares_subjects` migration no longer creates an unused MySQL `FULLTEXT`
  index (it does not accelerate `LIKE` substring queries). PostgreSQL keeps its
  trigram GIN index; other drivers use a plain index on `name`.
- Stopped tracking IDE (`.idea/`) and tooling cache directories in git.
