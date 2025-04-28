<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string createMaskedDump(?string $outputFile = null)
 * @method static array createMaskedDumps(?string $outputPath = null)
 * @method static array createMaskedDumpForConnection(string $connectionName, array $connectionConfig, ?string $outputFile = null)
 * @method static bool restoreMaskedDump(?string $inputFile = null, ?string $connectionName = null)
 *
 * @see \Hristijans\DatabaseMasker\Contracts\DatabaseMaskerInterface
 */
final class DatabaseMasker extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'database-masker';
    }
}
