<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class NameMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['name', 'firstName', 'lastName'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        $type = $columnConfig['type'] ?? 'name';

        return match ($type) {
            'firstName' => $this->faker->firstName(),
            'lastName' => $this->faker->lastName(),
            default => $this->faker->name()
        };
    }
}
