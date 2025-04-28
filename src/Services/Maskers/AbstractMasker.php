<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Services\Maskers;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Hristijans\DatabaseMasker\Contracts\ValueMaskerInterface;

abstract class AbstractMasker implements ValueMaskerInterface
{
    protected Generator $faker;

    /**
     * @var array<string>
     */
    protected array $supportedTypes = [];

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * Check if this masker can handle the given mask type.
     */
    public function canHandle(string $type): bool
    {
        return in_array($type, $this->supportedTypes);
    }

    /**
     * Mask a value based on configuration.
     *
     * @param  array<string, mixed>  $columnConfig
     */
    abstract public function mask(mixed $originalValue, array $columnConfig): mixed;
}
