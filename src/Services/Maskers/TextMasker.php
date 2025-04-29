<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class TextMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['text'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        $length = (int) ($columnConfig['length'] ?? 100);

        return $this->faker->text($length);
    }
}
