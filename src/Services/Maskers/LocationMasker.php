<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class LocationMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['city', 'country', 'postcode', 'state', 'streetName', 'streetAddress'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        $type = $columnConfig['type'] ?? 'city';

        return match ($type) {
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'postcode' => $this->faker->postcode(),
            'state' => $this->faker->state(),
            'streetName' => $this->faker->streetName(),
            'streetAddress' => $this->faker->streetAddress(),
            default => $this->faker->city()
        };
    }
}
