<?php

declare(strict_types=1);

namespace NyonCode\Ares\Facades;

use Illuminate\Support\Facades\Facade;
use NyonCode\Ares\Contracts\AresClientInterface;

/**
 * @method static \NyonCode\Ares\Data\CompanyData|null findCompany(string $ic)
 * @method static array<string, mixed>|null findCompanyRaw(string $ic)
 * @method static \NyonCode\Ares\Data\CompanyData findCompanyOrFail(string $ic)
 * @method static bool forgetCompany(string $ic)
 * @method static bool isValidIc(string $ic)
 * @method static string normalizeIc(string $ic)
 *
 * @see AresClientInterface
 */
final class Ares extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AresClientInterface::class;
    }
}
