# Changelog

All notable changes to `laravel-ares` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0]

### Added
- `cache.enabled` config option (`ARES_CACHE_ENABLED`) to disable caching entirely.
- `cache.store` (`ARES_CACHE_STORE`) to select a specific cache store, and
  `cache.prefix` (`ARES_CACHE_PREFIX`) to customize cache key prefixes.
- CI coverage for PHP 8.5.

### Changed
- **Breaking:** the `cache_ttl` config key moved under a `cache` array as
  `cache.ttl`. Republish the config or rename the key when upgrading.
- Configuration is now read through Laravel's built-in typed config accessors.

### Removed
- **Breaking:** dropped support for Laravel 10; Laravel 11+ is now required.

## [1.0.0]

Initial public release.

### Added
- ARES API client (`findCompany`, `findCompanyOrFail`, `findCompanyRaw`,
  `forgetCompany`) exposed via the `Ares` facade, the `ares` container binding
  and the `AresClientInterface` contract.
- IČO validation with full modulo-11 checksum and normalization
  (`isValidIc`, `normalizeIc`); invalid values are rejected before any request.
- Response caching with configurable TTL and self-healing of corrupted cached
  payloads, plus configurable HTTP timeout / connect-timeout.
- Immutable, typed data objects: `CompanyData`, `AddressData`,
  `DeliveryAddressData`, `RegistrationData`, `RegistrationStatusData`,
  `SubjectData`, and the `RegistrationSourceState` enum.
- `CompanyLookupSucceeded` and `CompanyLookupFailed` events.
- Fluent query builder with chainable filters (`active`, `inactive`,
  `legalForm`, `withVat`, `search`, `limit`, …) and terminals.
- Global helper functions (`ares()`, `ares_is_company_active()`,
  `ares_get_address()`, `ares_validate_ic()`, `ares_search()`, …).
- Subject indexing into the `ares_subjects` table with auto-indexing via a
  queued, unique `IndexAresSubject` job, stale-record tracking, and local
  substring/prefix search through `Ares::search()`.
- Artisan commands `ares:test` and `ares:index`.
- Czech and English translations, publishable config, and full documentation.

### Compatibility
- PHP 8.2 – 8.4
- Laravel 10 / 11 / 12 / 13
