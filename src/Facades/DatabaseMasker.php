<?php

namespace Hristijans\DatabaseMasker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string createMaskedDump(?string $outputFile = null)
 * @method static bool restoreMaskedDump(?string $inputFile = null)
 * 
 * @see \Hristijans\DatabaseMasker\DatabaseMasker
 */
class DatabaseMasker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'database-masker';
    }
}