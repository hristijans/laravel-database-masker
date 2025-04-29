<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class IpAddressMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['ipv4', 'ipv6'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        $type = $columnConfig['type'] ?? 'ipv4';

        return match ($type) {
            'ipv6' => $this->faker->ipv6(),
            default => $this->faker->ipv4()
        };
    }
}
