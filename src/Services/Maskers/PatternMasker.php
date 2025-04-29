<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

final class PatternMasker extends AbstractMasker
{
    /**
     * @var array<string>
     */
    protected array $supportedTypes = ['numerify', 'lexify', 'bothify', 'regexify'];

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    public function mask(mixed $originalValue, array $columnConfig): string
    {
        $type = $columnConfig['type'] ?? 'numerify';

        return match ($type) {
            'numerify' => $this->faker->numerify($columnConfig['format'] ?? '###'),
            'lexify' => $this->faker->lexify($columnConfig['format'] ?? '????'),
            'bothify' => $this->faker->bothify($columnConfig['format'] ?? '##??'),
            'regexify' => $this->faker->regexify($columnConfig['regex'] ?? '[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}'),
            default => $this->faker->numerify('###')
        };
    }
}
