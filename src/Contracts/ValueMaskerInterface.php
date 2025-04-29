<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Contracts;

interface ValueMaskerInterface
{
    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): mixed;

    /**
     * Check if this masker can handle the given mask type.
     */
    public function canHandle(string $type): bool;
}
