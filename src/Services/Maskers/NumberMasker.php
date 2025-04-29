<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class NumberMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['number', 'randomNumber'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): int
    {
        $min = (int) ($columnConfig['min'] ?? 1);
        $max = (int) ($columnConfig['max'] ?? 1000);

        return $this->faker->numberBetween($min, $max);
    }
}
