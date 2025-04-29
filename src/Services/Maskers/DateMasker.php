<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class DateMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['date', 'datetime', 'time'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        $type = $columnConfig['type'] ?? 'date';

        return match ($type) {
            'datetime' => $this->faker->dateTime()->format($columnConfig['format'] ?? 'Y-m-d H:i:s'),
            'time' => $this->faker->time($columnConfig['format'] ?? 'H:i:s'),
            default => $this->faker->date($columnConfig['format'] ?? 'Y-m-d')
        };
    }
}
