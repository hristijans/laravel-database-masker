<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class DefaultMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['default'];

    /**
     * Check if this masker can handle the given mask type.
     */
    public function canHandle(string $type): bool
    {
        // Default masker handles any type as a fallback
        return true;
    }

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        return $this->faker->text(50);
    }
}
