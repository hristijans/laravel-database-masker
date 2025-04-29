<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class UuidMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['uuid'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        return $this->faker->uuid();
    }
}
